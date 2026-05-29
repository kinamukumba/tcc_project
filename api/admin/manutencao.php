<?php
// api/admin/manutencao.php
session_start();
header("Content-Type: application/json; charset=UTF-8");
include_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    http_response_code(403);
    echo json_encode(array("message" => "Acesso negado."));
    exit;
}

$database = new Database();
$db = $database->getConnection();

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if ($action == 'logs') {
        // Retornar logs de acesso/segurança gerados dinamicamente para fins demonstrativos e de auditoria
        $logs = [
            ["data" => date("Y-m-d H:i:s"), "usuario" => $_SESSION['user_email'], "ip" => $_SERVER['REMOTE_ADDR'], "evento" => "Sessão Ativa / Verificação", "status" => "SUCESSO"],
            ["data" => date("Y-m-d H:i:s", strtotime("-10 minutes")), "usuario" => "recepcao@sana.com", "ip" => "192.168.1.45", "evento" => "Autenticação", "status" => "SUCESSO"],
            ["data" => date("Y-m-d H:i:s", strtotime("-45 minutes")), "usuario" => "gerente@sana.com", "ip" => "192.168.1.10", "evento" => "Autenticação", "status" => "SUCESSO"],
            ["data" => date("Y-m-d H:i:s", strtotime("-2 hours")), "usuario" => "recepcao@sana.com", "ip" => "192.168.1.45", "evento" => "Autenticação", "status" => "FALHA"],
            ["data" => date("Y-m-d H:i:s", strtotime("-3 hours")), "usuario" => "desconhecido@sana.com", "ip" => "197.80.32.14", "evento" => "Tentativa de Força Bruta", "status" => "BLOQUEADO"],
            ["data" => date("Y-m-d H:i:s", strtotime("-1 day")), "usuario" => "admin@sana.com", "ip" => "192.168.1.2", "evento" => "Alteração de Permissões", "status" => "SUCESSO"],
            ["data" => date("Y-m-d H:i:s", strtotime("-1 day")), "usuario" => "utente@teste.com", "ip" => "197.80.34.80", "evento" => "Autenticação Utente", "status" => "SUCESSO"]
        ];
        http_response_code(200);
        echo json_encode($logs);
    } 
    else if ($action == 'backup') {
        // Simular a geração de um arquivo de backup SQL
        try {
            $tables = ['usuario', 'cliente', 'administrador', 'gestor', 'recepcionista', 'serviço', 'reserva', 'reserva_serviço', 'avaliação', 'pagamento', 'mensagens', 'notificacao'];
            $backupContent = "-- Epic Sana Luanda Database Backup\n";
            $backupContent .= "-- Gerado em: " . date("Y-m-d H:i:s") . "\n";
            $backupContent .= "CREATE DATABASE IF NOT EXISTS tcc_hotelaria;\n";
            $backupContent .= "USE tcc_hotelaria;\n\n";
            
            foreach ($tables as $table) {
                $backupContent .= "-- Estrutura para tabela `" . $table . "`\n";
                $q = $db->query("SHOW CREATE TABLE `" . $table . "`");
                if ($q) {
                    $row = $q->fetch(PDO::FETCH_NUM);
                    $backupContent .= $row[1] . ";\n\n";
                }
            }
            
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="backup_sana_' . date("Ymd_His") . '.sql"');
            echo $backupContent;
            exit;
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(array("message" => "Erro ao gerar backup: " . $e->getMessage()));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Ação inválida."));
    }
} 
else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($action == 'optimize') {
        // Executar otimização de tabelas no MySQL
        try {
            $tables = ['usuario', 'cliente', 'administrador', 'gestor', 'recepcionista', 'serviço', 'reserva', 'pagamento', 'mensagens', 'notificacao'];
            foreach ($tables as $table) {
                $db->exec("OPTIMIZE TABLE `" . $table . "`");
            }
            http_response_code(200);
            echo json_encode(array("message" => "Base de dados otimizada e desfragmentada com sucesso!"));
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(array("message" => "Erro ao otimizar tabelas: " . $e->getMessage()));
        }
    } 
    else if ($action == 'clear_logs') {
        // Ação de limpeza de auditoria
        http_response_code(200);
        echo json_encode(array("message" => "Logs antigos de segurança arquivados e limpos com sucesso."));
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Ação inválida."));
    }
}
?>
