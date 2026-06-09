<?php
// api/admin/usuarios.php — CRUD completo para utentes, recepcionistas e gerentes
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
$method = $_SERVER['REQUEST_METHOD'];

// ── GET ──────────────────────────────────────────────────────────────────────
if ($method === 'GET') {
    $tipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : null;
    $id   = isset($_GET['id'])   ? (int)$_GET['id']   : null;

    $allowed = ['utente', 'recepcionista', 'gerente'];

    if ($id) {
        $stmt = $db->prepare("SELECT id_usuario, nome, email, telefone, tipo_usuario FROM usuario WHERE id_usuario = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: []);
    } elseif ($tipo && in_array($tipo, $allowed)) {
        $stmt = $db->prepare("SELECT id_usuario, nome, email, telefone, tipo_usuario FROM usuario WHERE tipo_usuario = :tipo ORDER BY nome ASC");
        $stmt->execute([':tipo' => $tipo]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } else {
        $stmt = $db->query("SELECT id_usuario, nome, email, telefone, tipo_usuario FROM usuario WHERE tipo_usuario IN ('utente','recepcionista','gerente') ORDER BY tipo_usuario ASC, nome ASC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    exit;
}

// ── POST (criar) ──────────────────────────────────────────────────────────────
if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    $nome  = isset($data->nome)         ? trim($data->nome)         : '';
    $email = isset($data->email)        ? trim($data->email)        : '';
    $tel   = isset($data->telefone)     ? trim($data->telefone)     : '';
    $senha = isset($data->senha)        ? trim($data->senha)        : '';
    $tipo  = isset($data->tipo_usuario) ? trim($data->tipo_usuario) : '';

    // Admin nao pode criar outro admin por aqui
    if ($tipo === 'admin') {
        http_response_code(403);
        echo json_encode(["message" => "Nao e permitido criar administradores por este painel."]);
        exit;
    }

    if (!$nome || !$email || !$senha || !$tipo) {
        http_response_code(400);
        echo json_encode(["message" => "Dados incompletos. Nome, email, senha e tipo sao obrigatorios."]);
        exit;
    }

    // Verificar email duplicado
    $chk = $db->prepare("SELECT id_usuario FROM usuario WHERE email = :email LIMIT 1");
    $chk->execute([':email' => $email]);
    if ($chk->rowCount() > 0) {
        http_response_code(409);
        echo json_encode(["message" => "Este e-mail ja esta em uso por outro utilizador."]);
        exit;
    }

    try {
        $db->beginTransaction();

        // 1. Inserir na tabela principal usuario
        $s1 = $db->prepare("INSERT INTO usuario (nome, email, telefone, senha, tipo_usuario) VALUES (:nome, :email, :tel, MD5(:senha), :tipo)");
        $s1->execute([':nome' => $nome, ':email' => $email, ':tel' => $tel, ':senha' => $senha, ':tipo' => $tipo]);
        $uid = $db->lastInsertId();

        // 2. Inserir na tabela secundaria do perfil
        if ($tipo === 'utente') {
            $s2 = $db->prepare("INSERT INTO cliente (nome, email, telemovel, bi, senha, id_usuario) VALUES (:nome,:email,:tel,'S/N',MD5(:senha),:id)");
            $s2->execute([':nome'=>$nome,':email'=>$email,':tel'=>$tel,':senha'=>$senha,':id'=>$uid]);
        } elseif ($tipo === 'recepcionista') {
            $s2 = $db->prepare("INSERT INTO recepcionista (nome, telefone, email, senha, id_usuario) VALUES (:nome,:tel,:email,MD5(:senha),:id)");
            $s2->execute([':nome'=>$nome,':tel'=>$tel,':email'=>$email,':senha'=>$senha,':id'=>$uid]);
        } elseif ($tipo === 'gerente') {
            $s2 = $db->prepare("INSERT INTO gestor (nome, telefone, email, senha, nivel_acesso, id_usuario) VALUES (:nome,:tel,:email,MD5(:senha),'geral',:id)");
            $s2->execute([':nome'=>$nome,':tel'=>$tel,':email'=>$email,':senha'=>$senha,':id'=>$uid]);
        }

        $db->commit();
        http_response_code(201);
        echo json_encode(["message" => ucfirst($tipo) . " criado com sucesso.", "id_usuario" => $uid]);
    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(["message" => "Erro ao criar utilizador: " . $e->getMessage()]);
    }
    exit;
}

// ── PUT (editar) ──────────────────────────────────────────────────────────────
if ($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"));

    $id    = isset($data->id_usuario) ? (int)$data->id_usuario : 0;
    $nome  = isset($data->nome)       ? trim($data->nome)       : '';
    $email = isset($data->email)      ? trim($data->email)      : '';
    $tel   = isset($data->telefone)   ? trim($data->telefone)   : '';
    $senha = isset($data->senha)      ? trim($data->senha)      : '';

    if (!$id || !$nome || !$email) {
        http_response_code(400);
        echo json_encode(["message" => "Dados incompletos."]);
        exit;
    }

    // Verificar tipo actual do utilizador
    $chkType = $db->prepare("SELECT tipo_usuario FROM usuario WHERE id_usuario = :id LIMIT 1");
    $chkType->execute([':id' => $id]);
    $row = $chkType->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode(["message" => "Utilizador nao encontrado."]);
        exit;
    }

    if ($row['tipo_usuario'] === 'admin') {
        http_response_code(403);
        echo json_encode(["message" => "Nao e permitido editar administradores por este painel."]);
        exit;
    }

    try {
        $db->beginTransaction();
        $tipo = $row['tipo_usuario'];

        // Atualizar tabela principal
        if ($senha) {
            $s = $db->prepare("UPDATE usuario SET nome=:nome, email=:email, telefone=:tel, senha=MD5(:senha) WHERE id_usuario=:id");
            $s->execute([':nome'=>$nome,':email'=>$email,':tel'=>$tel,':senha'=>$senha,':id'=>$id]);
        } else {
            $s = $db->prepare("UPDATE usuario SET nome=:nome, email=:email, telefone=:tel WHERE id_usuario=:id");
            $s->execute([':nome'=>$nome,':email'=>$email,':tel'=>$tel,':id'=>$id]);
        }

        // Atualizar tabela secundaria
        if ($tipo === 'utente') {
            if ($senha) {
                $s2 = $db->prepare("UPDATE cliente SET nome=:nome,email=:email,telemovel=:tel,senha=MD5(:senha) WHERE id_usuario=:id");
                $s2->execute([':nome'=>$nome,':email'=>$email,':tel'=>$tel,':senha'=>$senha,':id'=>$id]);
            } else {
                $s2 = $db->prepare("UPDATE cliente SET nome=:nome,email=:email,telemovel=:tel WHERE id_usuario=:id");
                $s2->execute([':nome'=>$nome,':email'=>$email,':tel'=>$tel,':id'=>$id]);
            }
        } elseif ($tipo === 'recepcionista') {
            if ($senha) {
                $s2 = $db->prepare("UPDATE recepcionista SET nome=:nome,email=:email,telefone=:tel,senha=MD5(:senha) WHERE id_usuario=:id");
                $s2->execute([':nome'=>$nome,':email'=>$email,':tel'=>$tel,':senha'=>$senha,':id'=>$id]);
            } else {
                $s2 = $db->prepare("UPDATE recepcionista SET nome=:nome,email=:email,telefone=:tel WHERE id_usuario=:id");
                $s2->execute([':nome'=>$nome,':email'=>$email,':tel'=>$tel,':id'=>$id]);
            }
        } elseif ($tipo === 'gerente') {
            if ($senha) {
                $s2 = $db->prepare("UPDATE gestor SET nome=:nome,email=:email,telefone=:tel,senha=MD5(:senha) WHERE id_usuario=:id");
                $s2->execute([':nome'=>$nome,':email'=>$email,':tel'=>$tel,':senha'=>$senha,':id'=>$id]);
            } else {
                $s2 = $db->prepare("UPDATE gestor SET nome=:nome,email=:email,telefone=:tel WHERE id_usuario=:id");
                $s2->execute([':nome'=>$nome,':email'=>$email,':tel'=>$tel,':id'=>$id]);
            }
        }

        $db->commit();
        http_response_code(200);
        echo json_encode(["message" => ucfirst($tipo) . " actualizado com sucesso."]);
    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(["message" => "Erro ao actualizar: " . $e->getMessage()]);
    }
    exit;
}

// ── DELETE ────────────────────────────────────────────────────────────────────
if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if (!$id) {
        http_response_code(400);
        echo json_encode(["message" => "ID nao fornecido."]);
        exit;
    }

    if ($id === (int)$_SESSION['user_id']) {
        http_response_code(400);
        echo json_encode(["message" => "Nao pode eliminar a sua propria conta."]);
        exit;
    }

    $chk = $db->prepare("SELECT tipo_usuario FROM usuario WHERE id_usuario=:id LIMIT 1");
    $chk->execute([':id' => $id]);
    $row = $chk->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode(["message" => "Utilizador nao encontrado."]);
        exit;
    }

    if ($row['tipo_usuario'] === 'admin') {
        http_response_code(403);
        echo json_encode(["message" => "Nao e permitido eliminar administradores por este painel."]);
        exit;
    }

    $stmt = $db->prepare("DELETE FROM usuario WHERE id_usuario=:id");
    if ($stmt->execute([':id' => $id])) {
        http_response_code(200);
        echo json_encode(["message" => ucfirst($row['tipo_usuario']) . " eliminado com sucesso."]);
    } else {
        http_response_code(500);
        echo json_encode(["message" => "Erro ao eliminar."]);
    }
    exit;
}

http_response_code(405);
echo json_encode(["message" => "Metodo nao permitido."]);
?>