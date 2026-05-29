<?php
// api/admin/servicos.php
session_start();
header("Content-Type: application/json; charset=UTF-8");
include_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    http_response_code(403);
    echo json_encode(array("message" => "Acesso negado. Apenas administradores podem gerir serviços."));
    exit;
}

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'GET') {
    // Listar todos os serviços com status
    $query = "SELECT id_serviço, tipos_servicos, descrição, preço, status FROM serviço ORDER BY id_serviço ASC";
    $stmt = $db->query($query);
    $servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    http_response_code(200);
    echo json_encode($servicos);
} 
else if ($method == 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->tipos_servicos) && isset($data->preco) && !empty($data->descricao)) {
        $status = (!empty($data->status) && $data->status == 'ocupado') ? 'ocupado' : 'desocupado';
        
        $query = "INSERT INTO serviço (tipos_servicos, descrição, preço, status) VALUES (:tipo, :descricao, :preco, :status)";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([
            ':tipo' => $data->tipos_servicos,
            ':descricao' => $data->descricao,
            ':preco' => $data->preco,
            ':status' => $status
        ])) {
            http_response_code(201);
            echo json_encode(array("message" => "Serviço cadastrado com sucesso!"));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Erro ao cadastrar serviço."));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Dados incompletos."));
    }
} 
else if ($method == 'PUT') {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->id_servico) && !empty($data->tipos_servicos) && isset($data->preco) && !empty($data->descricao) && !empty($data->status)) {
        $query = "UPDATE serviço SET tipos_servicos = :tipo, preço = :preco, descrição = :descricao, status = :status WHERE id_serviço = :id";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([
            ':tipo' => $data->tipos_servicos,
            ':preco' => $data->preco,
            ':descricao' => $data->descricao,
            ':status' => $data->status,
            ':id' => $data->id_servico
        ])) {
            http_response_code(200);
            echo json_encode(array("message" => "Serviço atualizado com sucesso!"));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Erro ao atualizar serviço."));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Dados incompletos."));
    }
} 
else if ($method == 'DELETE') {
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    
    if ($id) {
        $query = "DELETE FROM serviço WHERE id_serviço = :id";
        $stmt = $db->prepare($query);
        if ($stmt->execute([':id' => $id])) {
            http_response_code(200);
            echo json_encode(array("message" => "Serviço excluído com sucesso."));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Erro ao excluir serviço."));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "ID não fornecido."));
    }
} else {
    http_response_code(405);
    echo json_encode(array("message" => "Método não permitido."));
}
?>
