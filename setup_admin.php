<?php
// Acesse via: http://localhost/tcc_project/setup_admin.php
// Este script regista o admin padrão na base de dados.
header("Content-Type: text/plain; charset=UTF-8");

include_once 'api/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $email    = 'admin@sana.com';
    $password = 'admin123';
    $hashed   = md5($password);

    // Verificar se já existe
    $check = $db->prepare("SELECT id_usuario FROM usuario WHERE email = :email LIMIT 1");
    $check->execute([':email' => $email]);

    if ($check->rowCount() > 0) {
        $row = $check->fetch(PDO::FETCH_ASSOC);
        // Actualizar senha para garantir que é MD5
        $upd = $db->prepare("UPDATE usuario SET senha = :senha WHERE email = :email");
        $upd->execute([':senha' => $hashed, ':email' => $email]);
        echo "✅ Admin já existe (id: {$row['id_usuario']}). Senha actualizada para MD5.\n";
        echo "Email: $email\n";
        echo "Senha: $password\n";
    } else {
        $db->beginTransaction();

        $ins1 = $db->prepare("INSERT INTO usuario (nome, email, telefone, senha, tipo_usuario) VALUES ('Administrador Geral', :email, '900000000', :senha, 'admin')");
        $ins1->execute([':email' => $email, ':senha' => $hashed]);
        $id = $db->lastInsertId();

        $ins2 = $db->prepare("INSERT INTO administrador (nome, email, senha, nivel_acesso, id_usuario) VALUES ('Administrador Geral', :email, :senha, 'Geral', :id)");
        $ins2->execute([':email' => $email, ':senha' => $hashed, ':id' => $id]);

        $db->commit();
        echo "✅ Admin registado com sucesso!\n";
        echo "ID: $id\n";
        echo "Email: $email\n";
        echo "Senha: $password\n";
    }

    echo "\n--- TODOS OS UTILIZADORES ---\n";
    $all = $db->query("SELECT id_usuario, nome, email, tipo_usuario FROM usuario ORDER BY id_usuario");
    while ($u = $all->fetch(PDO::FETCH_ASSOC)) {
        echo "#{$u['id_usuario']} | {$u['tipo_usuario']} | {$u['email']} | {$u['nome']}\n";
    }

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}
?>
