<?php
// api/reserva_publica.php
session_start();
header("Content-Type: application/json; charset=UTF-8");
include_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    // Validar campos obrigatórios
    if (
        !empty($data->nome) && 
        !empty($data->email) && 
        !empty($data->telemovel) && 
        !empty($data->bi) && 
        !empty($data->data_checkin) && 
        !empty($data->data_checkout) && 
        !empty($data->id_servico) && 
        !empty($data->n_pessoa) &&
        !empty($data->metodo_pagamento)
    ) {
        try {
            $db->beginTransaction();
            
            $id_cliente = null;
            
            // 1. Identificar ou Criar Cliente
            if (isset($_SESSION['user_id']) && $_SESSION['user_role'] == 'utente') {
                $id_cliente = $_SESSION['role_id'];
            } else {
                // Verificar se já existe cliente com este e-mail
                $qCheck = "SELECT id_cliente FROM cliente WHERE email = :email LIMIT 1";
                $sCheck = $db->prepare($qCheck);
                $sCheck->execute([':email' => $data->email]);
                
                if ($sCheck->rowCount() > 0) {
                    $row = $sCheck->fetch(PDO::FETCH_ASSOC);
                    $id_cliente = $row['id_cliente'];
                } else {
                    // Criar cliente temporário/visitante (sem conta de usuário associada)
                    $qCreate = "INSERT INTO cliente (nome, email, bi, telemovel, id_usuario) 
                                VALUES (:nome, :email, :bi, :telemovel, NULL)";
                    $sCreate = $db->prepare($qCreate);
                    $sCreate->execute([
                        ':nome' => $data->nome,
                        ':email' => $data->email,
                        ':bi' => $data->bi,
                        ':telemovel' => $data->telemovel
                    ]);
                    $id_cliente = $db->lastInsertId();
                }
            }
            
            // 2. Buscar preço e status do serviço para cálculo e validação
            $qServico = "SELECT preço, status FROM serviço WHERE id_serviço = :id_servico LIMIT 1";
            $sServico = $db->prepare($qServico);
            $sServico->execute([':id_servico' => $data->id_servico]);
            if ($sServico->rowCount() == 0) {
                throw new Exception("Serviço/Quarto inválido.");
            }
            $servico = $sServico->fetch(PDO::FETCH_ASSOC);
            
            // Verificação 1: Status manual de ocupado
            if ($servico['status'] == 'ocupado') {
                throw new Exception("Este serviço/quarto está marcado como ocupado ou indisponível.");
            }
            
            // Verificação 2: Sobreposição de datas com reservas ativas
            $qOverLap = "SELECT id_reserva FROM reserva 
                         WHERE id_serviço = :id_servico 
                           AND status_reserva IN ('aprovada', 'checkin', 'pendente')
                           AND NOT (data_checkout <= :checkin OR data_checkin >= :checkout)";
            $sOverLap = $db->prepare($qOverLap);
            $sOverLap->execute([
                ':id_servico' => $data->id_servico,
                ':checkin' => $data->data_checkin,
                ':checkout' => $data->data_checkout
            ]);
            if ($sOverLap->rowCount() > 0) {
                throw new Exception("Este serviço/quarto já está reservado/ocupado no período selecionado.");
            }
            
            // Calcular noites
            $date1 = new DateTime($data->data_checkin);
            $date2 = new DateTime($data->data_checkout);
            $diff = $date1->diff($date2)->days;
            $dias = $diff > 0 ? $diff : 1;
            $valorTotal = $servico['preço'] * $dias;
            
            // 3. Gerar código de reserva único
            $codigo_reserva = "";
            $isUnique = false;
            while (!$isUnique) {
                $codigo_reserva = "EPIC-" . rand(100000, 999999);
                $qCodeCheck = "SELECT id_reserva FROM reserva WHERE codigo_reserva = :code";
                $sCodeCheck = $db->prepare($qCodeCheck);
                $sCodeCheck->execute([':code' => $codigo_reserva]);
                if ($sCodeCheck->rowCount() == 0) {
                    $isUnique = true;
                }
            }
            
            // 4. Inserir Reserva
            $qReserva = "INSERT INTO reserva (id_cliente, id_serviço, n_pessoa, data_reserva, data_checkin, data_checkout, status_reserva, codigo_reserva) 
                         VALUES (:id_cliente, :id_serviço, :n_pessoa, NOW(), :checkin, :checkout, 'pendente', :codigo_reserva)";
            $sReserva = $db->prepare($qReserva);
            $sReserva->execute([
                ':id_cliente' => $id_cliente,
                ':id_serviço' => $data->id_servico,
                ':n_pessoa' => $data->n_pessoa,
                ':checkin' => $data->data_checkin,
                ':checkout' => $data->data_checkout,
                ':codigo_reserva' => $codigo_reserva
            ]);
            $id_reserva = $db->lastInsertId();
            
            // 5. Registrar Pagamento
            $status_pagamento = ($data->metodo_pagamento == 'dinheiro') ? 'pendente' : 'pago'; // Se for transferência ou cartão, simula pago/pendente de validação
            $qPagamento = "INSERT INTO pagamento (id_reserva, valor, método_pagamento, status_pagamento, data_pagamento) 
                           VALUES (:id_reserva, :valor, :metodo_pagamento, :status_pagamento, NOW())";
            $sPagamento = $db->prepare($qPagamento);
            $sPagamento->execute([
                ':id_reserva' => $id_reserva,
                ':valor' => $valorTotal,
                ':metodo_pagamento' => $data->metodo_pagamento,
                ':status_pagamento' => $status_pagamento
            ]);
            
            // 6. Criar notificação para o cliente se ele for logado
            if (isset($_SESSION['user_id']) && $_SESSION['user_role'] == 'utente') {
                $qNotify = "INSERT INTO notificacao (id_cliente, mensagem, lida, data_criacao) 
                            VALUES (:id_cliente, :msg, 0, NOW())";
                $sNotify = $db->prepare($qNotify);
                $msgText = "A sua reserva " . $codigo_reserva . " foi efetuada com sucesso e está pendente de aprovação.";
                $sNotify->execute([
                    ':id_cliente' => $id_cliente,
                    ':msg' => $msgText
                ]);
            }
            
            $db->commit();
            
            http_response_code(201);
            echo json_encode(array(
                "message" => "Reserva criada com sucesso!",
                "codigo_reserva" => $codigo_reserva,
                "valor_total" => number_format($valorTotal, 2, ',', '.') . ' KZ',
                "dias" => $dias
            ));
            
        } catch (Exception $e) {
            $db->rollBack();
            http_response_code(500);
            echo json_encode(array("message" => "Erro ao processar reserva: " . $e->getMessage()));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Dados incompletos. Por favor preencha todos os campos do formulário."));
    }
} else {
    http_response_code(405);
    echo json_encode(array("message" => "Método não permitido."));
}
?>
