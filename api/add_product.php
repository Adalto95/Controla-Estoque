<?php
// api/add_product.php - Endpoint AJAX para adicionar produto
require_once '../auth_check.php'; // Ajuste o caminho conforme a estrutura de pastas
require_once '../db.php';         // Ajuste o caminho

header('Content-Type: application/json');

// Admins e Gerentes podem adicionar produtos
$allowed_profiles = ['admin', 'gerente'];
if (!in_array($_SESSION['user_profile'], $allowed_profiles)) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fornecedor_id = filter_input(INPUT_POST, 'fornecedor_id', FILTER_VALIDATE_INT);
    $nome_produto = trim(filter_input(INPUT_POST, 'nome_produto', FILTER_SANITIZE_STRING));
    $estoque1 = filter_input(INPUT_POST, 'estoque1', FILTER_VALIDATE_FLOAT);
    $estoque2 = filter_input(INPUT_POST, 'estoque2', FILTER_VALIDATE_FLOAT);
    $estoque3 = filter_input(INPUT_POST, 'estoque3', FILTER_VALIDATE_FLOAT);
    $estoque4 = filter_input(INPUT_POST, 'estoque4', FILTER_VALIDATE_FLOAT);

    // Validação de inputs
    if (!$fornecedor_id || empty($nome_produto) || $estoque1 === false || $estoque2 === false || $estoque3 === false || $estoque4 === false || $estoque1 < 0 || $estoque2 < 0 || $estoque3 < 0 || $estoque4 < 0) {
        echo json_encode(['success' => false, 'message' => 'Dados inválidos fornecidos.']);
        exit();
    }

    try {
        $stmt = $conn->prepare("INSERT INTO produtos (fornecedor_id, nome, estoque1, estoque2, estoque3, estoque4) 
                                   VALUES (:fornecedor_id, :nome, :estoque1, :estoque2, :estoque3, :estoque4)");
        $stmt->bindParam(':fornecedor_id', $fornecedor_id);
        $stmt->bindParam(':nome', $nome_produto);
        $stmt->bindParam(':estoque1', $estoque1);
        $stmt->bindParam(':estoque2', $estoque2);
        $stmt->bindParam(':estoque3', $estoque3);
        $stmt->bindParam(':estoque4', $estoque4);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Produto adicionado com sucesso!']);

    } catch (PDOException $e) {
        error_log("Erro ao adicionar produto: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro ao adicionar produto: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
}
?>