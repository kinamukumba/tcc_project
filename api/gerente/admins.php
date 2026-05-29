<?php
// api/gerente/admins.php
session_start();
header("Content-Type: application/json; charset=UTF-8");
include_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'gerente') {
    http_response_code(403);
    echo json_encode(array("message" => "Acesso negado. Apenas o gerente pode gerir administradores."));
    exit;
}

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'GET') {
    // Listar apenas administradores
    $query = "SELECT id_usuario, nome, email, telefone, tipo_usuario FROM usuario WHERE tipo_usuario = 'admin' ORDER BY nome ASC";
    $stmt = $db->query($query);
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode($admins);
} 
else if ($method == 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->nome) && !empty($data->email) && !empty($data->telefone) && !empty($data->senha)) {
        try {
            $db->beginTransaction();
            
            // Verificar email duplicado
            $qCheck = "SELECT id_usuario FROM usuario WHERE email = :email LIMIT 1";
            $sCheck = $db->prepare($qCheck);
            $sCheck->execute([':email' => $data->email]);
            if ($sCheck->rowCount() > 0) {
                throw new Exception("E-mail já está em uso por outro utilizador.");
            }
            
            // 1. Inserir na tabela geral usuario
            $qUser = "INSERT INTO usuario (nome, email, telefone, senha, tipo_usuario) 
                      VALUES (:nome, :email, :tel, MD5(:senha), 'admin')";
            $sUser = $db->prepare($qUser);
            $sUser->execute([
                ':nome' => $data->nome,
                ':email' => $data->email,
                ':tel' => $data->telefone,
                ':senha' => $data->senha
            ]);
            $id_usuario = $db->lastInsertId();
            
            // 2. Inserir na tabela administrador
            $qSub = "INSERT INTO administrador (nome, email, senha, nivel_acesso, id_usuario) 
                     VALUES (:nome, :email, MD5(:senha), 'total', :id_usuario)";
            $sSub = $db->prepare($qSub);
            $sSub->execute([
                ':nome' => $data->nome,
                ':email' => $data->email,
                ':senha' => $data->senha,
                ':id_usuario' => $id_usuario
            ]);
            
            $db->commit();
            http_response_code(201);
            echo json_encode(array("message" => "Administrador cadastrado com sucesso!"));
        } catch (Exception $e) {
            $db->rollBack();
            http_response_code(500);
            echo json_encode(array("message" => "Erro ao cadastrar administrador: " . $e->getMessage()));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Dados incompletos."));
    }
} 
else if ($method == 'PUT') {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->id_usuario) && !empty($data->nome) && !empty($data->email) && !empty($data->telefone)) {
        try {
            // Verificar se o ID é de fato um admin
            $qCheck = "SELECT tipo_usuario FROM usuario WHERE id_usuario = :id LIMIT 1";
            $sCheck = $db->prepare($qCheck);
            $sCheck->execute([':id' => $data->id_usuario]);
            $userRow = $sCheck->fetch(PDO::FETCH_ASSOC);
            if (!$userRow || $userRow['tipo_usuario'] != 'admin') {
                http_response_code(403);
                echo json_encode(array("message" => "Utilizador inválido. Apenas administradores podem ser geridos aqui."));
                exit;
            }

            $db->beginTransaction();
            
            // Atualizar na tabela geral
            if (!empty($data->senha)) {
                $qUser = "UPDATE usuario SET nome = :nome, email = :email, telefone = :tel, senha = MD5(:senha) WHERE id_usuario = :id";
                $params = [
                    ':nome' => $data->nome,
                    ':email' => $data->email,
                    ':tel' => $data->telefone,
                    ':senha' => $data->senha,
                    ':id' => $data->id_usuario
                ];
            } else {
                $qUser = "UPDATE usuario SET nome = :nome, email = :email, telefone = :tel WHERE id_usuario = :id";
                $params = [
                    ':nome' => $data->nome,
                    ':email' => $data->email,
                    ':tel' => $data->telefone,
                    ':id' => $data->id_usuario
                ];
            }
            $sUser = $db->prepare($qUser);
            $sUser->execute($params);
            
            // Atualizar na tabela administrador
            $qSub = !empty($data->senha) 
                ? "UPDATE administrador SET nome = :nome, email = :email, senha = MD5(:senha) WHERE id_usuario = :id" 
                : "UPDATE administrador SET nome = :nome, email = :email WHERE id_usuario = :id";
            $sSub = $db->prepare($qSub);
            $subParams = [':nome' => $data->nome, ':email' => $data->email, ':id' => $data->id_usuario];
            if (!empty($data->senha)) $subParams[':senha'] = $data->senha;
            $sSub->execute($subParams);
            
            $db->commit();
            http_response_code(200);
            echo json_encode(array("message" => "Administrador atualizado com sucesso!"));
        } catch (Exception $e) {
            $db->rollBack();
            http_response_code(500);
            echo json_encode(array("message" => "Erro ao atualizar administrador: " . $e->getMessage()));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Dados incompletos."));
    }
} 
else if ($method == 'DELETE') {
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    
    if ($id) {
        // Verificar se é de fato um administrador
        $qCheck = "SELECT tipo_usuario FROM usuario WHERE id_usuario = :id LIMIT 1";
        $sCheck = $db->prepare($qCheck);
        $sCheck->execute([':id' => $id]);
        $userRow = $sCheck->fetch(PDO::FETCH_ASSOC);
        if (!$userRow || $userRow['tipo_usuario'] != 'admin') {
            http_response_code(403);
            echo json_encode(array("message" => "Apenas administradores podem ser excluídos aqui."));
            exit;
        }

        $query = "DELETE FROM usuario WHERE id_usuario = :id";
        $stmt = $db->prepare($query);
        if ($stmt->execute([':id' => $id])) {
            http_response_code(200);
            echo json_encode(array("message" => "Administrador excluído com sucesso."));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Erro ao excluir administrador."));
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
