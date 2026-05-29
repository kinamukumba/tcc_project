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
        
        // Verificar status do serviço
        $qServico = "SELECT status FROM serviço WHERE id_serviço = :id_servico LIMIT 1";
        $sServico = $db->prepare($qServico);
        $sServico->execute([':id_servico' => $data->id_servico]);
        if ($sServico->rowCount() == 0) {
            http_response_code(400);
            echo json_encode(array("message" => "Serviço/Quarto inválido."));
            exit;
        }
        $servico = $sServico->fetch(PDO::FETCH_ASSOC);
        
        // Verificação 1: Status manual de ocupado
        if ($servico['status'] == 'ocupado') {
            http_response_code(400);
            echo json_encode(array("message" => "Este serviço/quarto está marcado como ocupado ou indisponível."));
            exit;
        }
        
        // Verificação 2: Sobreposição de datas com reservas ativas
        $qOverLap = "SELECT id_reserva FROM reserva 
                     WHERE id_serviço = :id_servico 
                       AND status_reserva IN ('aprovada', 'checkin', 'pendente')
                       AND NOT (data_checkout <= :checkin OR data_checkin >= :checkout)";
        $sOverLap = $db->prepare($qOverLap);
        $sOverLap->execute([
            ':id_servico' => $data->id_servico,
            ':checkin' => $data->data_checkin,
            ':checkout' => $data->data_checkout
        ]);
        if ($sOverLap->rowCount() > 0) {
            http_response_code(400);
            echo json_encode(array("message" => "Este serviço/quarto já está reservado/ocupado no período selecionado."));
            exit;
        }
        
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
else if($method == 'PUT') {
    $data = json_decode(file_get_contents("php://input"));
    
    if(!empty($data->id_reserva) && !empty($data->data_checkin) && !empty($data->data_checkout)) {
        // Verificar se a reserva pertence a esse cliente e se está pendente
        $queryCheck = "SELECT status_reserva, id_serviço FROM reserva WHERE id_reserva = :id AND id_cliente = :id_cliente LIMIT 1";
        $stmtCheck = $db->prepare($queryCheck);
        $stmtCheck->execute([':id' => $data->id_reserva, ':id_cliente' => $id_cliente]);
        
        if($stmtCheck->rowCount() > 0) {
            $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            if ($row['status_reserva'] == 'pendente') {
                $id_servico = $row['id_serviço'];
                
                // Verificar sobreposição de datas com outras reservas ativas
                $qOverLap = "SELECT id_reserva FROM reserva 
                             WHERE id_serviço = :id_servico 
                               AND id_reserva != :id_reserva
                               AND status_reserva IN ('aprovada', 'checkin', 'pendente')
                               AND NOT (data_checkout <= :checkin OR data_checkin >= :checkout)";
                $sOverLap = $db->prepare($qOverLap);
                $sOverLap->execute([
                    ':id_servico' => $id_servico,
                    ':id_reserva' => $data->id_reserva,
                    ':checkin' => $data->data_checkin,
                    ':checkout' => $data->data_checkout
                ]);
                if ($sOverLap->rowCount() > 0) {
                    http_response_code(400);
                    echo json_encode(array("message" => "Este serviço/quarto já está reservado/ocupado no período selecionado."));
                    exit;
                }

                $queryUpdate = "UPDATE reserva SET data_checkin = :checkin, data_checkout = :checkout WHERE id_reserva = :id";
                $stmtUpdate = $db->prepare($queryUpdate);
                if($stmtUpdate->execute([
                    ':checkin' => $data->data_checkin,
                    ':checkout' => $data->data_checkout,
                    ':id' => $data->id_reserva
                ])) {
                    // Enviar notificação de alteração de datas
                    $msgText = "As datas da sua reserva #" . $data->id_reserva . " foram alteradas com sucesso para: " . date("d/m/Y", strtotime($data->data_checkin)) . " a " . date("d/m/Y", strtotime($data->data_checkout));
                    $qNotify = "INSERT INTO notificacao (id_cliente, mensagem, lida, data_criacao) VALUES (:id_cliente, :msg, 0, NOW())";
                    $sNotify = $db->prepare($qNotify);
                    $sNotify->execute([':id_cliente' => $id_cliente, ':msg' => $msgText]);
                    
                    http_response_code(200);
                    echo json_encode(array("message" => "Datas da reserva alteradas com sucesso!"));
                } else {
                    http_response_code(503);
                    echo json_encode(array("message" => "Erro ao atualizar datas."));
                }
            } else {
                http_response_code(400);
                echo json_encode(array("message" => "Esta reserva já foi processada e as datas não podem ser alteradas."));
            }
        } else {
            http_response_code(403);
            echo json_encode(array("message" => "Acesso não autorizado ou reserva inválida."));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Dados incompletos."));
    }
}
else if($method == 'DELETE') {
    $id_reserva = isset($_GET['id']) ? $_GET['id'] : null;
    
    if($id_reserva) {
        $query = "DELETE FROM reserva WHERE id_reserva = :id AND id_cliente = :id_cliente AND status_reserva = 'pendente'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id_reserva);
        $stmt->bindParam(':id_cliente', $id_cliente);
        
        if($stmt->execute()) {
            if($stmt->rowCount() > 0) {
                // Enviar notificação de cancelamento
                $msgText = "A sua reserva #" . $id_reserva . " foi cancelada com sucesso.";
                $qNotify = "INSERT INTO notificacao (id_cliente, mensagem, lida, data_criacao) VALUES (:id_cliente, :msg, 0, NOW())";
                $sNotify = $db->prepare($qNotify);
                $sNotify->execute([':id_cliente' => $id_cliente, ':msg' => $msgText]);
                
                http_response_code(200);
                echo json_encode(array("message" => "Reserva cancelada com sucesso."));
            } else {
                http_response_code(403);
                echo json_encode(array("message" => "Não é possível cancelar esta reserva (já aprovada ou não pertence a você)."));
            }
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Erro ao cancelar reserva."));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "ID da reserva não fornecido."));
    }
}
?>
