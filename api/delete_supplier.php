<?php
require_once '../auth_check.php';
require_once '../db.php';
header('Content-Type: application/json');
if ($_SESSION['user_profile'] !== 'admin') { echo json_encode(['success'=>false,'message'=>'Acesso negado.']); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false,'message'=>'Método inválido.']); exit(); }
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) { echo json_encode(['success'=>false,'message'=>'ID inválido.']); exit(); }
try {
    // Verifica se há produtos ativos para este fornecedor
    $stmt = $conn->prepare('SELECT COUNT(*) AS total_ativos FROM produtos WHERE fornecedor_id = :id AND ativo = 1');
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $row = $stmt->fetch();
    if ($row && (int)$row->total_ativos > 0) {
        echo json_encode(['success'=>false,'message'=>'Não é possível excluir: existem produtos ativos para este fornecedor.']);
        exit();
    }
    // Remove todos os produtos inativos do fornecedor
    $delProd = $conn->prepare('DELETE FROM produtos WHERE fornecedor_id = :id AND ativo = 0');
    $delProd->bindParam(':id', $id);
    $delProd->execute();
    // Exclui o fornecedor
    $delSup = $conn->prepare('DELETE FROM fornecedores WHERE id = :id');
    $delSup->bindParam(':id', $id);
    $delSup->execute();
    echo json_encode(['success'=>true,'message'=>'Fornecedor excluído com sucesso.']);
} catch (PDOException $e) {
    error_log('Erro ao excluir fornecedor: '.$e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Erro ao excluir fornecedor.']);
}
?>