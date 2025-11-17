<?php
// api/add_supplier.php - Endpoint AJAX para adicionar fornecedor
require_once '../auth_check.php'; // Ajuste o caminho
require_once '../db.php';         // Ajuste o caminho

header('Content-Type: application/json');

// Admins e Gerentes podem adicionar fornecedores
$is_admin = ($_SESSION['user_profile'] === 'admin');
$perms = isset($_SESSION['permissions']) ? $_SESSION['permissions'] : [];
if (!$is_admin && (empty($perms['add_supplier']) || $perms['add_supplier'] != 1)) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_fornecedor = isset($_POST['nome_fornecedor']) ? trim($_POST['nome_fornecedor']) : '';

    // Validação
    if ($nome_fornecedor === '' || mb_strlen($nome_fornecedor) > 100) {
        echo json_encode(['success' => false, 'message' => 'Nome do fornecedor inválido.']);
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