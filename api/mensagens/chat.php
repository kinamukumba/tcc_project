<?php
// api/mensagens/chat.php
session_start();
header("Content-Type: application/json; charset=UTF-8");
include_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["message" => "Nao autenticado."]);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$method     = $_SERVER['REQUEST_METHOD'];
$id_usuario = $_SESSION['user_id'];
$role       = $_SESSION['user_role'];

// ── GET: buscar mensagens da conversa ─────────────────────────────────────────
if ($method === 'GET') {
    $destinatario_id = isset($_GET['destinatario_id']) ? (int)$_GET['destinatario_id'] : null;

    if (($role === 'recepcionista' || $role === 'admin') && $destinatario_id) {
        // Admin ou recepcionista buscam conversa com utilizador especifico
        $sql = "SELECT m.id_mensagem, m.conteudo, m.data_envio,
                       m.remetente_id, u.nome AS remetente_nome
                FROM mensagens m
                INNER JOIN usuario u ON m.remetente_id = u.id_usuario
                WHERE (m.remetente_id = :me AND m.destinatario_id = :dest)
                   OR (m.remetente_id = :dest2 AND m.destinatario_id = :me2)
                ORDER BY m.data_envio ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute([':me' => $id_usuario, ':dest' => $destinatario_id, ':dest2' => $destinatario_id, ':me2' => $id_usuario]);
    } elseif ($role === 'utente') {
        // Utente ve todas as suas mensagens
        $sql = "SELECT m.id_mensagem, m.conteudo, m.data_envio,
                       m.remetente_id, u.nome AS remetente_nome
                FROM mensagens m
                INNER JOIN usuario u ON m.remetente_id = u.id_usuario
                WHERE m.remetente_id = :me OR m.destinatario_id = :me2
                ORDER BY m.data_envio ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute([':me' => $id_usuario, ':me2' => $id_usuario]);
    } else {
        http_response_code(400);
        echo json_encode(["message" => "destinatario_id obrigatorio."]);
        exit;
    }

    $msgs = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['tipo'] = ($row['remetente_id'] == $id_usuario) ? 'sent' : 'received';
        $msgs[] = $row;
    }
    http_response_code(200);
    echo json_encode($msgs);
    exit;
}

// ── POST: enviar mensagem ──────────────────────────────────────────────────────
if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if (empty($data->conteudo)) {
        http_response_code(400);
        echo json_encode(["message" => "Conteudo vazio."]);
        exit;
    }

    $destinatario_id = isset($data->destinatario_id) ? (int)$data->destinatario_id : null;

    // Se utente envia sem especificar destinatario -> encontrar recepcionista disponivel
    if ($role === 'utente' && !$destinatario_id) {
        $qR = "SELECT id_usuario FROM usuario WHERE tipo_usuario = 'recepcionista' LIMIT 1";
        $sR = $db->prepare($qR);
        $sR->execute();
        $rR = $sR->fetch(PDO::FETCH_ASSOC);
        if ($rR) {
            $destinatario_id = $rR['id_usuario'];
        } else {
            // Fallback para admin
            $qA = "SELECT id_usuario FROM usuario WHERE tipo_usuario = 'admin' LIMIT 1";
            $sA = $db->prepare($qA);
            $sA->execute();
            $rA = $sA->fetch(PDO::FETCH_ASSOC);
            if ($rA) $destinatario_id = $rA['id_usuario'];
        }
    }

    if (!$destinatario_id) {
        http_response_code(400);
        echo json_encode(["message" => "Nao foi possivel encontrar um destinatario."]);
        exit;
    }

    try {
        $sql = "INSERT INTO mensagens (remetente_id, destinatario_id, conteudo, data_envio)
                VALUES (:rem, :dest, :conteudo, NOW())";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':rem'      => $id_usuario,
            ':dest'     => $destinatario_id,
            ':conteudo' => trim($data->conteudo)
        ]);
        http_response_code(201);
        echo json_encode(["status" => "success", "message" => "Mensagem enviada."]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["message" => "Erro ao enviar: " . $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(["message" => "Metodo nao permitido."]);
?>