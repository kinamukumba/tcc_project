<?php
ob_start();
session_start();
header("Content-Type: application/json; charset=UTF-8");
include_once '../config/database.php';

// Log de depuração
$logFile = 'chat_debug.log';
function debugLog($msg) {
    global $logFile;
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $msg . PHP_EOL, FILE_APPEND);
}

if(!isset($_SESSION['user_id'])) {
    debugLog("Acesso negado: Sessão não encontrada.");
    http_response_code(401);
    echo json_encode(array("message" => "Não autenticado."));
    exit;
}

$database = new Database();
$db = $database->getConnection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$method = $_SERVER['REQUEST_METHOD'];
$id_usuario = $_SESSION['user_id'];
$role = $_SESSION['user_role'];

debugLog("Request: $method | User ID: $id_usuario | Role: $role");

if($method == 'GET') {
    $destinatario_id = isset($_GET['destinatario_id']) ? $_GET['destinatario_id'] : null;

    if(($role == 'admin' || $role == 'recepcionista') && $destinatario_id) {
        $query = "SELECT m.id_mensagem, m.conteudo, m.data_envio, m.remetente_id, u.nome as remetente_nome 
                  FROM mensagens m
                  INNER JOIN usuario u ON m.remetente_id = u.id_usuario
                  WHERE (m.remetente_id = :id && m.destinatario_id = :dest) 
                     OR (m.remetente_id = :dest && m.destinatario_id = :id)
                  ORDER BY m.data_envio ASC";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id_usuario);
        $stmt->bindParam(':dest', $destinatario_id);
    } else {
        $query = "SELECT m.id_mensagem, m.conteudo, m.data_envio, m.remetente_id, u.nome as remetente_nome 
                  FROM mensagens m
                  INNER JOIN usuario u ON m.remetente_id = u.id_usuario
                  WHERE m.remetente_id = :id OR m.destinatario_id = :id
                  ORDER BY m.data_envio ASC";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id_usuario);
    }
    
    $stmt->execute();
    $mensagens = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        $row['tipo'] = ($row['remetente_id'] == $id_usuario) ? 'sent' : 'received';
        array_push($mensagens, $row);
    }
    debugLog("GET: Encontradas " . count($mensagens) . " mensagens.");
    echo json_encode($mensagens);
} 
else if($method == 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    debugLog("POST: Recebido payload: " . json_encode($data));
    
    if(!empty($data->conteudo)) {
        $destinatario_id = isset($data->destinatario_id) ? $data->destinatario_id : null;
        
        if($role == 'utente' && !$destinatario_id) {
            $qA = "SELECT id_usuario FROM usuario WHERE tipo_usuario = 'recepcionista' LIMIT 1";
            $sA = $db->prepare($qA);
            $sA->execute();
            if($rA = $sA->fetch(PDO::FETCH_ASSOC)) {
                $destinatario_id = $rA['id_usuario'];
            } else {
                $qA = "SELECT id_usuario FROM usuario WHERE tipo_usuario = 'admin' LIMIT 1";
                $sA = $db->prepare($qA);
                $sA->execute();
                if($rA = $sA->fetch(PDO::FETCH_ASSOC)) {
                    $destinatario_id = $rA['id_usuario'];
                }
            }
        }
        
        debugLog("POST: Destinatário identificado: " . ($destinatario_id ?? 'NULO'));

        if($destinatario_id) {
            try {
                $query = "INSERT INTO mensagens (remetente_id, destinatario_id, conteudo, data_envio) 
                          VALUES (:remetente, :destinatario, :conteudo, NOW())";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':remetente', $id_usuario);
                $stmt->bindParam(':destinatario', $destinatario_id);
                $stmt->bindParam(':conteudo', $data->conteudo);
                
                if($stmt->execute()) {
                    debugLog("POST: Sucesso ao inserir ID " . $db->lastInsertId());
                    http_response_code(201);
                    echo json_encode(array("status" => "success", "message" => "Mensagem enviada."));
                }
            } catch(Exception $e) {
                debugLog("POST ERRO SQL: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(array("status" => "error", "message" => $e->getMessage()));
            }
        } else {
            debugLog("POST ERRO: Destinatário não configurado.");
            http_response_code(400);
            echo json_encode(array("message" => "Admin não encontrado."));
        }
    }
}
?>
