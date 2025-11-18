<?php
require_once 'auth_check.php';
require_once 'db.php';
checkProfile(['admin','gerente']);
$hasDel = false; $hasViewInactive = false;
try { $chk = $conn->query("SHOW COLUMNS FROM permissions LIKE 'delete_product'"); $hasDel = ($chk && $chk->rowCount() > 0); } catch (PDOException $e) { $hasDel = false; }
try { $chk2 = $conn->query("SHOW COLUMNS FROM permissions LIKE 'view_inactive_products'"); $hasViewInactive = ($chk2 && $chk2->rowCount() > 0); } catch (PDOException $e) { $hasViewInactive = false; }
if (!$hasDel) { try { $conn->exec("ALTER TABLE permissions ADD COLUMN delete_product TINYINT(1) NOT NULL DEFAULT 0"); $hasDel = true; } catch (PDOException $e) { $hasDel = false; } }
if (!$hasViewInactive) { try { $conn->exec("ALTER TABLE permissions ADD COLUMN view_inactive_products TINYINT(1) NOT NULL DEFAULT 0"); $hasViewInactive = true; } catch (PDOException $e) { $hasViewInactive = false; } }
$cols = "perfil, view_suppliers, add_supplier, toggle_supplier_status, add_product, edit_product_name, update_stock, toggle_product_status";
if ($hasDel) { $cols .= ", delete_product"; }
if ($hasViewInactive) { $cols .= ", view_inactive_products"; }
$cols .= ", manage_permissions";
$sql = "SELECT $cols FROM permissions";
$stmt = $conn->query($sql);
$rows = $stmt->fetchAll();
$map = [];
foreach ($rows as $r) { $map[$r->perfil] = (array)$r; }
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permissões</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .perm-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-top:1rem}
        .perm-card{border:1px solid #d1d5db;border-radius:12px;padding:1rem}
        .perm-card h3{margin:0 0 0.5rem 0;color:#2563eb}
        .perm-list{display:grid;grid-template-columns:1fr 1fr;gap:0.5rem}
        .perm-item{display:flex;align-items:center;gap:0.5rem}
    </style>
    </head>
<body>
    <div class="main-container">
        <header>
            <h1>Permissões</h1>
            <nav>
                <a href="suppliers.php" class="button back-button"><i class="fas fa-arrow-left"></i> Voltar</a>
                <a href="logout.php" class="button logout-button">Sair <i class="fas fa-sign-out-alt"></i></a>
            </nav>
        </header>
        <main>
            <div class="perm-grid">
                <?php foreach(['gerente','vendedor'] as $role): $p = isset($map[$role]) ? $map[$role] : []; ?>
                <div class="perm-card">
                    <h3><?php echo ucfirst($role); ?></h3>
                    <form class="perm-form" data-role="<?php echo $role; ?>">
                        <div class="perm-list">
                            <label class="perm-item"><input type="checkbox" name="view_suppliers" <?php echo !empty($p['view_suppliers'])?'checked':''; ?>> Ver fornecedores</label>
                            <label class="perm-item"><input type="checkbox" name="add_supplier" <?php echo !empty($p['add_supplier'])?'checked':''; ?>> Adicionar fornecedor</label>
                            <label class="perm-item"><input type="checkbox" name="toggle_supplier_status" <?php echo !empty($p['toggle_supplier_status'])?'checked':''; ?>> Ativar/Inativar fornecedor</label>
                            <label class="perm-item"><input type="checkbox" name="add_product" <?php echo !empty($p['add_product'])?'checked':''; ?>> Adicionar produto</label>
                            <label class="perm-item"><input type="checkbox" name="edit_product_name" <?php echo !empty($p['edit_product_name'])?'checked':''; ?>> Editar nome de produto</label>
                            <label class="perm-item"><input type="checkbox" name="update_stock" <?php echo !empty($p['update_stock'])?'checked':''; ?>> Atualizar estoque</label>
                            <label class="perm-item"><input type="checkbox" name="toggle_product_status" <?php echo !empty($p['toggle_product_status'])?'checked':''; ?>> Ativar/Inativar produto</label>
                            <?php if ($hasDel): ?>
                            <label class="perm-item"><input type="checkbox" name="delete_product" <?php echo !empty($p['delete_product'])?'checked':''; ?>> Excluir produto</label>
                            <?php endif; ?>
                            <?php if ($hasViewInactive): ?>
                            <label class="perm-item"><input type="checkbox" name="view_inactive_products" <?php echo !empty($p['view_inactive_products'])?'checked':''; ?>> Ver produtos inativos</label>
                            <?php endif; ?>
                            <label class="perm-item"><input type="checkbox" name="manage_permissions" <?php echo !empty($p['manage_permissions'])?'checked':''; ?>> Gerenciar permissões</label>
                        </div>
                        <button type="submit" class="button" style="margin-top:1rem">Salvar</button>
                        <div class="form-message" style="display:none"></div>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
    <script>
        document.querySelectorAll('.perm-form').forEach(form=>{
            form.addEventListener('submit',async e=>{
                e.preventDefault();
                const role=form.dataset.role;
                const data=new URLSearchParams(new FormData(form)).toString()+`&perfil=${role}`;
                const msg=form.querySelector('.form-message');
                try{
                    const r=await fetch('api/update_permissions.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:data});
                    const j=await r.json();
                    msg.textContent=j.message;
                    msg.className='form-message '+(j.success?'success-message':'error-message');
                    msg.style.display='block';
                }catch(err){
                    msg.textContent='Erro de conexão ao salvar.';
                    msg.className='form-message error-message';
                    msg.style.display='block';
                }
            });
        });
    </script>
</body>
</html>