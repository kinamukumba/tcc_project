<?php
// api/reserva_consulta.php
header("Content-Type: application/json; charset=UTF-8");
include_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    // Buscar detalhes da reserva
    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->email) && !empty($data->codigo_reserva)) {
        $query = "SELECT r.id_reserva, r.data_checkin, r.data_checkout, r.status_reserva, r.n_pessoa, r.data_reserva,
                         s.tipos_servicos as servico, s.preço, c.nome, c.email, c.telemovel
                  FROM reserva r
                  LEFT JOIN serviço s ON r.id_serviço = s.id_serviço
                  LEFT JOIN cliente c ON r.id_cliente = c.id_cliente
                  WHERE c.email = :email AND r.codigo_reserva = :codigo_reserva LIMIT 1";
                  
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $data->email);
        $stmt->bindParam(':codigo_reserva', $data->codigo_reserva);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Calcular valor total
            $date1 = new DateTime($row['data_checkin']);
            $date2 = new DateTime($row['data_checkout']);
            $diff = $date1->diff($date2)->days;
            $dias = $diff > 0 ? $diff : 1;
            
            $precoTotal = $row['preço'] * $dias;
            $row['preco_total_formatado'] = number_format($precoTotal, 2, ',', '.') . ' KZ';
            $row['preco_total'] = $precoTotal;
            $row['dias'] = $dias;
            
            http_response_code(200);
            echo json_encode($row);
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "Reserva não encontrada. Verifique o e-mail e o código informados."));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Dados incompletos. Preencha todos os campos."));
    }
} 
else if ($method == 'PUT') {
    // Cancelar a reserva
    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->id_reserva) && !empty($data->email) && !empty($data->codigo_reserva)) {
        // Verificar primeiro se pertence ao e-mail correto e se está pendente
        $queryCheck = "SELECT r.status_reserva 
                       FROM reserva r 
                       LEFT JOIN cliente c ON r.id_cliente = c.id_cliente
                       WHERE r.id_reserva = :id_reserva AND c.email = :email AND r.codigo_reserva = :codigo_reserva LIMIT 1";
        $stmtCheck = $db->prepare($queryCheck);
        $stmtCheck->execute([
            ':id_reserva' => $data->id_reserva,
            ':email' => $data->email,
            ':codigo_reserva' => $data->codigo_reserva
        ]);
        
        if ($stmtCheck->rowCount() > 0) {
            $res = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            if ($res['status_reserva'] == 'pendente') {
                $queryUpdate = "UPDATE reserva SET status_reserva = 'cancelada' WHERE id_reserva = :id_reserva";
                $stmtUpdate = $db->prepare($queryUpdate);
                if ($stmtUpdate->execute([':id_reserva' => $data->id_reserva])) {
                    http_response_code(200);
                    echo json_encode(array("message" => "Reserva cancelada com sucesso!"));
                } else {
                    http_response_code(500);
                    echo json_encode(array("message" => "Erro interno ao atualizar reserva."));
                }
            } else {
                http_response_code(400);
                echo json_encode(array("message" => "Esta reserva já foi processada (aprovada/check-in/concluída) e não pode ser cancelada diretamente. Por favor, contacte a recepção."));
            }
        } else {
            http_response_code(403);
            echo json_encode(array("message" => "Acesso não autorizado ou dados inválidos."));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Dados incompletos para cancelamento."));
    }
} else {
    http_response_code(405);
    echo json_encode(array("message" => "Método não permitido."));
}
?>
