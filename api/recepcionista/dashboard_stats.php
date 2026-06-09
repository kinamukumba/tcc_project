<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");
include_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'recepcionista') {
    http_response_code(403);
    echo json_encode(["message" => "Acesso negado."]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $total     = $db->query("SELECT COUNT(*) FROM reserva")->fetchColumn();
    $checkins  = $db->query("SELECT COUNT(*) FROM reserva WHERE status_reserva = 'check-in'  AND DATE(data_checkin)  = CURDATE()")->fetchColumn();
    $checkouts = $db->query("SELECT COUNT(*) FROM reserva WHERE status_reserva = 'check-out' AND DATE(data_checkout) = CURDATE()")->fetchColumn();
    $pendentes = $db->query("SELECT COUNT(*) FROM reserva WHERE status_reserva = 'pendente'")->fetchColumn();
    $aprovadas = $db->query("SELECT COUNT(*) FROM reserva WHERE status_reserva = 'aprovada'")->fetchColumn();

    http_response_code(200);
    echo json_encode([
        'total'     => (int)$total,
        'checkins'  => (int)$checkins,
        'checkouts' => (int)$checkouts,
        'pendentes' => (int)$pendentes,
        'aprovadas' => (int)$aprovadas,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Erro: " . $e->getMessage()]);
}
?>