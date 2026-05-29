<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->email) && !empty($data->senha)) {

    $query = "SELECT id_usuario, nome, email, senha, tipo_usuario FROM usuario WHERE email = :email LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":email", $data->email);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $authenticated = false;
        $input_password = $data->senha;
        $db_password    = $row['senha'];
        $role           = $row['tipo_usuario'];

        // Verificação de senha — todos usam MD5 ou texto puro (compatibilidade)
        if (md5($input_password) === $db_password || $input_password === $db_password) {
            $authenticated = true;
        }

        if ($authenticated) {
            // Configura a sessão
            $_SESSION['user_id']    = $row['id_usuario'];
            $_SESSION['user_name']  = $row['nome'];
            $_SESSION['user_email'] = $row['email'];
            $_SESSION['user_role']  = $role;

            // Buscar o ID específico do perfil relacionado
            $role_id = null;
            switch ($role) {
                case 'utente':
                    $qRole = "SELECT id_cliente FROM cliente WHERE id_usuario = :id LIMIT 1";
                    $sRole = $db->prepare($qRole);
                    $sRole->execute([':id' => $row['id_usuario']]);
                    $rRole = $sRole->fetch(PDO::FETCH_ASSOC);
                    if ($rRole) $role_id = $rRole['id_cliente'];
                    break;

                case 'admin':
                    $qRole = "SELECT id_adminitrador FROM administrador WHERE id_usuario = :id LIMIT 1";
                    $sRole = $db->prepare($qRole);
                    $sRole->execute([':id' => $row['id_usuario']]);
                    $rRole = $sRole->fetch(PDO::FETCH_ASSOC);
                    if ($rRole) $role_id = $rRole['id_adminitrador'];
                    break;

                case 'gerente':
                    $qRole = "SELECT id_gestor FROM gestor WHERE id_usuario = :id LIMIT 1";
                    $sRole = $db->prepare($qRole);
                    $sRole->execute([':id' => $row['id_usuario']]);
                    $rRole = $sRole->fetch(PDO::FETCH_ASSOC);
                    if ($rRole) $role_id = $rRole['id_gestor'];
                    break;

                case 'recepcionista':
                    $qRole = "SELECT id_recepcionista FROM recepcionista WHERE id_usuario = :id LIMIT 1";
                    $sRole = $db->prepare($qRole);
                    $sRole->execute([':id' => $row['id_usuario']]);
                    $rRole = $sRole->fetch(PDO::FETCH_ASSOC);
                    if ($rRole) $role_id = $rRole['id_recepcionista'];
                    break;
            }

            $_SESSION['role_id'] = $role_id;

            http_response_code(200);
            echo json_encode([
                "message" => "Login realizado com sucesso.",
                "user"    => [
                    "id"      => $row['id_usuario'],
                    "nome"    => $row['nome'],
                    "email"   => $row['email'],
                    "role"    => $role,
                    "role_id" => $role_id
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode(["message" => "Senha incorrecta."]);
        }
    } else {
        http_response_code(401);
        echo json_encode(["message" => "Utilizador não encontrado."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "Dados incompletos. Preencha o e-mail e a senha."]);
}
?>
