<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->email) && !empty($data->senha)) {
    // Busca o usuário na base (nota: senha idealmente seria com hash, usando MD5 aqui conforme estrutura comum, mas recomendável password_hash em prod)
    $query = "SELECT id_usuario, nome, email, tipo_usuario FROM usuario WHERE email = :email AND senha = :senha LIMIT 1";
    $stmt = $db->prepare($query);

    $stmt->bindParam(":email", $data->email);
    // Assumindo que a senha não estava com hash no modelo ER anterior. 
    // SE houver md5, altere para md5($data->senha)
    $stmt->bindParam(":senha", $data->senha);
    
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Configura sessão
        $_SESSION['user_id'] = $row['id_usuario'];
        $_SESSION['user_name'] = $row['nome'];
        $_SESSION['user_email'] = $row['email'];
        $_SESSION['user_role'] = $row['tipo_usuario'];

        // Buscar o ID específico da tabela relacionada
        $role_id = null;
        if($row['tipo_usuario'] == 'utente') {
            $qRole = "SELECT id_cliente FROM cliente WHERE id_usuario = :id LIMIT 1";
            $sRole = $db->prepare($qRole);
            $sRole->execute([':id' => $row['id_usuario']]);
            $rRole = $sRole->fetch(PDO::FETCH_ASSOC);
            if($rRole) $role_id = $rRole['id_cliente'];
            $_SESSION['role_id'] = $role_id;
        } else if($row['tipo_usuario'] == 'admin') {
            $qRole = "SELECT id_adminitrador FROM administrador WHERE id_usuario = :id LIMIT 1";
            $sRole = $db->prepare($qRole);
            $sRole->execute([':id' => $row['id_usuario']]);
            $rRole = $sRole->fetch(PDO::FETCH_ASSOC);
            if($rRole) $role_id = $rRole['id_adminitrador'];
            $_SESSION['role_id'] = $role_id;
        } else if($row['tipo_usuario'] == 'gerente') {
            $qRole = "SELECT id_gestor FROM gestor WHERE id_usuario = :id LIMIT 1";
            $sRole = $db->prepare($qRole);
            $sRole->execute([':id' => $row['id_usuario']]);
            $rRole = $sRole->fetch(PDO::FETCH_ASSOC);
            if($rRole) $role_id = $rRole['id_gestor'];
            $_SESSION['role_id'] = $role_id;
        }

        http_response_code(200);
        echo json_encode(array(
            "message" => "Login realizado com sucesso.",
            "user" => array(
                "id" => $row['id_usuario'],
                "nome" => $row['nome'],
                "email" => $row['email'],
                "role" => $row['tipo_usuario'],
                "role_id" => $role_id
            )
        ));
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Email ou senha incorretos."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Dados incompletos."));
}
?>
