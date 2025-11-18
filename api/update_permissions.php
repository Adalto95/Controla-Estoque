<?php
require_once '../auth_check.php';
require_once '../db.php';
header('Content-Type: application/json');
if (!in_array($_SESSION['user_profile'], ['admin','gerente'])) { echo json_encode(['success'=>false,'message'=>'Acesso negado.']); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false,'message'=>'Método inválido.']); exit(); }
$perfil = isset($_POST['perfil']) ? $_POST['perfil'] : '';
if (!in_array($perfil, ['gerente','vendedor'])) { echo json_encode(['success'=>false,'message'=>'Perfil inválido.']); exit(); }
$fields = ['view_suppliers','add_supplier','toggle_supplier_status','add_product','edit_product_name','update_stock','toggle_product_status','delete_product','view_inactive_products','manage_permissions'];
try { $chk = $conn->query("SHOW COLUMNS FROM permissions LIKE 'delete_product'"); $hasDel = ($chk && $chk->rowCount() > 0); } catch (PDOException $e) { $hasDel = false; }
try { $chk2 = $conn->query("SHOW COLUMNS FROM permissions LIKE 'view_inactive_products'"); $hasViewInactive = ($chk2 && $chk2->rowCount() > 0); } catch (PDOException $e) { $hasViewInactive = false; }
if (!$hasDel) { $fields = array_values(array_diff($fields, ['delete_product'])); }
if (!$hasViewInactive) { $fields = array_values(array_diff($fields, ['view_inactive_products'])); }
$values = [];
foreach ($fields as $f) { $values[$f] = isset($_POST[$f]) ? 1 : 0; }
try {
    $exists = $conn->prepare('SELECT perfil FROM permissions WHERE perfil = :perfil');
    $exists->bindParam(':perfil',$perfil);
    $exists->execute();
    if ($exists->fetch()) {
        $set = implode(', ', array_map(function($f){ return $f.'=:'.$f; }, $fields));
        $sql = 'UPDATE permissions SET ' . $set . ' WHERE perfil=:perfil';
        $stmt = $conn->prepare($sql);
    } else {
        $cols = implode(', ', $fields);
        $placeholders = ':' . implode(', :', $fields);
        $sql = 'INSERT INTO permissions (' . $cols . ', perfil) VALUES (' . $placeholders . ', :perfil)';
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