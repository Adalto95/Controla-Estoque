<?php
require_once '../auth_check.php';
require_once '../db.php';
header('Content-Type: application/json');
if ($_SESSION['user_profile'] !== 'admin') { echo json_encode(['success'=>false,'message'=>'Acesso negado.']); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false,'message'=>'Método inválido.']); exit(); }
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$status = filter_input(INPUT_POST, 'status', FILTER_VALIDATE_INT);
if (!$id || ($status !== 0 && $status !== 1)) { echo json_encode(['success'=>false,'message'=>'Dados inválidos.']); exit(); }
try {
    $stmt = $conn->prepare("UPDATE usuarios SET ativo = :status WHERE id = :id AND perfil != 'admin'");
    $stmt->bindParam(':status',$status);
    $stmt->bindParam(':id',$id);
    $stmt->execute();
    echo json_encode(['success'=>true,'message'=>'Status do usuário atualizado.']);
} catch (PDOException $e) {
    error_log('Erro atualizar status usuario: '.$e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Erro ao atualizar status.']);
}
?>