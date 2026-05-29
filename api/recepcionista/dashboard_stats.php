<?php
// api/recepcionista/dashboard_stats.php
session_start();
header("Content-Type: application/json; charset=UTF-8");
include_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'recepcionista') {
    http_response_code(403);
    echo json_encode(array("message" => "Acesso negado."));
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    // 1. Total de Reservas do dia (check-in ou check-out agendado para hoje)
    $qTodayBookings = "SELECT COUNT(*) as count FROM reserva WHERE (data_checkin = CURRENT_DATE() OR data_checkout = CURRENT_DATE()) AND status_reserva != 'cancelada'";
    $sTodayBookings = $db->query($qTodayBookings);
    $todayBookings = $sTodayBookings->fetch(PDO::FETCH_ASSOC)['count'];

    // 2. Check-ins realizados hoje
    $qCheckinsToday = "SELECT COUNT(*) as count FROM reserva WHERE data_checkin = CURRENT_DATE() AND status_reserva = 'checkin'";
    $sCheckinsToday = $db->query($qCheckinsToday);
    $checkinsToday = $sCheckinsToday->fetch(PDO::FETCH_ASSOC)['count'];

    // 3. Check-outs realizados hoje
    $qCheckoutsToday = "SELECT COUNT(*) as count FROM reserva WHERE data_checkout = CURRENT_DATE() AND (status_reserva = 'checkout' OR status_reserva = 'concluida')";
    $sCheckoutsToday = $db->query($qCheckoutsToday);
    $checkoutsToday = $sCheckoutsToday->fetch(PDO::FETCH_ASSOC)['count'];

    // 4. Quartos Disponíveis (Capacidade total fictícia do hotel: 60 quartos - quartos atualmente em check-in)
    $qOccupiedRooms = "SELECT COUNT(*) as count FROM reserva WHERE status_reserva = 'checkin'";
    $sOccupiedRooms = $db->query($qOccupiedRooms);
    $occupied = $sOccupiedRooms->fetch(PDO::FETCH_ASSOC)['count'];
    $totalRooms = 60;
    $availableRooms = max(0, $totalRooms - $occupied);

    // 5. Total de cancelamentos
    $qCancellations = "SELECT COUNT(*) as count FROM reserva WHERE status_reserva = 'cancelada'";
    $sCancellations = $db->query($qCancellations);
    $cancellations = $sCancellations->fetch(PDO::FETCH_ASSOC)['count'];

    http_response_code(200);
    echo json_encode(array(
        "today_bookings" => $todayBookings,
        "checkins_today" => $checkinsToday,
        "checkouts_today" => $checkoutsToday,
        "occupied_rooms" => $occupied,
        "available_rooms" => $availableRooms,
        "cancellations" => $cancellations
    ));
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(array("message" => "Erro ao calcular estatísticas: " . $e->getMessage()));
}
?>
