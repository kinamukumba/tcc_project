<?php
// api/recepcionista/conversas.php
session_start();
header("Content-Type: application/json; charset=UTF-8");
include_once '../config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'recepcionista') {
    http_response_code(403);
    echo json_encode(array("message" => "Acesso negado."));
    exit;
}

$database = new Database();
$db = $database->getConnection();

$id_recep = $_SESSION['user_id'];

// Buscar lista de utentes que trocaram mensagens com a recepção
$query = "SELECT DISTINCT u.id_usuario, u.nome, u.email 
          FROM usuario u
          INNER JOIN mensagens m ON (u.id_usuario = m.remetente_id OR u.id_usuario = m.destinatario_id)
          WHERE u.tipo_usuario = 'utente' AND (m.remetente_id = :id_recep OR m.destinatario_id = :id_recep)
          ORDER BY (SELECT MAX(data_envio) FROM mensagens WHERE remetente_id = u.id_usuario OR destinatario_id = u.id_usuario) DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':id_recep', $id_recep);
$stmt->execute();

$conversas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Adicionar última mensagem de cada conversa
foreach($conversas as &$c) {
    $qMsg = "SELECT conteudo, data_envio FROM mensagens 
             WHERE (remetente_id = :u AND destinatario_id = :r) 
                OR (remetente_id = :r AND destinatario_id = :u)
             ORDER BY data_envio DESC LIMIT 1";
    $sMsg = $db->prepare($qMsg);
    $sMsg->bindParam(':u', $c['id_usuario']);
    $sMsg->bindParam(':r', $id_recep);
    $sMsg->execute();
    $last = $sMsg->fetch(PDO::FETCH_ASSOC);
    $c['ultima_mensagem'] = $last ? $last['conteudo'] : '';
    $c['data_ultima'] = $last ? $last['data_envio'] : '';
}

http_response_code(200);
echo json_encode($conversas);
?>
