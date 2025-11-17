<?php
require_once '../auth_check.php';
require_once '../db.php';

header('Content-Type: application/json');

if ($_SESSION['user_profile'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $status = filter_input(INPUT_POST, 'status', FILTER_VALIDATE_INT);

    if (!$id || ($status !== 0 && $status !== 1)) {
        echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
        exit();
    }

    try {
        $stmt = $conn->prepare("UPDATE fornecedores SET ativo = :status WHERE id = :id");
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        echo json_encode(['success' => true, 'message' => 'Status do fornecedor atualizado.']);
    } catch (PDOException $e) {
        error_log('Erro ao atualizar status do fornecedor: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar status do fornecedor.']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
}
?>