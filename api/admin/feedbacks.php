<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");
include_once '../config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    http_response_code(403);
    echo json_encode(array("message" => "Acesso negado."));
    exit;
}

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if($method == 'GET') {
    // Listar todos os feedbacks (avaliações)
    $query = "SELECT a.id_avaliação, u.nome as utente, a.comentario, a.data_avaliação, a.nota
              FROM avaliação a
              JOIN cliente c ON a.id_cliente = c.id_cliente
              JOIN usuario u ON c.id_usuario = u.id_usuario
              ORDER BY a.data_avaliação DESC";
              
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    http_response_code(200);
    echo json_encode($feedbacks);
} 
else if($method == 'DELETE') {
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    if($id) {
        $query = "DELETE FROM avaliação WHERE id_avaliação = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        if($stmt->execute()) {
            echo json_encode(array("message" => "Feedback removido com sucesso."));
        }
    }
}
?>
