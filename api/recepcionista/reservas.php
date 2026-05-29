<?php
// api/recepcionista/reservas.php
session_start();
header("Content-Type: application/json; charset=UTF-8");
include_once '../config/database.php';

// Validar se o usuário está logado e é recepcionista
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'recepcionista') {
    http_response_code(403);
    echo json_encode(array("message" => "Acesso negado."));
    exit;
}

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'GET') {
    // Listar reservas com filtros opcionais
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $date = isset($_GET['date']) ? $_GET['date'] : ''; // Ex: 'today'
    
    $query = "SELECT r.id_reserva, r.data_checkin, r.data_checkout, r.status_reserva, r.n_pessoa, r.codigo_reserva, r.data_reserva,
                     s.tipos_servicos as servico, s.preço, c.nome as cliente_nome, c.email as cliente_email, c.telemovel as cliente_telefone
              FROM reserva r
              LEFT JOIN serviço s ON r.id_serviço = s.id_serviço
              LEFT JOIN cliente c ON r.id_cliente = c.id_cliente
              WHERE 1=1";
              
    $params = [];
    
    if (!empty($search)) {
        $query .= " AND (c.nome LIKE :search OR r.codigo_reserva LIKE :search OR c.email LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }
    
    if (!empty($status)) {
        $query .= " AND r.status_reserva = :status";
        $params[':status'] = $status;
    }
    
    if ($date == 'today') {
        $query .= " AND (r.data_checkin = CURRENT_DATE() OR r.data_checkout = CURRENT_DATE())";
    }
    
    $query .= " ORDER BY r.data_reserva DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    
    $reservas = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Calcular valor total
        $date1 = new DateTime($row['data_checkin']);
        $date2 = new DateTime($row['data_checkout']);
        $diff = $date1->diff($date2)->days;
        $dias = $diff > 0 ? $diff : 1;
        $precoTotal = $row['preço'] * $dias;
        
        $row['preco_total'] = $precoTotal;
        $row['preco_total_formatado'] = number_format($precoTotal, 2, ',', '.') . ' KZ';
        $row['dias'] = $dias;
        $reservas[] = $row;
    }
    
    http_response_code(200);
    echo json_encode($reservas);
} 
else if ($method == 'POST') {
    // Atualizar status da reserva (Check-in, Check-out, Cancelar)
    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->id_reserva) && !empty($data->status)) {
        $validStatuses = ['pendente', 'aprovada', 'rejeitada', 'concluida', 'checkin', 'checkout', 'cancelada'];
        if (!in_array($data->status, $validStatuses)) {
            http_response_code(400);
            echo json_encode(array("message" => "Status inválido."));
            exit;
        }
        
        $query = "UPDATE reserva SET status_reserva = :status WHERE id_reserva = :id_reserva";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([':status' => $data->status, ':id_reserva' => $data->id_reserva])) {
            
            // Criar notificação para o cliente do status da reserva
            $qInfo = "SELECT id_cliente, codigo_reserva FROM reserva WHERE id_reserva = :id LIMIT 1";
            $sInfo = $db->prepare($qInfo);
            $sInfo->execute([':id' => $data->id_reserva]);
            $resInfo = $sInfo->fetch(PDO::FETCH_ASSOC);
            
            if ($resInfo && !empty($resInfo['id_cliente'])) {
                $msg = "";
                switch ($data->status) {
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
            echo json_encode(array("message" => "Status da reserva atualizado com sucesso para: " . $data->status));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Não foi possível atualizar o status da reserva."));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Dados incompletos."));
    }
} else {
    http_response_code(405);
    echo json_encode(array("message" => "Método não permitido."));
}
?>
