<?php
// scratch/migrate.php
include_once __DIR__ . '/../api/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Iniciando migrações da base de dados...\n";
    
    // 1. Modificar ENUM de tipo_usuario
    echo "Alterando ENUM tipo_usuario...\n";
    $query1 = "ALTER TABLE usuario MODIFY COLUMN tipo_usuario ENUM('utente', 'admin', 'gerente', 'recepcionista')";
    $db->exec($query1);
    echo "ENUM tipo_usuario alterado com sucesso!\n";
    
    // 2. Criar tabela recepcionista
    echo "Criando tabela recepcionista...\n";
    $query2 = "CREATE TABLE IF NOT EXISTS recepcionista (
        id_recepcionista INT(11) AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100),
        telefone VARCHAR(15),
        email VARCHAR(50),
        senha VARCHAR(32),
        id_usuario INT(11),
        FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $db->exec($query2);
    echo "Tabela recepcionista criada com sucesso!\n";
    
    // 3. Adicionar codigo_reserva à tabela reserva se não existir
    echo "Verificando coluna codigo_reserva na tabela reserva...\n";
    $checkQuery = "SHOW COLUMNS FROM reserva LIKE 'codigo_reserva'";
    $stmt = $db->query($checkQuery);
    if ($stmt->rowCount() == 0) {
        echo "Adicionando coluna codigo_reserva...\n";
        $query3 = "ALTER TABLE reserva ADD COLUMN codigo_reserva VARCHAR(20) UNIQUE";
        $db->exec($query3);
        echo "Coluna codigo_reserva adicionada com sucesso!\n";
    } else {
        echo "Coluna codigo_reserva já existe!\n";
    }
    
    // 4. Criar tabela notificacao
    echo "Criando tabela notificacao...\n";
    $query4 = "CREATE TABLE IF NOT EXISTS notificacao (
        id_notificacao INT(11) AUTO_INCREMENT PRIMARY KEY,
        id_cliente INT(11),
        mensagem TEXT,
        lida TINYINT(1) DEFAULT 0,
        data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_cliente) REFERENCES cliente(id_cliente) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $db->exec($query4);
    echo "Tabela notificacao criada com sucesso!\n";
    
    // 5. Inserir dados padrão de teste para Recepcionista se não existirem
    echo "Verificando utilizador recepcionista de teste...\n";
    $checkUser = "SELECT id_usuario FROM usuario WHERE email = 'recepcao@sana.com' LIMIT 1";
    $stmt = $db->query($checkUser);
    if ($stmt->rowCount() == 0) {
        echo "Criando utilizador de recepção de teste...\n";
        
        $db->beginTransaction();
        
        $qUser = "INSERT INTO usuario (nome, email, telefone, senha, tipo_usuario) VALUES ('Carla Recepcionista', 'recepcao@sana.com', '912345678', MD5('recep123'), 'recepcionista')";
        $db->exec($qUser);
        $userId = $db->lastInsertId();
        
        $qRecep = "INSERT INTO recepcionista (nome, telefone, email, senha, id_usuario) VALUES ('Carla Recepcionista', '912345678', 'recepcao@sana.com', MD5('recep123'), :id_usuario)";
        $stmtRecep = $db->prepare($qRecep);
        $stmtRecep->execute([':id_usuario' => $userId]);
        
        $db->commit();
        echo "Recepcionista de teste criada: recepcao@sana.com / recep123\n";
    } else {
        echo "Recepcionista de teste já existe!\n";
    }
    
    echo "Migrações concluídas com sucesso!\n";
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "ERRO DURANTE AS MIGRAÇÕES: " . $e->getMessage() . "\n";
}
