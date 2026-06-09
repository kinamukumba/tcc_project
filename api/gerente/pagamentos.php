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
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $sql = "SELECT p.id_pagamento,
                       p.id_reserva,
                       p.metodo,
                       p.valor,
                       p.status AS status_pagamento,
                       p.data_criacao,
                       p.data_pagamento,
                       r.codigo_reserva,
                       r.status_reserva,
                       r.data_checkin,
                       r.data_checkout,
                       u.nome  AS cliente_nome,
                       u.email AS cliente_email,
                       s.tipos_servicos AS servico
                FROM pagamento p
                INNER JOIN reserva  r ON p.id_reserva = r.id_reserva
                LEFT JOIN  cliente  c ON r.id_cliente  = c.id_cliente
                LEFT JOIN  usuario  u ON c.id_usuario  = u.id_usuario
                LEFT JOIN  `serviço` s ON r.`id_serviço` = s.`id_serviço`
                ORDER BY p.id_pagamento DESC";

        $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $v = (float)$row['valor'];
            $row['valor_formatado'] = number_format($v, 2, ',', '.') . ' Kz';
            $row['metodo_pagamento'] = $row['metodo'] ?? 'transferencia';
        }

        http_response_code(200);
        echo json_encode($rows);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["message" => "Erro: " . $e->getMessage()]);
    }
    exit;
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if (empty($data->id_pagamento) || empty($data->status)) {
        http_response_code(400);
        echo json_encode(["message" => "Dados incompletos."]);
        exit;
    }

    $valid = ['pago', 'pendente', 'cancelado'];
    if (!in_array($data->status, $valid)) {
        http_response_code(400);
        echo json_encode(["message" => "Status invalido."]);
        exit;
    }

    try {
        $dataPag = ($data->status === 'pago') ? ', data_pagamento = NOW()' : '';
        $stmt = $db->prepare("UPDATE pagamento SET status = :status$dataPag WHERE id_pagamento = :id");
        $stmt->execute([':status' => $data->status, ':id' => $data->id_pagamento]);

        http_response_code(200);
        echo json_encode(["message" => "Pagamento actualizado para '{$data->status}'."]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["message" => "Erro: " . $e->getMessage()]);
    }
    exit;
}

// POST para criar pagamento manualmente (reserva sem pagamento associado)
if ($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"));
    if (empty($data->id_reserva)) {
        http_response_code(400);
        echo json_encode(["message" => "id_reserva obrigatorio."]);
        exit;
    }
    try {
        // Calcular valor
        $r = $db->prepare("SELECT r.data_checkin, r.data_checkout, s.`preço` as preco
            FROM reserva r LEFT JOIN `serviço` s ON r.`id_serviço`=s.`id_serviço`
            WHERE r.id_reserva=:id LIMIT 1");
        $r->execute([':id' => $data->id_reserva]);
        $info = $r->fetch(PDO::FETCH_ASSOC);
        $dias = 1;
        if ($info && $info['data_checkin'] && $info['data_checkout']) {
            $dias = max(1, (new DateTime($info['data_checkin']))->diff(new DateTime($info['data_checkout']))->days);
        }
        $valor = ($info['preco'] ?? 0) * $dias;
        $db->prepare("INSERT INTO pagamento (id_reserva, metodo, valor, status) VALUES (:r,:m,:v,'pendente')")
           ->execute([':r' => $data->id_reserva, ':m' => ($data->metodo ?? 'transferencia'), ':v' => $valor]);
        http_response_code(201);
        echo json_encode(["message" => "Pagamento criado."]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["message" => "Erro: " . $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(["message" => "Metodo nao permitido."]);
?>