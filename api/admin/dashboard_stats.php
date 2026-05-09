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

$stats = array();

// Total de reservas
$stmtRes = $db->query("SELECT COUNT(*) as total FROM reserva");
$rowRes = $stmtRes->fetch(PDO::FETCH_ASSOC);
$stats['total_reservas'] = $rowRes['total'];

// Total de utentes (clientes)
$stmtUt = $db->query("SELECT COUNT(*) as total FROM cliente");
$rowUt = $stmtUt->fetch(PDO::FETCH_ASSOC);
$stats['total_utentes'] = $rowUt['total'];

// Receita Estimada (soma de pagamentos 'pago')
$stmtRec = $db->query("SELECT SUM(valor) as total FROM pagamento WHERE status_pagamento = 'pago'");
$rowRec = $stmtRec->fetch(PDO::FETCH_ASSOC);
$stats['receita_estimada'] = $rowRec['total'] ? $rowRec['total'] : 0;

http_response_code(200);
echo json_encode($stats);
?>
