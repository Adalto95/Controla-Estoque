<?php
// api/update_stock.php - Endpoint AJAX para atualizar o estoque
require_once '../auth_check.php';
require_once '../db.php';

header('Content-Type: application/json');

// Admins e Gerentes podem atualizar o estoque
$allowed_profiles = ['admin', 'gerente'];
if (!in_array($_SESSION['user_profile'], $allowed_profiles)) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $stock_field = filter_input(INPUT_POST, 'stock_field', FILTER_SANITIZE_STRING); // ex: estoque1, estoque2
    $new_value = filter_input(INPUT_POST, 'new_value', FILTER_VALIDATE_FLOAT);

    if (!$product_id || !$stock_field || $new_value === false || !in_array($stock_field, ['estoque1', 'estoque2', 'estoque3', 'estoque4']) || $new_value < 0) {
        error_log("Validação falhou em update_stock.php: product_id={$product_id}, stock_field={$stock_field}, new_value={$new_value}");
        echo json_encode(['success' => false, 'message' => 'Dados inválidos fornecidos.']);
        exit();
    }

    try {
        $sql = "UPDATE produtos SET {$stock_field} = :new_value WHERE id = :product_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':new_value', $new_value);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Estoque atualizado com sucesso.']);

    } catch (PDOException $e) {
        error_log("Erro PDO em update_stock.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar estoque: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
}
?>