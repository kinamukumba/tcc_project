<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");
include_once '../config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'utente') {
    http_response_code(403);
    echo json_encode(array("message" => "Acesso negado."));
    exit;
}

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$id_usuario = $_SESSION['user_id'];
$id_cliente = $_SESSION['role_id'];

if($method == 'GET') {
    $query = "SELECT c.nome, c.sobrenome, c.email, c.telemovel, c.bi,
              (SELECT COUNT(*) FROM reserva WHERE id_cliente = c.id_cliente) as total_estadias
              FROM cliente c
              WHERE c.id_cliente = :id_cliente";
              
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_cliente', $id_cliente);
    $stmt->execute();
    
    if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        http_response_code(200);
        echo json_encode($row);
    } else {
        http_response_code(404);
        echo json_encode(array("message" => "Perfil não encontrado."));
    }
} 
else if($method == 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    if(!empty($data->nome) && !empty($data->email)) {
        
        $query = "UPDATE cliente SET nome = :nome, sobrenome = :sobrenome, email = :email, telemovel = :telemovel WHERE id_cliente = :id_cliente";
        $stmt = $db->prepare($query);
        
        $stmt->bindParam(':nome', $data->nome);
        $stmt->bindParam(':sobrenome', $data->sobrenome);
        $stmt->bindParam(':email', $data->email);
        $stmt->bindParam(':telemovel', $data->telemovel);
        $stmt->bindParam(':id_cliente', $id_cliente);
        
        if($stmt->execute()) {
            // Atualizar nome completo no usuario também para consistência
            $nomeCompleto = $data->nome . ' ' . $data->sobrenome;
            $qUpUserMain = "UPDATE usuario SET nome = :nome, email = :email WHERE id_usuario = :id_usuario";
            $sUpMain = $db->prepare($qUpUserMain);
            $sUpMain->bindParam(':nome', $nomeCompleto);
            $sUpMain->bindParam(':email', $data->email);
            $sUpMain->bindParam(':id_usuario', $id_usuario);
            $sUpMain->execute();

            // Se tiver senha nova
            if(!empty($data->nova_senha) && !empty($data->senha_atual)) {
                $qUser = "SELECT senha FROM usuario WHERE id_usuario = :id_usuario";
                $stmtU = $db->prepare($qUser);
                $stmtU->bindParam(':id_usuario', $id_usuario);
                $stmtU->execute();
                if($rowU = $stmtU->fetch(PDO::FETCH_ASSOC)) {
                    if(md5($data->senha_atual) == $rowU['senha']) {
                        $newPass = md5($data->nova_senha);
                        $qUpUser = "UPDATE usuario SET senha = :senha WHERE id_usuario = :id_usuario";
                        $sUp = $db->prepare($qUpUser);
                        $sUp->bindParam(':senha', $newPass);
                        $sUp->bindParam(':id_usuario', $id_usuario);
                        $sUp->execute();
                    } else {
                        echo json_encode(array("message" => "Dados atualizados, mas a senha atual está incorreta."));
                        exit;
                    }
                }
            }
            http_response_code(200);
            echo json_encode(array("message" => "Perfil atualizado com sucesso."));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Não foi possível atualizar o perfil."));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Dados incompletos."));
    }
}
else if($method == 'DELETE') {
    // Eliminar conta do utente
    // Como tem ON DELETE CASCADE nas chaves estrangeiras de usuario -> cliente, etc.
    // Deletar o usuario deve limpar tudo.
    
    $query = "DELETE FROM usuario WHERE id_usuario = :id_usuario";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_usuario', $id_usuario);
    
    if($stmt->execute()) {
        session_destroy();
        http_response_code(200);
        echo json_encode(array("message" => "Conta eliminada com sucesso. Sentiremos sua falta!"));
    } else {
        http_response_code(503);
        echo json_encode(array("message" => "Erro ao eliminar conta."));
    }
}
?>
