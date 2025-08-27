<?php
// api/get_suppliers.php - Endpoint AJAX para buscar fornecedores (todos)
// Este arquivo não está mais em uso, a busca é feita por search_suppliers.php
// Mas foi mantido para compatibilidade, se necessário.
require_once '../auth_check.php';
require_once '../db.php';

header('Content-Type: application/json');

$suppliers = [];

try {
    $stmt = $conn->query("SELECT id, nome FROM fornecedores ORDER BY nome ASC");
    $suppliers = $stmt->fetchAll();

    echo json_encode(['success' => true, 'suppliers' => $suppliers]);

} catch (PDOException $e) {
    error_log("Erro ao buscar fornecedores (API): " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno ao buscar fornecedores.']);
}
?>