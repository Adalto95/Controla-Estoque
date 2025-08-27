<?php
// api/update_user_password.php - Endpoint para alterar senha de usuário (apenas Admin)
require_once '../auth_check.php';
require_once '../db.php';

header('Content-Type: application/json');

if ($_SESSION['user_profile'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $new_password = trim($_POST['new_password']);

    if (!$user_id || empty($new_password)) {
        echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
        exit();
    }
    
    // MD5 para a nova senha
    $password_hash = MD5($new_password);
    
    try {
        $stmt = $conn->prepare("UPDATE usuarios SET senha = :password_hash WHERE id = :user_id AND perfil != 'admin'");
        $stmt->bindParam(':password_hash', $password_hash);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Senha alterada com sucesso.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Não foi possível alterar a senha.']);
        }
    } catch (PDOException $e) {
        error_log("Erro ao alterar senha do usuário: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro ao alterar senha: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
}
?>