<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->email) && !empty($data->senha)) {
    // 1. Buscar o usuário pelo email primeiro para identificar o papel (role)
    $query = "SELECT id_usuario, nome, email, senha, tipo_usuario FROM usuario WHERE email = :email LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":email", $data->email);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $authenticated = false;
        $input_password = $data->senha;
        $db_password = $row['senha'];
        $role = $row['tipo_usuario'];

        // 2. Lógica de verificação separada por papel
        switch ($role) {
            case 'admin':
                // O Admin usa MD5 (conforme configurado anteriormente)
                if (md5($input_password) === $db_password) {
                    $authenticated = true;
                }
                break;

            case 'utente':
                // Para o Utente, vamos verificar tanto MD5 quanto texto puro 
                // para garantir compatibilidade com registros antigos e novos
                if (md5($input_password) === $db_password || $input_password === $db_password) {
                    $authenticated = true;
                }
                break;

            case 'gerente':
                // Para o Gestor/Gerente, seguimos a mesma lógica flexível por enquanto
                if (md5($input_password) === $db_password || $input_password === $db_password) {
                    $authenticated = true;
                }
                break;
        }

        if ($authenticated) {
            // Configura a sessão
            $_SESSION['user_id'] = $row['id_usuario'];
            $_SESSION['user_name'] = $row['nome'];
            $_SESSION['user_email'] = $row['email'];
            $_SESSION['user_role'] = $role;

            // 3. Buscar o ID específico da tabela relacionada (cliente, administrador ou gestor)
            $role_id = null;
            if ($role === 'utente') {
                $qRole = "SELECT id_cliente FROM cliente WHERE id_usuario = :id LIMIT 1";
                $sRole = $db->prepare($qRole);
                $sRole->execute([':id' => $row['id_usuario']]);
                $rRole = $sRole->fetch(PDO::FETCH_ASSOC);
                if ($rRole) $role_id = $rRole['id_cliente'];
            } else if ($role === 'admin') {
                $qRole = "SELECT id_adminitrador FROM administrador WHERE id_usuario = :id LIMIT 1";
                $sRole = $db->prepare($qRole);
                $sRole->execute([':id' => $row['id_usuario']]);
                $rRole = $sRole->fetch(PDO::FETCH_ASSOC);
                if ($rRole) $role_id = $rRole['id_adminitrador'];
            } else if ($role === 'gerente') {
                $qRole = "SELECT id_gestor FROM gestor WHERE id_usuario = :id LIMIT 1";
                $sRole = $db->prepare($qRole);
                $sRole->execute([':id' => $row['id_usuario']]);
                $rRole = $sRole->fetch(PDO::FETCH_ASSOC);
                if ($rRole) $role_id = $rRole['id_gestor'];
            }
            
            $_SESSION['role_id'] = $role_id;

            http_response_code(200);
            echo json_encode(array(
                "message" => "Login realizado com sucesso.",
                "user" => array(
                    "id" => $row['id_usuario'],
                    "nome" => $row['nome'],
                    "email" => $row['email'],
                    "role" => $role,
                    "role_id" => $role_id
                )
            ));
        } else {
            http_response_code(401);
            echo json_encode(array("message" => "Senha incorreta."));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Utilizador não encontrado."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Dados incompletos."));
}
?>
