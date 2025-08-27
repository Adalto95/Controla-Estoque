<?php
// api/search_suppliers.php - Endpoint AJAX para buscar fornecedores com base em um termo de pesquisa
require_once '../auth_check.php';
require_once '../db.php';

header('Content-Type: application/json');

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado.']);
    exit();
}

$is_admin = ($_SESSION['user_profile'] === 'admin');
$show_inactive = filter_input(INPUT_GET, 'show_inactive', FILTER_VALIDATE_INT) == 1;
$search_term = trim(filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING));

$suppliers = [];

try {
    $sql = "SELECT id, nome, ativo FROM fornecedores";
    $conditions = [];
    $params = [];

    if (!empty($search_term)) {
        $conditions[] = "nome LIKE :search_term";
        $params[':search_term'] = '%' . $search_term . '%';
    }

    // Adiciona a condição de 'ativo' para todos exceto admin que optou por ver inativos
    if (!$is_admin || ($is_admin && !$show_inactive)) {
        $conditions[] = "ativo = 1";
    }

    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    $sql .= " ORDER BY nome ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $suppliers = $stmt->fetchAll();

    echo json_encode(['success' => true, 'suppliers' => $suppliers]);

} catch (PDOException $e) {
    error_log("Erro ao buscar fornecedores na API de busca: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno ao buscar fornecedores.']);
}
?>