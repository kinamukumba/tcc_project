<?php
// seed_pagamentos.php - Gerar pagamentos para reservas existentes e verificar estrutura
header("Content-Type: text/plain; charset=UTF-8");
include_once 'api/config/database.php';
$db = (new Database())->getConnection();

// Ver estrutura da tabela pagamento
echo "=== ESTRUTURA TABELA pagamento ===\n";
$cols = $db->query("SHOW COLUMNS FROM pagamento")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo "  {$c['Field']} [{$c['Type']}] null={$c['Null']} default={$c['Default']}\n";
}

// Ver reservas e calcular valores
echo "\n=== RESERVAS E VALORES ===\n";
$reservas = $db->query(
    "SELECT r.id_reserva, r.codigo_reserva, r.data_checkin, r.data_checkout,
            s.tipos_servicos AS servico, s.`preço` AS preco
     FROM reserva r
     LEFT JOIN `serviço` s ON r.`id_serviço` = s.`id_serviço`"
)->fetchAll(PDO::FETCH_ASSOC);

foreach ($reservas as $res) {
    echo "Reserva #{$res['id_reserva']} | {$res['servico']} | preco={$res['preco']}\n";
}

// Verificar colunas da tabela pagamento para saber quais usar
$colNames = array_column($cols, 'Field');
echo "\nColunas disponíveis: " . implode(', ', $colNames) . "\n";

// Inserir pagamentos para cada reserva (se não existir)
echo "\n=== CRIAR PAGAMENTOS ===\n";
$metodos = ['transferencia', 'multicaixa', 'dinheiro'];
$i = 0;
foreach ($reservas as $res) {
    // Verificar se já existe
    $exists = $db->prepare("SELECT id_pagamento FROM pagamento WHERE id_reserva = :id LIMIT 1");
    $exists->execute([':id' => $res['id_reserva']]);
    if ($exists->rowCount() > 0) {
        echo "  Reserva #{$res['id_reserva']}: pagamento ja existe\n";
        continue;
    }

    $dias = 1;
    if (!empty($res['data_checkin']) && !empty($res['data_checkout'])) {
        $d1 = new DateTime($res['data_checkin']);
        $d2 = new DateTime($res['data_checkout']);
        $dias = max(1, $d1->diff($d2)->days);
    }
    $valor = ((float)($res['preco'] ?? 100)) * $dias;
    $metodo = $metodos[$i % count($metodos)];

    // Tentar inserir com as colunas certas
    try {
        // Tentar com coluna 'metodo'
        $stmt = $db->prepare("INSERT INTO pagamento (id_reserva, metodo, valor, status) VALUES (:r, :m, :v, 'pendente')");
        $stmt->execute([':r' => $res['id_reserva'], ':m' => $metodo, ':v' => $valor]);
        echo "  OK: Reserva #{$res['id_reserva']} -> {$valor} Kz via {$metodo}\n";
    } catch (Exception $e1) {
        try {
            // Tentar com colunas diferentes se a primeira falhar
            $stmt2 = $db->prepare("INSERT INTO pagamento (id_reserva, valor) VALUES (:r, :v)");
            $stmt2->execute([':r' => $res['id_reserva'], ':v' => $valor]);
            echo "  OK (simples): Reserva #{$res['id_reserva']} -> {$valor} Kz\n";
        } catch (Exception $e2) {
            echo "  ERRO: " . $e2->getMessage() . "\n";
        }
    }
    $i++;
}

// Verificar todos os pagamentos agora
echo "\n=== PAGAMENTOS NA BD ===\n";
$all = $db->query("SELECT * FROM pagamento")->fetchAll(PDO::FETCH_ASSOC);
foreach ($all as $p) {
    echo "  Pagamento #{$p['id_pagamento']} | reserva=#{$p['id_reserva']}\n";
    foreach ($p as $k => $v) {
        if ($k !== 'id_pagamento' && $k !== 'id_reserva') echo "    $k = $v\n";
    }
}

echo "\nTotal pagamentos: " . count($all) . "\n";

// Agora ajustar a API de pagamentos para usar as colunas certas
if (!empty($all)) {
    $firstPag = $all[0];
    $hasColunaMetodo = array_key_exists('metodo', $firstPag);
    $hasColunaMPgto  = array_key_exists('metodo_pagamento', $firstPag);
    $hasColStatus    = array_key_exists('status', $firstPag);
    $hasColStatusPag = array_key_exists('status_pagamento', $firstPag);

    echo "\nColuna metodo: " . ($hasColunaMetodo ? 'metodo' : ($hasColunaMPgto ? 'metodo_pagamento' : 'DESCONHECIDA')) . "\n";
    echo "Coluna status: " . ($hasColStatus ? 'status' : ($hasColStatusPag ? 'status_pagamento' : 'DESCONHECIDA')) . "\n";
}
echo "\n=== CONCLUIDO === Elimine: " . basename(__FILE__) . "\n";
?>
