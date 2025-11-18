<?php
require_once 'auth_check.php';
require_once 'db.php';
checkProfile(['admin','gerente']);
$stmt = $conn->query("SELECT perfil, view_suppliers, add_supplier, toggle_supplier_status, add_product, edit_product_name, update_stock, toggle_product_status, delete_product, manage_permissions FROM permissions");
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
                            <label class="perm-item"><input type="checkbox" name="delete_product" <?php echo !empty($p['delete_product'])?'checked':''; ?>> Excluir produto</label>
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