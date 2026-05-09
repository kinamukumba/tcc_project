<?php
header("Content-Type: application/json; charset=UTF-8");
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if(!empty($data->nome) && !empty($data->email) && !empty($data->senha)) {
    // Verificar se email já existe
    $queryCheck = "SELECT id_usuario FROM usuario WHERE email = :email LIMIT 1";
    $stmtCheck = $db->prepare($queryCheck);
    $stmtCheck->bindParam(':email', $data->email);
    $stmtCheck->execute();

    if($stmtCheck->rowCount() > 0) {
        http_response_code(400);
        echo json_encode(array("message" => "Este e-mail já está em uso."));
        exit;
    }

    try {
        $db->beginTransaction();

        // 1. Inserir em usuario
        $queryUsuario = "INSERT INTO usuario (nome, email, telefone, senha, tipo_usuario) VALUES (:nome, :email, :telefone, :senha, 'utente')";
        $stmtUsuario = $db->prepare($queryUsuario);
        
        $telefone = isset($data->telefone) ? $data->telefone : '';
        $senha = $data->senha; // Ideal: hash, ex: password_hash($data->senha, PASSWORD_DEFAULT);

        $stmtUsuario->bindParam(':nome', $data->nome);
        $stmtUsuario->bindParam(':email', $data->email);
        $stmtUsuario->bindParam(':telefone', $telefone);
        $stmtUsuario->bindParam(':senha', $senha);
        
        if($stmtUsuario->execute()) {
            $id_usuario = $db->lastInsertId();

            // 2. Inserir em cliente
            $queryCliente = "INSERT INTO cliente (nome, sobrenome, email, bi, telemovel, senha, id_usuario) VALUES (:nome, :sobrenome, :email, :bi, :telemovel, :senha, :id_usuario)";
            $stmtCliente = $db->prepare($queryCliente);
            
            $sobrenome = isset($data->sobrenome) ? $data->sobrenome : '';
            $bi = isset($data->bi) ? $data->bi : '';
            
            $stmtCliente->bindParam(':nome', $data->nome);
            $stmtCliente->bindParam(':sobrenome', $sobrenome);
            $stmtCliente->bindParam(':email', $data->email);
            $stmtCliente->bindParam(':bi', $bi);
            $stmtCliente->bindParam(':telemovel', $telefone);
            $stmtCliente->bindParam(':senha', $senha);
            $stmtCliente->bindParam(':id_usuario', $id_usuario);

            if($stmtCliente->execute()) {
                $db->commit();
                http_response_code(201);
                echo json_encode(array("message" => "Usuário registrado com sucesso."));
            } else {
                $db->rollBack();
                http_response_code(503);
                echo json_encode(array("message" => "Não foi possível registrar o cliente."));
            }
        } else {
            $db->rollBack();
            http_response_code(503);
            echo json_encode(array("message" => "Não foi possível criar o usuário."));
        }
    } catch(Exception $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(array("message" => "Erro interno: " . $e->getMessage()));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Dados incompletos para o cadastro."));
}
?>
