<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

if(isset($_SESSION['user_id'])) {
    http_response_code(200);
    echo json_encode(array(
        "authenticated" => true,
        "user" => array(
            "id" => $_SESSION['user_id'],
            "nome" => $_SESSION['user_name'],
            "email" => $_SESSION['user_email'],
            "role" => $_SESSION['user_role'],
            "role_id" => isset($_SESSION['role_id']) ? $_SESSION['role_id'] : null
        )
    ));
} else {
    http_response_code(401);
    echo json_encode(array(
        "authenticated" => false,
        "message" => "Usuário não autenticado."
    ));
}
?>
