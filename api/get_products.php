<?php
// api/get_products.php - Endpoint AJAX para buscar produtos
require_once '../auth_check.php';
require_once '../db.php';

header('Content-Type: application/json');

$is_admin = ($_SESSION['user_profile'] === 'admin');
$show_inactive = filter_input(INPUT_GET, 'show_inactive', FILTER_VALIDATE_INT) == 1;

$supplier_id = filter_input(INPUT_GET, 'supplier_id', FILTER_SANITIZE_STRING); // 'all' ou ID
$search_term = trim(filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING));

$products = [];

try {
    $sql = "SELECT p.id, p.nome AS produto_nome, p.estoque1, p.estoque2, p.estoque3, p.estoque4, p.ativo, f.nome AS fornecedor_nome
            FROM produtos p
            JOIN fornecedores f ON p.fornecedor_id = f.id";
    
    $conditions = [];
    $params = [];

    if ($supplier_id !== 'all' && filter_var($supplier_id, FILTER_VALIDATE_INT)) {
        $conditions[] = "p.fornecedor_id = :supplier_id";
        $params[':supplier_id'] = (int)$supplier_id;
    }

    if (!empty($search_term)) {
        $conditions[] = "(p.nome LIKE :search_term OR f.nome LIKE :search_term)";
        $params[':search_term'] = '%' . $search_term . '%';
    }
    
    // Adiciona a condição de 'ativo' para todos exceto admin que optou por ver inativos
    if (!$is_admin || ($is_admin && !$show_inactive)) {
        $conditions[] = "p.ativo = 1";
    }

    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    $sql .= " ORDER BY p.nome ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    echo json_encode(['success' => true, 'products' => $products]);

} catch (PDOException $e) {
    error_log("Erro ao buscar produtos (API): " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno ao buscar produtos.']);
}
?>