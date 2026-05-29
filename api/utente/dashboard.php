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

$id_cliente = $_SESSION['role_id'];

$stats = array();

// Reservas Ativas (Pendente ou Aprovada)
$queryAtivas = "SELECT COUNT(*) as total FROM reserva WHERE id_cliente = :id AND status_reserva IN ('pendente', 'aprovada')";
$stmtAtivas = $db->prepare($queryAtivas);
$stmtAtivas->execute([':id' => $id_cliente]);
$rowAtivas = $stmtAtivas->fetch(PDO::FETCH_ASSOC);
$stats['reservas_ativas'] = $rowAtivas['total'];

// Mock para notificações e fidelidade, já que não tem na base
$stats['notificacoes'] = 0; // Simulado
$stats['pontos_fidelidade'] = 0; // Simulado

// Buscar a última reserva para destaque no dashboard
$queryUltima = "SELECT r.id_reserva, r.data_checkin, r.data_checkout, r.status_reserva, r.n_pessoa, s.tipos_servicos, s.descrição
                FROM reserva r
                INNER JOIN serviço s ON r.id_serviço = s.id_serviço
                WHERE r.id_cliente = :id
                ORDER BY r.data_reserva DESC LIMIT 1";
$stmtUltima = $db->prepare($queryUltima);
$stmtUltima->execute([':id' => $id_cliente]);
if ($rowUltima = $stmtUltima->fetch(PDO::FETCH_ASSOC)){
    $stats['ultima_reserva'] = $rowUltima;
} else {
    $stats['ultima_reserva'] = null;
}

http_response_code(200);
echo json_encode($stats);
?>
