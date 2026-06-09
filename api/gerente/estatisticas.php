<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");
include_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'gerente') {
    http_response_code(403);
    echo json_encode(["message" => "Acesso negado."]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    // KPIs
    $total      = (int)$db->query("SELECT COUNT(*) FROM reserva")->fetchColumn();
    $confirmed  = (int)$db->query("SELECT COUNT(*) FROM reserva WHERE status_reserva IN ('aprovada','check-in','check-out','concluida')")->fetchColumn();
    $cancelled  = (int)$db->query("SELECT COUNT(*) FROM reserva WHERE status_reserva IN ('cancelada','rejeitada')")->fetchColumn();
    $checkin    = (int)$db->query("SELECT COUNT(*) FROM reserva WHERE status_reserva = 'check-in'")->fetchColumn();

    // Quartos/Servicos disponiveis vs ocupados
    $totalRooms   = (int)$db->query("SELECT COUNT(*) FROM `serviço`")->fetchColumn();
    $occupiedRooms= $checkin;
    $freeRooms    = max(0, $totalRooms - $occupiedRooms);
    $occupancyRate= $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100) : 0;

    // Grafico - ultimos 6 meses
    $labels = [];
    $chartData = [];
    $meses = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
    for ($i = 5; $i >= 0; $i--) {
        $date = new DateTime(); $date->modify("-$i months");
        $m = $date->format('n'); $y = $date->format('Y');
        $labels[] = $meses[$m-1] . '/' . substr($y, 2);
        $stmt = $db->prepare("SELECT COUNT(*) FROM reserva WHERE MONTH(data_reserva)=:m AND YEAR(data_reserva)=:y");
        $stmt->execute([':m'=>$m, ':y'=>$y]);
        $chartData[] = (int)$stmt->fetchColumn();
    }

    // Servicos mais reservados
    $mostBooked = $db->query(
        "SELECT s.tipos_servicos AS servico, COUNT(*) AS reservas_count
         FROM reserva r
         LEFT JOIN `serviço` s ON r.`id_serviço` = s.`id_serviço`
         WHERE s.tipos_servicos IS NOT NULL
         GROUP BY s.`id_serviço`, s.tipos_servicos
         ORDER BY reservas_count DESC LIMIT 5"
    )->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'total_bookings'     => $total,
        'confirmed_bookings' => $confirmed,
        'cancelled_bookings' => $cancelled,
        'occupancy_rate'     => $occupancyRate,
        'free_rooms'         => $freeRooms,
        'occupied_rooms'     => $occupiedRooms,
        'total_rooms'        => $totalRooms,
        'monthly_chart'      => ['labels' => $labels, 'data' => $chartData],
        'most_booked'        => $mostBooked,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Erro: " . $e->getMessage()]);
}
?>