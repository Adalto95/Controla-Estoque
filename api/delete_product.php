<?php
require_once '../auth_check.php';
require_once '../db.php';
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false,'message'=>'Método inválido.']); exit(); }
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) { echo json_encode(['success'=>false,'message'=>'ID inválido.']); exit(); }
$is_admin = ($_SESSION['user_profile'] === 'admin');
$perms = isset($_SESSION['permissions']) ? $_SESSION['permissions'] : [];
$can_delete = $is_admin || (!empty($perms['delete_product']) && (int)$perms['delete_product'] === 1);
if (!$can_delete) { echo json_encode(['success'=>false,'message'=>'Acesso negado.']); exit(); }
try {
    $stmt = $conn->prepare('SELECT estoque1, estoque2, estoque3, estoque4 FROM produtos WHERE id = :id');
    $stmt->bindParam(':id',$id);
    $stmt->execute();
    $p = $stmt->fetch();
    if (!$p) { echo json_encode(['success'=>false,'message'=>'Produto não encontrado.']); exit(); }
    $ok = (float)$p->estoque1 === 0.0 && (float)$p->estoque2 === 0.0 && (float)$p->estoque3 === 0.0 && (float)$p->estoque4 === 0.0;
    if (!$ok) { echo json_encode(['success'=>false,'message'=>'Estoques devem estar zerados para excluir.']); exit(); }
    $del = $conn->prepare('DELETE FROM produtos WHERE id = :id');
    $del->bindParam(':id',$id);
    $del->execute();
    echo json_encode(['success'=>true,'message'=>'Produto excluído.']);
} catch (PDOException $e) {
    error_log('Erro excluir produto: '.$e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Erro ao excluir produto.']);
}
?>