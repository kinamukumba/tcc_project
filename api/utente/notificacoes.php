<?php
// api/utente/notificacoes.php
session_start();
header("Content-Type: application/json; charset=UTF-8");
include_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'utente') {
    http_response_code(403);
    echo json_encode(array("message" => "Acesso negado."));
    exit;
}

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$id_cliente = $_SESSION['role_id'];

if ($method == 'GET') {
    // Listar notificações do cliente
    try {
        $query = "SELECT id_notificacao, mensagem, lida, data_criacao 
                  FROM notificacao 
                  WHERE id_cliente = :id_cliente 
                  ORDER BY data_criacao DESC";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id_cliente', $id_cliente);
        $stmt->execute();
        
        $notificacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode($notificacoes);
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Erro ao carregar notificações."));
    }
} 
else if ($method == 'POST') {
    // Marcar como lida
    $data = json_decode(file_get_contents("php://input"));
    if (!empty($data->id_notificacao)) {
        $query = "UPDATE notificacao SET lida = 1 WHERE id_notificacao = :id AND id_cliente = :id_cliente";
        $stmt = $db->prepare($query);
        if ($stmt->execute([':id' => $data->id_notificacao, ':id_cliente' => $id_cliente])) {
            http_response_code(200);
            echo json_encode(array("message" => "Notificação marcada como lida."));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Erro ao atualizar."));
        }
    } else {
        // Marcar todas como lidas
        $query = "UPDATE notificacao SET lida = 1 WHERE id_cliente = :id_cliente";
        $stmt = $db->prepare($query);
        if ($stmt->execute([':id_cliente' => $id_cliente])) {
            http_response_code(200);
            echo json_encode(array("message" => "Todas as notificações marcadas como lidas."));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Erro ao atualizar."));
        }
    }
} else {
    http_response_code(405);
    echo json_encode(array("message" => "Método não permitido."));
}
?>
