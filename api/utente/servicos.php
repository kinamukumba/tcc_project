<?php
header("Content-Type: application/json; charset=UTF-8");
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$query = "SELECT id_serviço, tipos_servicos, descrição, preço FROM serviço WHERE status = 'desocupado'";
$stmt = $db->prepare($query);
$stmt->execute();

$servicos = array();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
    array_push($servicos, $row);
}

http_response_code(200);
echo json_encode($servicos);
?>
