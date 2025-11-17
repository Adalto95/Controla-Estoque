<?php
require_once '../auth_check.php';
require_once '../db.php';
header('Content-Type: application/json');
if (!in_array($_SESSION['user_profile'], ['admin','gerente'])) { echo json_encode(['success'=>false,'message'=>'Acesso negado.']); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false,'message'=>'Método inválido.']); exit(); }
$perfil = isset($_POST['perfil']) ? $_POST['perfil'] : '';
if (!in_array($perfil, ['gerente','vendedor'])) { echo json_encode(['success'=>false,'message'=>'Perfil inválido.']); exit(); }
$fields = ['view_suppliers','add_supplier','toggle_supplier_status','add_product','edit_product_name','update_stock','toggle_product_status','manage_permissions'];
$values = [];
foreach ($fields as $f) { $values[$f] = isset($_POST[$f]) ? 1 : 0; }
try {
    $exists = $conn->prepare('SELECT perfil FROM permissions WHERE perfil = :perfil');
    $exists->bindParam(':perfil',$perfil);
    $exists->execute();
    if ($exists->fetch()) {
        $sql = 'UPDATE permissions SET view_suppliers=:view_suppliers, add_supplier=:add_supplier, toggle_supplier_status=:toggle_supplier_status, add_product=:add_product, edit_product_name=:edit_product_name, update_stock=:update_stock, toggle_product_status=:toggle_product_status, manage_permissions=:manage_permissions WHERE perfil=:perfil';
        $stmt = $conn->prepare($sql);
    } else {
        $sql = 'INSERT INTO permissions (view_suppliers, add_supplier, toggle_supplier_status, add_product, edit_product_name, update_stock, toggle_product_status, manage_permissions, perfil) VALUES (:view_suppliers, :add_supplier, :toggle_supplier_status, :add_product, :edit_product_name, :update_stock, :toggle_product_status, :manage_permissions, :perfil)';
        $stmt = $conn->prepare($sql);
    }
    foreach ($fields as $f) { $stmt->bindValue(':'.$f, $values[$f]); }
    $stmt->bindValue(':perfil', $perfil);
    $stmt->execute();
    echo json_encode(['success'=>true,'message'=>'Permissões atualizadas.']);
} catch (PDOException $e) {
    error_log('Erro ao atualizar permissões: '.$e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Erro ao atualizar permissões.']);
}
?>