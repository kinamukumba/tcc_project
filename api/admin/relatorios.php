<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");
include_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    http_response_code(403);
    echo json_encode(["message" => "Acesso negado."]);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$period = isset($_GET['period']) ? $_GET['period'] : 'mensal';

try {
    // ── Totais gerais ──────────────────────────────────────────────────
    $total      = (int)$db->query("SELECT COUNT(*) FROM reserva")->fetchColumn();
    $aprovadas  = (int)$db->query("SELECT COUNT(*) FROM reserva WHERE status_reserva = 'aprovada'")->fetchColumn();
    $checkins   = (int)$db->query("SELECT COUNT(*) FROM reserva WHERE status_reserva = 'check-in'")->fetchColumn();
    $concluidas = (int)$db->query("SELECT COUNT(*) FROM reserva WHERE status_reserva IN ('check-out','concluida')")->fetchColumn();
    $rejeitadas = (int)$db->query("SELECT COUNT(*) FROM reserva WHERE status_reserva IN ('rejeitada','cancelada')")->fetchColumn();
    $pendentes  = (int)$db->query("SELECT COUNT(*) FROM reserva WHERE status_reserva = 'pendente'")->fetchColumn();
    $utentes    = (int)$db->query("SELECT COUNT(*) FROM usuario WHERE tipo_usuario = 'utente'")->fetchColumn();

    // ── Taxa de ocupacao (aprovadas + check-in sobre total) ────────────
    $ocupadas = $aprovadas + $checkins + $concluidas;
    $taxa = $total > 0 ? round(($ocupadas / $total) * 100) : 0;

    // ── Receita total estimada (reservas concluidas * preco * dias) ────
    $qReceita = "SELECT SUM(s.`preço` * GREATEST(1, DATEDIFF(r.data_checkout, r.data_checkin))) AS receita
                 FROM reserva r
                 LEFT JOIN `serviço` s ON r.`id_serviço` = s.`id_serviço`
                 WHERE r.status_reserva IN ('check-out','concluida')
                 AND r.data_checkin IS NOT NULL AND r.data_checkout IS NOT NULL";
    $receita = (float)($db->query($qReceita)->fetchColumn() ?: 0);

    // ── Grafico mensal (ultimos 6 meses) ───────────────────────────────
    $labels = [];
    $chartData = [];
    $meses = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
    for ($i = 5; $i >= 0; $i--) {
        $date = new DateTime();
        $date->modify("-$i months");
        $m = $date->format('n');
        $y = $date->format('Y');
        $labels[] = $meses[$m - 1] . '/' . substr($y, 2);
        $stmt = $db->prepare("SELECT COUNT(*) FROM reserva WHERE MONTH(data_reserva) = :m AND YEAR(data_reserva) = :y");
        $stmt->execute([':m' => $m, ':y' => $y]);
        $chartData[] = (int)$stmt->fetchColumn();
    }

    // ── Grafico por status ─────────────────────────────────────────────
    $statusChart = [
        'labels' => ['Pendente','Aprovada','Rejeitada','Check-in','Check-out/Concluida','Cancelada'],
        'data'   => [
            $pendentes,
            $aprovadas,
            (int)$db->query("SELECT COUNT(*) FROM reserva WHERE status_reserva = 'rejeitada'")->fetchColumn(),
            $checkins,
            $concluidas,
            (int)$db->query("SELECT COUNT(*) FROM reserva WHERE status_reserva = 'cancelada'")->fetchColumn(),
        ],
        'colors' => ['#ffc107','#28a745','#dc3545','#6f42c1','#17a2b8','#fd7e14']
    ];

    // ── Top servicos mais reservados ───────────────────────────────────
    $qTop = "SELECT s.tipos_servicos AS servico, COUNT(*) AS total
             FROM reserva r
             LEFT JOIN `serviço` s ON r.`id_serviço` = s.`id_serviço`
             WHERE s.tipos_servicos IS NOT NULL
             GROUP BY s.`id_serviço`, s.tipos_servicos
             ORDER BY total DESC LIMIT 5";
    $topServicos = $db->query($qTop)->fetchAll(PDO::FETCH_ASSOC);

    // ── Reservas recentes (ultimas 10) ─────────────────────────────────
    $qRec = "SELECT r.id_reserva, r.codigo_reserva, r.data_checkin, r.data_checkout,
                    r.status_reserva AS status, r.n_pessoa,
                    u.nome AS cliente_nome,
                    s.tipos_servicos AS servico,
                    s.`preço` AS preco_noite,
                    GREATEST(1, IFNULL(DATEDIFF(r.data_checkout, r.data_checkin),1)) AS dias
             FROM reserva r
             LEFT JOIN `serviço` s ON r.`id_serviço` = s.`id_serviço`
             LEFT JOIN cliente c   ON r.id_cliente = c.id_cliente
             LEFT JOIN usuario u   ON c.id_usuario = u.id_usuario
             ORDER BY r.id_reserva DESC LIMIT 10";
    $recentes = $db->query($qRec)->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'total'       => $total,
        'aprovadas'   => $aprovadas,
        'concluidas'  => $concluidas,
        'rejeitadas'  => $rejeitadas,
        'pendentes'   => $pendentes,
        'checkins'    => $checkins,
        'utentes'     => $utentes,
        'taxa_ocupacao' => $taxa,
        'receita_total' => $receita,
        'monthly_chart' => ['labels' => $labels, 'data' => $chartData],
        'status_chart'  => $statusChart,
        'top_servicos'  => $topServicos,
        'recentes'      => $recentes,
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Erro: " . $e->getMessage()]);
}
?>