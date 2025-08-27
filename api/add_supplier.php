<?php
// api/add_supplier.php - Endpoint AJAX para adicionar fornecedor
require_once '../auth_check.php'; // Ajuste o caminho
require_once '../db.php';         // Ajuste o caminho

header('Content-Type: application/json');

// Admins e Gerentes podem adicionar fornecedores
$allowed_profiles = ['admin', 'gerente'];
if (!in_array($_SESSION['user_profile'], $allowed_profiles)) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_fornecedor = trim(filter_input(INPUT_POST, 'nome_fornecedor', FILTER_SANITIZE_STRING));

    // Validação
    if (empty($nome_fornecedor)) {
        echo json_encode(['success' => false, 'message' => 'Nome do fornecedor não pode ser vazio.']);
        exit();
    }

    try {
        $stmt = $conn->prepare("INSERT INTO fornecedores (nome) VALUES (:nome)");
        $stmt->bindParam(':nome', $nome_fornecedor);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Fornecedor adicionado com sucesso!']);

    } catch (PDOException $e) {
        error_log("Erro ao adicionar fornecedor: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro ao adicionar fornecedor: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
}
?>