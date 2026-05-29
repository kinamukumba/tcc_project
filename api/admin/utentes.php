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
    // Buscar um utente específico ou todos
    if(isset($_GET['id'])) {
        $query = "SELECT u.id_usuario, c.id_cliente, u.nome, u.email, c.sobrenome, c.telemovel, c.bi, u.status 
                  FROM usuario u 
                  JOIN cliente c ON u.id_usuario = c.id_usuario 
                  WHERE u.id_usuario = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([':id' => $_GET['id']]);
        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
    } else {
        $query = "SELECT u.id_usuario, c.id_cliente, u.nome, u.email, c.telemovel, c.bi, u.status 
                  FROM usuario u 
                  JOIN cliente c ON u.id_usuario = c.id_usuario 
                  WHERE u.tipo_usuario = 'utente'
                  ORDER BY u.id_usuario DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
} 
else if($method == 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    // Ação de Toggle Status (Suspender/Ativar)
    if(isset($data->acao) && $data->acao == 'toggle_status') {
        $novo_status = ($data->status_atual == 'ativo') ? 'suspenso' : 'ativo';
        $query = "UPDATE usuario SET status = :status WHERE id_usuario = :id";
        $stmt = $db->prepare($query);
        if($stmt->execute([':status' => $novo_status, ':id' => $data->id_usuario])) {
            echo json_encode(array("message" => "Status atualizado."));
        }
        exit;
    }

    // Criar novo utente
    if(!empty($data->nome) && !empty($data->email) && !empty($data->senha)) {
        try {
            $db->beginTransaction();
            
            $q1 = "INSERT INTO usuario (nome, email, senha, tipo_usuario) VALUES (:nome, :email, :senha, 'utente')";
            $s1 = $db->prepare($q1);
            $hash = md5($data->senha);
            $s1->execute([':nome' => $data->nome, ':email' => $data->email, ':senha' => $hash]);
            $id_u = $db->lastInsertId();
            
            $q2 = "INSERT INTO cliente (nome, sobrenome, email, bi, telemovel, senha, id_usuario) 
                   VALUES (:nome, :sobrenome, :email, :bi, :tel, :senha, :id_u)";
            $s2 = $db->prepare($q2);
            $s2->execute([
                ':nome' => $data->nome,
                ':sobrenome' => $data->sobrenome ?? '',
                ':email' => $data->email,
                ':bi' => $data->bi ?? '',
                ':tel' => $data->telemovel ?? '',
                ':senha' => $hash,
                ':id_u' => $id_u
            ]);
            
            $db->commit();
            echo json_encode(array("message" => "Utente criado com sucesso."));
        } catch(Exception $e) {
            $db->rollBack();
            http_response_code(500);
            echo json_encode(array("message" => "Erro: " . $e->getMessage()));
        }
    }
}
else if($method == 'PUT') {
    $data = json_decode(file_get_contents("php://input"));
    if(!empty($data->id_usuario)) {
        try {
            $db->beginTransaction();
            
            $q1 = "UPDATE usuario SET nome = :nome, email = :email WHERE id_usuario = :id";
            $s1 = $db->prepare($q1);
            $s1->execute([':nome' => $data->nome, ':email' => $data->email, ':id' => $data->id_usuario]);
            
            $q2 = "UPDATE cliente SET nome = :nome, sobrenome = :sobrenome, email = :email, bi = :bi, telemovel = :tel 
                   WHERE id_usuario = :id";
            $s2 = $db->prepare($q2);
            $s2->execute([
                ':nome' => $data->nome,
                ':sobrenome' => $data->sobrenome,
                ':email' => $data->email,
                ':bi' => $data->bi,
                ':tel' => $data->telemovel,
                ':id' => $data->id_usuario
            ]);
            
            $db->commit();
            echo json_encode(array("message" => "Utente atualizado com sucesso."));
        } catch(Exception $e) {
            $db->rollBack();
            http_response_code(500);
            echo json_encode(array("message" => "Erro: " . $e->getMessage()));
        }
    }
}
else if($method == 'DELETE') {
    $id = $_GET['id'];
    $query = "DELETE FROM usuario WHERE id_usuario = :id";
    $stmt = $db->prepare($query);
    if($stmt->execute([':id' => $id])) {
        echo json_encode(array("message" => "Utente removido."));
    }
}
?>
