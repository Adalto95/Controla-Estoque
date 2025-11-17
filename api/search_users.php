<?php
require_once '../auth_check.php';
require_once '../db.php';
header('Content-Type: application/json');
if ($_SESSION['user_profile'] !== 'admin') { echo json_encode(['success'=>false,'message'=>'Acesso negado.']); exit(); }
$show_inactive = filter_input(INPUT_GET, 'show_inactive', FILTER_VALIDATE_INT) == 1;
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
if (mb_strlen($search_term) > 100) { $search_term = mb_substr($search_term,0,100); }
$sql = "SELECT id, nome, email, perfil, ativo FROM usuarios";
$conditions = [];
$params = [];
if ($search_term !== '') { $conditions[] = "(nome LIKE :s OR email LIKE :s)"; $params[':s'] = '%'.$search_term.'%'; }
if (!$show_inactive) { $conditions[] = "ativo = 1"; }
if (!empty($conditions)) { $sql .= ' WHERE '.implode(' AND ',$conditions); }
$sql .= ' ORDER BY nome ASC';
try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    echo json_encode(['success'=>true,'users'=>$users]);
} catch (PDOException $e) {
    // Fallback se coluna 'ativo' não existir no banco
    error_log('Erro buscar usuarios (tentando fallback): '.$e->getMessage());
    try {
        $sql2 = "SELECT id, nome, email, perfil FROM usuarios";
        $conds2 = [];
        $params2 = [];
        if ($search_term !== '') { $conds2[] = "(nome LIKE :s OR email LIKE :s)"; $params2[':s'] = '%'.$search_term.'%'; }
        if (!empty($conds2)) { $sql2 .= ' WHERE '.implode(' AND ',$conds2); }
        $sql2 .= ' ORDER BY nome ASC';
        $stmt2 = $conn->prepare($sql2);
        $stmt2->execute($params2);
        $users2 = $stmt2->fetchAll();
        $users2 = array_map(function($u){ $arr = (array)$u; $arr['ativo'] = 1; return (object)$arr; }, $users2);
        echo json_encode(['success'=>true,'users'=>$users2]);
    } catch (PDOException $e2) {
        error_log('Erro fallback buscar usuarios: '.$e2->getMessage());
        echo json_encode(['success'=>false,'message'=>'Erro interno.']);
    }
}
?>