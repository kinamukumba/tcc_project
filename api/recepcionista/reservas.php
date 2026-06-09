<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");
include_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'recepcionista') {
    http_response_code(403);
    echo json_encode(["message" => "Acesso negado."]);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $status = isset($_GET['status']) ? trim($_GET['status']) : '';
    $date   = isset($_GET['date'])   ? trim($_GET['date'])   : '';

    $sql = "SELECT r.id_reserva, r.codigo_reserva,
                   r.data_reserva, r.data_checkin, r.data_checkout,
                   r.status_reserva AS status,
                   r.n_pessoa,
                   s.tipos_servicos AS servico,
                   s.`preço`        AS preco_noite,
                   u.nome           AS cliente_nome,
                   u.email          AS cliente_email,
                   c.telemovel      AS cliente_telefone
            FROM reserva r
            LEFT JOIN `serviço` s ON r.`id_serviço` = s.`id_serviço`
            LEFT JOIN cliente   c ON r.id_cliente    = c.id_cliente
            LEFT JOIN usuario   u ON c.id_usuario    = u.id_usuario
            WHERE 1=1";

    $params = [];

    if ($search !== '') {
        $sql .= " AND (u.nome LIKE :search OR r.codigo_reserva LIKE :search OR u.email LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }
    if ($status !== '') {
        $sql .= " AND r.status_reserva = :status";
        $params[':status'] = $status;
    }
    if ($date === 'today') {
        $sql .= " AND (DATE(r.data_checkin) = CURDATE() OR DATE(r.data_checkout) = CURDATE())";
    }

    $sql .= " ORDER BY r.id_reserva DESC";

    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        http_response_code(200);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["message" => "Erro SQL: " . $e->getMessage()]);
    }
    exit;
}

if ($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"));

    if (empty($data->id_reserva) || empty($data->status)) {
        http_response_code(400);
        echo json_encode(["message" => "Dados incompletos."]);
        exit;
    }

    // Recepcionista pode fazer TUDO (Opcao A)
    $valid = ['aprovada', 'rejeitada', 'cancelada', 'check-in', 'check-out', 'concluida'];
    if (!in_array($data->status, $valid)) {
        http_response_code(400);
        echo json_encode(["message" => "Status invalido: " . $data->status]);
        exit;
    }

    try {
        $stmt = $db->prepare("UPDATE reserva SET status_reserva = :status WHERE id_reserva = :id");
        $stmt->execute([':status' => $data->status, ':id' => $data->id_reserva]);

        // Buscar info da reserva para notificacao
        $ri = $db->prepare("SELECT id_cliente, codigo_reserva FROM reserva WHERE id_reserva = :id LIMIT 1");
        $ri->execute([':id' => $data->id_reserva]);
        $info = $ri->fetch(PDO::FETCH_ASSOC);

        if ($info && !empty($info['id_cliente'])) {
            $msgMap = [
                'aprovada'  => "A sua reserva {$info['codigo_reserva']} foi APROVADA. Aguardamos a sua chegada!",
                'rejeitada' => "A sua reserva {$info['codigo_reserva']} foi REJEITADA. Contacte-nos para mais informacoes.",
                'cancelada' => "A sua reserva {$info['codigo_reserva']} foi CANCELADA.",
                'check-in'  => "Check-in realizado com sucesso para {$info['codigo_reserva']}. Bem-vindo(a)!",
                'check-out' => "Check-out realizado para {$info['codigo_reserva']}. Obrigado pela estadia!",
            ];
            if (isset($msgMap[$data->status])) {
                try {
                    $qN = "INSERT INTO notificacao (id_cliente, mensagem, lida, data_criacao) VALUES (:c,:m,0,NOW())";
                    $sN = $db->prepare($qN);
                    $sN->execute([':c' => $info['id_cliente'], ':m' => $msgMap[$data->status]]);
                } catch (Exception $ignored) {}
            }
        }

        http_response_code(200);
        echo json_encode(["message" => "Reserva actualizada para '{$data->status}'."]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["message" => "Erro: " . $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(["message" => "Metodo nao permitido."]);
?>