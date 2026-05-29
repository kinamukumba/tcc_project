<?php
// api/admin/usuarios.php
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

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'GET') {
    // Listar apenas utentes e recepcionistas
    $query = "SELECT id_usuario, nome, email, telefone, tipo_usuario FROM usuario WHERE tipo_usuario IN ('utente', 'recepcionista') ORDER BY tipo_usuario ASC, nome ASC";
    $stmt = $db->query($query);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode($usuarios);
} 
else if ($method == 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->nome) && !empty($data->email) && !empty($data->telefone) && !empty($data->senha) && !empty($data->tipo_usuario)) {
        
        if ($data->tipo_usuario == 'admin' || $data->tipo_usuario == 'gerente') {
            http_response_code(403);
            echo json_encode(array("message" => "Não é permitido cadastrar administradores ou gerentes através deste painel."));
            exit;
        }
        try {
            $db->beginTransaction();
            
            // Verificar email duplicado
            $qCheck = "SELECT id_usuario FROM usuario WHERE email = :email LIMIT 1";
            $sCheck = $db->prepare($qCheck);
            $sCheck->execute([':email' => $data->email]);
            if ($sCheck->rowCount() > 0) {
                throw new Exception("E-mail já está em uso por outro utilizador.");
            }
            
            // 1. Inserir na tabela geral usuario
            $qUser = "INSERT INTO usuario (nome, email, telefone, senha, tipo_usuario) 
                      VALUES (:nome, :email, :tel, MD5(:senha), :tipo)";
            $sUser = $db->prepare($qUser);
            $sUser->execute([
                ':nome' => $data->nome,
                ':email' => $data->email,
                ':tel' => $data->telefone,
                ':senha' => $data->senha,
                ':tipo' => $data->tipo_usuario
            ]);
            $id_usuario = $db->lastInsertId();
            
            // 2. Inserir na tabela secundária correspondente
            if ($data->tipo_usuario == 'utente') {
                $qSub = "INSERT INTO cliente (nome, email, telemovel, bi, senha, id_usuario) 
                         VALUES (:nome, :email, :tel, :bi, MD5(:senha), :id_usuario)";
                $sSub = $db->prepare($qSub);
                $sSub->execute([
                    ':nome' => $data->nome,
                    ':email' => $data->email,
                    ':tel' => $data->telefone,
                    ':bi' => isset($data->bi) ? $data->bi : 'S/N',
                    ':senha' => $data->senha,
                    ':id_usuario' => $id_usuario
                ]);
            } 
            else if ($data->tipo_usuario == 'admin') {
                $qSub = "INSERT INTO administrador (nome, email, senha, nivel_acesso, id_usuario) 
                         VALUES (:nome, :email, MD5(:senha), 'total', :id_usuario)";
                $sSub = $db->prepare($qSub);
                $sSub->execute([
                    ':nome' => $data->nome,
                    ':email' => $data->email,
                    ':senha' => $data->senha,
                    ':id_usuario' => $id_usuario
                ]);
            } 
            else if ($data->tipo_usuario == 'gerente') {
                $qSub = "INSERT INTO gestor (nome, telefone, email, senha, nivel_acesso, id_usuario) 
                         VALUES (:nome, :tel, :email, MD5(:senha), 'geral', :id_usuario)";
                $sSub = $db->prepare($qSub);
                $sSub->execute([
                    ':nome' => $data->nome,
                    ':tel' => $data->telefone,
                    ':email' => $data->email,
                    ':senha' => $data->senha,
                    ':id_usuario' => $id_usuario
                ]);
            } 
            else if ($data->tipo_usuario == 'recepcionista') {
                $qSub = "INSERT INTO recepcionista (nome, telefone, email, senha, id_usuario) 
                         VALUES (:nome, :tel, :email, MD5(:senha), :id_usuario)";
                $sSub = $db->prepare($qSub);
                $sSub->execute([
                    ':nome' => $data->nome,
                    ':tel' => $data->telefone,
                    ':email' => $data->email,
                    ':senha' => $data->senha,
                    ':id_usuario' => $id_usuario
                ]);
            }
            
            $db->commit();
            http_response_code(201);
            echo json_encode(array("message" => "Utilizador criado com sucesso!"));
        } catch (Exception $e) {
            $db->rollBack();
            http_response_code(500);
            echo json_encode(array("message" => "Erro ao criar utilizador: " . $e->getMessage()));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Dados incompletos."));
    }
} 
else if ($method == 'PUT') {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->id_usuario) && !empty($data->nome) && !empty($data->email) && !empty($data->telefone)) {
        // Verificar tipo do utilizador para impedir alteração de admin ou gerente
        $qType = "SELECT tipo_usuario FROM usuario WHERE id_usuario = :id LIMIT 1";
        $sType = $db->prepare($qType);
        $sType->execute([':id' => $data->id_usuario]);
        $userTypeRow = $sType->fetch(PDO::FETCH_ASSOC);
        if (!$userTypeRow) {
            http_response_code(404);
            echo json_encode(array("message" => "Utilizador não encontrado."));
            exit;
        }
        $userType = $userTypeRow['tipo_usuario'];
        if ($userType == 'admin' || $userType == 'gerente') {
            http_response_code(403);
            echo json_encode(array("message" => "Não é permitido alterar administradores ou gerentes através deste painel."));
            exit;
        }

        try {
            $db->beginTransaction();
            
            // Atualizar na tabela geral
            if (!empty($data->senha)) {
                $qUser = "UPDATE usuario SET nome = :nome, email = :email, telefone = :tel, senha = MD5(:senha) WHERE id_usuario = :id";
                $params = [
                    ':nome' => $data->nome,
                    ':email' => $data->email,
                    ':tel' => $data->telefone,
                    ':senha' => $data->senha,
                    ':id' => $data->id_usuario
                ];
            } else {
                $qUser = "UPDATE usuario SET nome = :nome, email = :email, telefone = :tel WHERE id_usuario = :id";
                $params = [
                    ':nome' => $data->nome,
                    ':email' => $data->email,
                    ':tel' => $data->telefone,
                    ':id' => $data->id_usuario
                ];
            }
            $sUser = $db->prepare($qUser);
            $sUser->execute($params);
            
            // Identificar tipo de usuário para atualizar tabela secundária
            $qType = "SELECT tipo_usuario FROM usuario WHERE id_usuario = :id LIMIT 1";
            $sType = $db->prepare($qType);
            $sType->execute([':id' => $data->id_usuario]);
            $userType = $sType->fetch(PDO::FETCH_ASSOC)['tipo_usuario'];
            
            if ($userType == 'utente') {
                $qSub = !empty($data->senha) 
                    ? "UPDATE cliente SET nome = :nome, email = :email, telemovel = :tel, bi = :bi, senha = MD5(:senha) WHERE id_usuario = :id" 
                    : "UPDATE cliente SET nome = :nome, email = :email, telemovel = :tel, bi = :bi WHERE id_usuario = :id";
                $sSub = $db->prepare($qSub);
                $subParams = [':nome' => $data->nome, ':email' => $data->email, ':tel' => $data->telefone, ':bi' => isset($data->bi) ? $data->bi : 'S/N', ':id' => $data->id_usuario];
                if (!empty($data->senha)) $subParams[':senha'] = $data->senha;
                $sSub->execute($subParams);
            } 
            else if ($userType == 'admin') {
                $qSub = !empty($data->senha) 
                    ? "UPDATE administrador SET nome = :nome, email = :email, senha = MD5(:senha) WHERE id_usuario = :id" 
                    : "UPDATE administrador SET nome = :nome, email = :email WHERE id_usuario = :id";
                $sSub = $db->prepare($qSub);
                $subParams = [':nome' => $data->nome, ':email' => $data->email, ':id' => $data->id_usuario];
                if (!empty($data->senha)) $subParams[':senha'] = $data->senha;
                $sSub->execute($subParams);
            } 
            else if ($userType == 'gerente') {
                $qSub = !empty($data->senha) 
                    ? "UPDATE gestor SET nome = :nome, email = :email, telefone = :tel, senha = MD5(:senha) WHERE id_usuario = :id" 
                    : "UPDATE gestor SET nome = :nome, email = :email, telefone = :tel WHERE id_usuario = :id";
                $sSub = $db->prepare($qSub);
                $subParams = [':nome' => $data->nome, ':email' => $data->email, ':tel' => $data->telefone, ':id' => $data->id_usuario];
                if (!empty($data->senha)) $subParams[':senha'] = $data->senha;
                $sSub->execute($subParams);
            } 
            else if ($userType == 'recepcionista') {
                $qSub = !empty($data->senha) 
                    ? "UPDATE recepcionista SET nome = :nome, email = :email, telefone = :tel, senha = MD5(:senha) WHERE id_usuario = :id" 
                    : "UPDATE recepcionista SET nome = :nome, email = :email, telefone = :tel WHERE id_usuario = :id";
                $sSub = $db->prepare($qSub);
                $subParams = [':nome' => $data->nome, ':email' => $data->email, ':tel' => $data->telefone, ':id' => $data->id_usuario];
                if (!empty($data->senha)) $subParams[':senha'] = $data->senha;
                $sSub->execute($subParams);
            }
            
            $db->commit();
            http_response_code(200);
            echo json_encode(array("message" => "Utilizador atualizado com sucesso!"));
        } catch (Exception $e) {
            $db->rollBack();
            http_response_code(500);
            echo json_encode(array("message" => "Erro ao atualizar utilizador: " . $e->getMessage()));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Dados incompletos."));
    }
} 
else if ($method == 'DELETE') {
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    
    if ($id) {
        // Impedir que o próprio administrador logado se auto-exclua
        if ($id == $_SESSION['user_id']) {
            http_response_code(400);
            echo json_encode(array("message" => "Não é permitido excluir o próprio utilizador ativo da sessão."));
            exit;
        }

        // Impedir exclusão de admin ou gerente
        $qType = "SELECT tipo_usuario FROM usuario WHERE id_usuario = :id LIMIT 1";
        $sType = $db->prepare($qType);
        $sType->execute([':id' => $id]);
        $userTypeRow = $sType->fetch(PDO::FETCH_ASSOC);
        if ($userTypeRow) {
            $userType = $userTypeRow['tipo_usuario'];
            if ($userType == 'admin' || $userType == 'gerente') {
                http_response_code(403);
                echo json_encode(array("message" => "Não é permitido excluir administradores ou gerentes através deste painel."));
                exit;
            }
        }
        
        $query = "DELETE FROM usuario WHERE id_usuario = :id";
        $stmt = $db->prepare($query);
        if ($stmt->execute([':id' => $id])) {
            http_response_code(200);
            echo json_encode(array("message" => "Utilizador excluído com sucesso."));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Erro ao excluir utilizador."));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "ID não fornecido."));
    }
} else {
    http_response_code(405);
    echo json_encode(array("message" => "Método não permitido."));
}
?>
