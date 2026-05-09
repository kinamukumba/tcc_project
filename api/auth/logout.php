<?php
session_start();
session_unset();
session_destroy();

header("Content-Type: application/json; charset=UTF-8");
http_response_code(200);
echo json_encode(array("message" => "Logout realizado com sucesso."));
?>
