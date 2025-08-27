<?php
// api/add_user.php - Endpoint AJAX para adicionar um novo usuário (apenas para Admin)
require_once '../auth_check.php';
require_once '../db.php';

header('Content-Type: application/json');

if ($_SESSION['user_profile'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL));
    $password = trim($_POST['password']);
    $profile = trim(filter_input(INPUT_POST, 'profile', FILTER_SANITIZE_STRING));

    if (empty($name) || empty($email) || empty($password) || !in_array($profile, ['gerente', 'vendedor'])) {
        echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
        exit();
    }
    
    // MD5 para a senha
    $password_hash = MD5($password);
    
    try {
        $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha, perfil) VALUES (:name, :email, :password_hash, :profile)");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password_hash', $password_hash);
        $stmt->bindParam(':profile', $profile);
        $stmt->execute();
        echo json_encode(['success' => true, 'message' => 'Usuário adicionado com sucesso.']);
    } catch (PDOException $e) {
        error_log("Erro ao adicionar usuário: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro ao adicionar usuário: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
}
?>