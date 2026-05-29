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

// Estatísticas Gerais
$stats = array();

// 1. Total de Reservas
$q1 = "SELECT COUNT(*) as total FROM reserva";
$s1 = $db->prepare($q1);
$s1->execute();
$stats['total_reservas'] = $s1->fetch(PDO::FETCH_ASSOC)['total'];

// 2. Utentes Ativos
$q2 = "SELECT COUNT(*) as total FROM usuario WHERE tipo_usuario = 'utente'";
$s2 = $db->prepare($q2);
$s2->execute();
$stats['total_utentes'] = $s2->fetch(PDO::FETCH_ASSOC)['total'];

// 3. Receita Estimada (Soma dos preços dos serviços das reservas aprovadas/concluídas)
$q3 = "SELECT SUM(s.preço) as total 
       FROM reserva r 
       JOIN serviço s ON r.id_serviço = s.id_serviço 
       WHERE r.status_reserva IN ('aprovada', 'concluida')";
$s3 = $db->prepare($q3);
$s3->execute();
$res3 = $s3->fetch(PDO::FETCH_ASSOC)['total'];
$stats['receita_total'] = $res3 ? $res3 : 0;

// 4. Novos Feedbacks (Avaliações)
$q4 = "SELECT COUNT(*) as total FROM avaliação";
$s4 = $db->prepare($q4);
$s4->execute();
$stats['total_feedbacks'] = $s4->fetch(PDO::FETCH_ASSOC)['total'];

// 5. Reservas Recentes
$q5 = "SELECT r.id_reserva, u.nome as utente, s.tipos_servicos as servico, r.data_checkin, r.status_reserva 
       FROM reserva r 
       JOIN cliente c ON r.id_cliente = c.id_cliente
       JOIN usuario u ON c.id_usuario = u.id_usuario
       JOIN serviço s ON r.id_serviço = s.id_serviço
       ORDER BY r.data_reserva DESC 
       LIMIT 5";
$s5 = $db->prepare($q5);
$s5->execute();
$stats['reservas_recentes'] = $s5->fetchAll(PDO::FETCH_ASSOC);

http_response_code(200);
echo json_encode($stats);
?>
