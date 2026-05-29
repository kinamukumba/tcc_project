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

// Relatórios Financeiros e de Ocupação
$report = array();

// 1. Receita por Mês (últimos 6 meses)
$report['receita_mensal'] = array();
for($i=5; $i>=0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthName = date('M', strtotime("-$i months"));
    
    $q = "SELECT SUM(s.preço) as total 
          FROM reserva r 
          JOIN serviço s ON r.id_serviço = s.id_serviço 
          WHERE r.status_reserva IN ('aprovada', 'concluida') 
          AND r.data_checkin LIKE :month";
    $s = $db->prepare($q);
    $mParam = $month . '%';
    $s->bindParam(':month', $mParam);
    $s->execute();
    $res = $s->fetch(PDO::FETCH_ASSOC);
    
    $report['receita_mensal'][] = array(
        "mes" => $monthName,
        "valor" => $res['total'] ? (float)$res['total'] : 0
    );
}

// 2. Ocupação por Tipo de Serviço
$q2 = "SELECT s.tipos_servicos, COUNT(r.id_reserva) as total 
       FROM serviço s 
       LEFT JOIN reserva r ON s.id_serviço = r.id_serviço 
       GROUP BY s.id_serviço";
$s2 = $db->prepare($q2);
$s2->execute();
$report['ocupacao_servicos'] = $s2->fetchAll(PDO::FETCH_ASSOC);

http_response_code(200);
echo json_encode($report);
?>
