<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");
include_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
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

    $sql .= " ORDER BY r.id_reserva DESC";

    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calcular dias e preco total
        foreach ($rows as &$row) {
            if (!empty($row['data_checkin']) && !empty($row['data_checkout'])) {
                $d1 = new DateTime($row['data_checkin']);
                $d2 = new DateTime($row['data_checkout']);
                $dias = max(1, $d1->diff($d2)->days);
                $row['dias'] = $dias;
                $row['preco_total'] = ($row['preco_noite'] ?? 0) * $dias;
            }
        }
        
        http_response_code(200);
        echo json_encode($rows);
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

    $valid = ['pendente','aprovada','confirmada','cancelada','check-in','check-out','concluida'];
    if (!in_array($data->status, $valid)) {
        http_response_code(400);
        echo json_encode(["message" => "Status invalido."]);
        exit;
    }

    try {
        $stmt = $db->prepare("UPDATE reserva SET status_reserva = :status WHERE id_reserva = :id");
        $stmt->execute([':status' => $data->status, ':id' => $data->id_reserva]);

        // Notificacao
        $qI = "SELECT id_cliente, codigo_reserva FROM reserva WHERE id_reserva = :id LIMIT 1";
        $sI = $db->prepare($qI);
        $sI->execute([':id' => $data->id_reserva]);
        $ri = $sI->fetch(PDO::FETCH_ASSOC);

        if ($ri && !empty($ri['id_cliente'])) {
            $msgMap = [
                'aprovada'  => "A sua reserva {$ri['codigo_reserva']} foi APROVADA.",
                'cancelada' => "A sua reserva {$ri['codigo_reserva']} foi cancelada.",
                'check-in'  => "Check-in realizado para {$ri['codigo_reserva']}. Bem-vindo!",
                'check-out' => "Check-out realizado para {$ri['codigo_reserva']}. Obrigado!",
            ];
            if (isset($msgMap[$data->status])) {
                try {
                    $qN = "INSERT INTO notificacao (id_cliente, mensagem, lida, data_criacao) VALUES (:c,:m,0,NOW())";
                    $sN = $db->prepare($qN);
                    $sN->execute([':c' => $ri['id_cliente'], ':m' => $msgMap[$data->status]]);
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

if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if (!$id) { http_response_code(400); echo json_encode(["message" => "ID em falta."]); exit; }
    try {
        $stmt = $db->prepare("DELETE FROM reserva WHERE id_reserva = :id");
        $stmt->execute([':id' => $id]);
        http_response_code(200);
        echo json_encode(["message" => "Reserva eliminada."]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["message" => "Erro: " . $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(["message" => "Metodo nao permitido."]);
?>