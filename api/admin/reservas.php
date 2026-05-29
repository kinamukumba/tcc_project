<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");
include_once '../config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    http_response_code(403);
    echo json_encode(array("message" => "Acesso negado."));
    exit;
}

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if($method == 'GET') {
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    
    $query = "SELECT r.id_reserva, u.nome as utente, s.tipos_servicos as servico, 
              r.data_checkin, r.data_checkout, r.status_reserva, r.n_pessoa
              FROM reserva r 
              JOIN cliente c ON r.id_cliente = c.id_cliente
              JOIN usuario u ON c.id_usuario = u.id_usuario
              JOIN serviço s ON r.id_serviço = s.id_serviço";
    
    if($status && $status != 'Todas') {
        $query .= " WHERE r.status_reserva = :status";
    }
    
    $query .= " ORDER BY r.data_reserva DESC";
    
    $stmt = $db->prepare($query);
    if($status && $status != 'Todas') {
        $stmt->bindParam(':status', $status);
    }
    $stmt->execute();
    
    $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    http_response_code(200);
    echo json_encode($reservas);
} 
else if($method == 'POST') {
    // Mudar status da reserva
    $data = json_decode(file_get_contents("php://input"));
    
    if(!empty($data->id_reserva) && !empty($data->novo_status)) {
        $query = "UPDATE reserva SET status_reserva = :status WHERE id_reserva = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':status', $data->novo_status);
        $stmt->bindParam(':id', $data->id_reserva);
        
        if($stmt->execute()) {
            // Criar notificação para o cliente
            $qInfo = "SELECT id_cliente, codigo_reserva FROM reserva WHERE id_reserva = :id LIMIT 1";
            $sInfo = $db->prepare($qInfo);
            $sInfo->execute([':id' => $data->id_reserva]);
            $resInfo = $sInfo->fetch(PDO::FETCH_ASSOC);
            
            if ($resInfo && !empty($resInfo['id_cliente'])) {
                $msg = "";
                switch ($data->novo_status) {
                    case 'aprovada':
                        $msg = "A sua reserva " . $resInfo['codigo_reserva'] . " foi APROVADA.";
                        break;
                    case 'rejeitada':
                        $msg = "A sua reserva " . $resInfo['codigo_reserva'] . " foi REJEITADA.";
                        break;
                    case 'checkin':
                        $msg = "Check-in realizado com sucesso para a reserva " . $resInfo['codigo_reserva'] . ". Bem-vindo!";
                        break;
                    case 'checkout':
                        $msg = "Check-out realizado com sucesso para a reserva " . $resInfo['codigo_reserva'] . ". Obrigado pela estadia!";
                        break;
                    case 'concluida':
                        $msg = "A sua reserva " . $resInfo['codigo_reserva'] . " foi CONCLUÍDA.";
                        break;
                    case 'cancelada':
                        $msg = "A sua reserva " . $resInfo['codigo_reserva'] . " foi CANCELADA.";
                        break;
                }
                
                if (!empty($msg)) {
                    $qNotify = "INSERT INTO notificacao (id_cliente, mensagem, lida, data_criacao) VALUES (:id_cliente, :msg, 0, NOW())";
                    $sNotify = $db->prepare($qNotify);
                    $sNotify->execute([
                        ':id_cliente' => $resInfo['id_cliente'],
                        ':msg' => $msg
                    ]);
                }
            }

            http_response_code(200);
            echo json_encode(array("message" => "Status da reserva atualizado para " . $data->novo_status));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Erro ao atualizar status."));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Dados incompletos."));
    }
}
?>
