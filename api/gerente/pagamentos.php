<?php
// api/gerente/pagamentos.php
session_start();
header("Content-Type: application/json; charset=UTF-8");
include_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'gerente') {
    http_response_code(403);
    echo json_encode(array("message" => "Acesso negado. Apenas o gerente pode ver pagamentos."));
    exit;
}

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'GET') {
    $query = "SELECT p.id_pagamento, p.valor, p.método_pagamento, p.status_pagamento, p.data_pagamento, 
                     r.codigo_reserva, c.nome as cliente_nome, c.email as cliente_email 
              FROM pagamento p 
              JOIN reserva r ON p.id_reserva = r.id_reserva 
              JOIN cliente c ON r.id_cliente = c.id_cliente 
              ORDER BY p.data_pagamento DESC";
              
    $stmt = $db->query($query);
    $pagamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($pagamentos as &$p) {
        $p['valor_formatado'] = number_format($p['valor'], 2, ',', '.') . ' KZ';
    }
    
    http_response_code(200);
    echo json_encode($pagamentos);
} 
else if ($method == 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->id_pagamento) && !empty($data->status)) {
        $query = "UPDATE pagamento SET status_pagamento = :status WHERE id_pagamento = :id";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([
            ':status' => $data->status,
            ':id' => $data->id_pagamento
        ])) {
            // Se o pagamento for pago, podemos aprovar automaticamente a reserva vinculada?
            // Mas o fluxo diz "Admin aprova, Recepcionista verifica e Gerente recebe confirmação".
            // Então apenas atualizamos o pagamento e está feito.
            http_response_code(200);
            echo json_encode(array("message" => "Pagamento atualizado com sucesso!"));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Erro ao atualizar pagamento."));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Dados incompletos."));
    }
} else {
    http_response_code(405);
    echo json_encode(array("message" => "Método não permitido."));
}
?>
