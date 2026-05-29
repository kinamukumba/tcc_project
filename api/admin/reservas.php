<?php
// api/admin/reservas.php
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

// ─── GET ──────────────────────────────────────────────────────────────────────
if ($method == 'GET') {
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $id     = isset($_GET['id'])     ? (int)$_GET['id'] : null;

    $where = [];
    $params = [];

    if ($id) {
        $where[] = "r.id_reserva = :id";
        $params[':id'] = $id;
    }
    if ($status && $status !== 'Todas') {
        $where[] = "r.status = :status";
        $params[':status'] = $status;
    }

    $sql = "SELECT 
                r.id_reserva,
                r.codigo_reserva,
                r.data_checkin,
                r.data_checkout,
                r.status,
                r.n_pessoa,
                r.observacoes,
                r.valor_total,
                u.nome  AS cliente_nome,
                u.email AS cliente_email,
                s.tipos_servicos AS servico
            FROM reserva r
            JOIN cliente c  ON r.id_cliente = c.id_cliente
            JOIN usuario u  ON c.id_usuario = u.id_usuario
            JOIN `serviço` s ON r.`id_serviço` = s.`id_serviço`"
        . (count($where) ? " WHERE " . implode(' AND ', $where) : "")
        . " ORDER BY r.id_reserva DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode($id ? ($rows[0] ?? null) : $rows);
}

// ─── PUT (trocar status) ──────────────────────────────────────────────────────
else if ($method == 'PUT') {
    $data = json_decode(file_get_contents("php://input"));

    if (empty($data->id_reserva) || empty($data->status)) {
        http_response_code(400);
        echo json_encode(["message" => "Dados incompletos (id_reserva + status)."]);
        exit;
    }

    $validStatuses = ['pendente','aprovada','confirmada','cancelada','check-in','check-out','concluida'];
    if (!in_array($data->status, $validStatuses)) {
        http_response_code(400);
        echo json_encode(["message" => "Status inválido."]);
        exit;
    }

    try {
        $stmt = $db->prepare("UPDATE reserva SET status = :status WHERE id_reserva = :id");
        $stmt->execute([':status' => $data->status, ':id' => $data->id_reserva]);

        // Notificação ao cliente
        $qInfo = "SELECT id_cliente, codigo_reserva FROM reserva WHERE id_reserva = :id LIMIT 1";
        $sInfo = $db->prepare($qInfo);
        $sInfo->execute([':id' => $data->id_reserva]);
        $resInfo = $sInfo->fetch(PDO::FETCH_ASSOC);

        if ($resInfo && !empty($resInfo['id_cliente'])) {
            $msgs = [
                'aprovada'   => "A sua reserva {$resInfo['codigo_reserva']} foi APROVADA.",
                'cancelada'  => "A sua reserva {$resInfo['codigo_reserva']} foi CANCELADA.",
                'confirmada' => "A sua reserva {$resInfo['codigo_reserva']} foi CONFIRMADA.",
                'check-in'   => "Check-in realizado com sucesso para {$resInfo['codigo_reserva']}. Bem-vindo!",
                'check-out'  => "Check-out realizado com sucesso para {$resInfo['codigo_reserva']}. Obrigado!",
                'concluida'  => "A sua reserva {$resInfo['codigo_reserva']} foi CONCLUÍDA.",
            ];
            $msg = $msgs[$data->status] ?? null;
            if ($msg) {
                $qN = "INSERT INTO notificacao (id_cliente, mensagem, lida, data_criacao) VALUES (:id_cliente, :msg, 0, NOW())";
                $sN = $db->prepare($qN);
                $sN->execute([':id_cliente' => $resInfo['id_cliente'], ':msg' => $msg]);
            }
        }

        http_response_code(200);
        echo json_encode(["message" => "Reserva actualizada para '{$data->status}' com sucesso."]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["message" => "Erro: " . $e->getMessage()]);
    }
}

// ─── POST (criar reserva manual pelo admin) ───────────────────────────────────
else if ($method == 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    // Suporte ao formato antigo: novo_status
    if (!empty($data->id_reserva) && !empty($data->novo_status)) {
        $data->status = $data->novo_status;
        // Redirecionar para lógica PUT
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $stmt = $db->prepare("UPDATE reserva SET status = :status WHERE id_reserva = :id");
        if ($stmt->execute([':status' => $data->novo_status, ':id' => $data->id_reserva])) {
            http_response_code(200);
            echo json_encode(["message" => "Status actualizado para '{$data->novo_status}'."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Erro ao actualizar."]);
        }
        exit;
    }
    http_response_code(400);
    echo json_encode(["message" => "Acção não suportada via POST. Use PUT para actualizar status."]);
}

// ─── DELETE ───────────────────────────────────────────────────────────────────
else if ($method == 'DELETE') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
    if (!$id) { http_response_code(400); echo json_encode(["message" => "ID não fornecido."]); exit; }
    $stmt = $db->prepare("DELETE FROM reserva WHERE id_reserva = :id");
    if ($stmt->execute([':id' => $id])) {
        http_response_code(200);
        echo json_encode(["message" => "Reserva eliminada com sucesso."]);
    } else {
        http_response_code(500);
        echo json_encode(["message" => "Erro ao eliminar reserva."]);
    }
}

else {
    http_response_code(405);
    echo json_encode(["message" => "Método não permitido."]);
}
?>
