<?php
// api/update_stock.php - Endpoint AJAX para atualizar o estoque
require_once '../auth_check.php'; // Ajuste o caminho
require_once '../db.php';         // Ajuste o caminho

header('Content-Type: application/json');

// Debug temporário (POST e SESSION)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("DEBUG POST: " . print_r($_POST, true));
    error_log("DEBUG SESSION: " . print_r($_SESSION, true));
}


// Apenas admins podem atualizar o estoque
if (!in_array($_SESSION['user_profile'], ['admin', 'gerente'])) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $stock_field = filter_input(INPUT_POST, 'stock_field', FILTER_SANITIZE_STRING); // ex: estoque1, estoque2
    $new_value = str_replace(',', '.', $_POST['new_value']); // aceita vírgula ou ponto
    $new_value = floatval($new_value);

    // Validação básica dos inputs
    if (!$product_id || !$stock_field || !in_array($stock_field, ['estoque1', 'estoque2', 'estoque3', 'estoque4']) || $new_value < 0) {
        error_log("Validação falhou em update_stock.php: product_id={$product_id}, stock_field={$stock_field}, new_value={$new_value}");
        echo json_encode(['success' => false, 'message' => 'Dados inválidos fornecidos. Certifique-se de que o estoque é um número não negativo.']);
        exit();
    }

    try {
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Constrói a query SQL dinamicamente para atualizar o campo de estoque correto
        $sql = "UPDATE produtos SET {$stock_field} = :new_value WHERE id = :product_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':new_value', $new_value, PDO::PARAM_STR);
        $stmt->bindValue(':product_id', $product_id, PDO::PARAM_INT);

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
