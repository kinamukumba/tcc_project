<?php
// api/recepcionista/conversas.php
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
$id_recep = $_SESSION['user_id'];

// Buscar utentes que trocaram mensagens com esta recepcionista
$sql = "SELECT DISTINCT
            u.id_usuario,
            u.nome,
            u.email,
            (SELECT conteudo FROM mensagens
             WHERE (remetente_id = u.id_usuario AND destinatario_id = :id1)
                OR (remetente_id = :id2 AND destinatario_id = u.id_usuario)
             ORDER BY data_envio DESC LIMIT 1) AS ultima_mensagem,
            (SELECT data_envio FROM mensagens
             WHERE (remetente_id = u.id_usuario AND destinatario_id = :id3)
                OR (remetente_id = :id4 AND destinatario_id = u.id_usuario)
             ORDER BY data_envio DESC LIMIT 1) AS data_ultima,
            (SELECT COUNT(*) FROM mensagens
             WHERE remetente_id = u.id_usuario
               AND destinatario_id = :id5) AS nao_lidas
        FROM usuario u
        INNER JOIN mensagens m
            ON (u.id_usuario = m.remetente_id OR u.id_usuario = m.destinatario_id)
        WHERE u.tipo_usuario = 'utente'
          AND (m.remetente_id = :id6 OR m.destinatario_id = :id7)
        ORDER BY data_ultima DESC";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':id1' => $id_recep, ':id2' => $id_recep,
        ':id3' => $id_recep, ':id4' => $id_recep,
        ':id5' => $id_recep, ':id6' => $id_recep,
        ':id7' => $id_recep
    ]);
    http_response_code(200);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Erro SQL: " . $e->getMessage()]);
}
?>