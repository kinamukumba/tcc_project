<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");
include_once '../config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'utente') {
    http_response_code(403);
    echo json_encode(array("message" => "Acesso negado."));
    exit;
}

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$id_cliente = $_SESSION['role_id'];

if($method == 'GET') {
    $query = "SELECT r.id_reserva, r.data_checkin, r.data_checkout, r.status_reserva, s.tipos_servicos as servico, s.preço, r.n_pessoa
              FROM reserva r
              LEFT JOIN serviço s ON r.id_serviço = s.id_serviço
              WHERE r.id_cliente = :id_cliente
              ORDER BY r.data_reserva DESC";
              
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_cliente', $id_cliente);
    $stmt->execute();
    
    $reservas = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        // Calcular preco total (mock para dias - assumindo 1 se checkin = checkout)
        $date1 = new DateTime($row['data_checkin']);
        $date2 = new DateTime($row['data_checkout']);
        $diff = $date1->diff($date2)->days;
        $dias = $diff > 0 ? $diff : 1;
        
        $precoTotal = $row['preço'] * $dias;
        $row['preco_total'] = number_format($precoTotal, 2, ',', '.') . ' KZ';
        array_push($reservas, $row);
    }
    
    http_response_code(200);
    echo json_encode($reservas);
} 
else if($method == 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    if(!empty($data->data_checkin) && !empty($data->data_checkout) && !empty($data->id_servico) && !empty($data->n_pessoa)) {
        
        $query = "INSERT INTO reserva (id_cliente, id_serviço, n_pessoa, data_reserva, data_checkin, data_checkout, status_reserva) 
                  VALUES (:id_cliente, :id_servico, :n_pessoa, NOW(), :checkin, :checkout, 'pendente')";
        $stmt = $db->prepare($query);
        
        $stmt->bindParam(':id_cliente', $id_cliente);
        $stmt->bindParam(':id_servico', $data->id_servico);
        $stmt->bindParam(':n_pessoa', $data->n_pessoa);
        $stmt->bindParam(':checkin', $data->data_checkin);
        $stmt->bindParam(':checkout', $data->data_checkout);
        
        if($stmt->execute()) {
            http_response_code(201);
            echo json_encode(array("message" => "Reserva efetuada com sucesso!"));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Não foi possível efetuar a reserva."));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Dados incompletos."));
    }
}
?>
