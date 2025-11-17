<?php
require_once '../auth_check.php';
require_once '../db.php';

header('Content-Type: application/json');

$is_admin = ($_SESSION['user_profile'] === 'admin');
$perms = isset($_SESSION['permissions']) ? $_SESSION['permissions'] : [];
if (!$is_admin && (empty($perms['edit_product_name']) || $perms['edit_product_name'] != 1)) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $new_name = isset($_POST['new_name']) ? trim($_POST['new_name']) : '';

    if (!$product_id || $new_name === '' || mb_strlen($new_name) > 100) {
        echo json_encode(['success' => false, 'message' => 'Nome inválido.']);
        exit();
    }

    try {
        $stmt = $conn->prepare('UPDATE produtos SET nome = :new_name WHERE id = :product_id');
        $stmt->bindParam(':new_name', $new_name);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        echo json_encode(['success' => true, 'message' => 'Nome do produto atualizado.']);
    } catch (PDOException $e) {
        error_log('Erro ao atualizar nome do produto: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar nome do produto.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
}
?>