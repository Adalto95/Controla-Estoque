<?php
// index.php - Página de Login

session_start();
require_once 'db.php'; // Inclui o arquivo de conexão com o banco de dados

$error = '';

if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    try {
        $token = $_COOKIE['remember_token'];
        $hash = hash('sha256', $token);
        $stmt = $conn->prepare("SELECT rt.user_id, u.nome, u.perfil FROM remember_tokens rt JOIN usuarios u ON u.id = rt.user_id WHERE rt.token_hash = :h AND rt.expires_at > NOW()");
        $stmt->bindParam(':h', $hash);
        $stmt->execute();
        $u = $stmt->fetch();
        if ($u) {
            $_SESSION['user_id'] = $u->user_id;
            $_SESSION['user_name'] = $u->nome;
            $_SESSION['user_profile'] = $u->perfil;
            if ($u->perfil === 'admin') {
                $_SESSION['permissions'] = [
                    'view_suppliers' => 1,
                    'add_supplier' => 1,
                    'toggle_supplier_status' => 1,
                    'add_product' => 1,
                    'edit_product_name' => 1,
                    'update_stock' => 1,
                    'toggle_product_status' => 1,
                    'manage_permissions' => 1
                ];
            } else {
                $pstmt = $conn->prepare("SELECT view_suppliers, add_supplier, toggle_supplier_status, add_product, edit_product_name, update_stock, toggle_product_status, delete_product, manage_permissions FROM permissions WHERE perfil = :perfil");
                $pstmt->bindParam(':perfil', $u->perfil);
                $pstmt->execute();
                $perms = $pstmt->fetch();
                $_SESSION['permissions'] = $perms ? (array)$perms : [];
            }
            header("Location: suppliers.php");
            exit();
        }
    } catch (PDOException $e) {
        error_log('Auto-login falhou: ' . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $senha = trim($_POST['senha']);

    if (empty($email) || empty($senha)) {
        $error = "Por favor, preencha todos os campos.";
    } else {
        $stmt = $conn->prepare("SELECT id, nome, perfil, senha FROM usuarios WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $row = $stmt->fetch();
        $user = null;
        if ($row) {
            $stored = $row->senha;
            $ok = false;
            if (!empty($stored) && password_verify($senha, $stored)) {
                $ok = true;
            } elseif (strlen($stored) === 32 && MD5($senha) === $stored) {
                $ok = true;
                $new_hash = password_hash($senha, PASSWORD_DEFAULT);
                try {
                    $up = $conn->prepare("UPDATE usuarios SET senha = :h WHERE id = :id");
                    $up->bindParam(':h', $new_hash);
                    $up->bindParam(':id', $row->id);
                    $up->execute();
                } catch (PDOException $e) { error_log('Falha ao atualizar hash: '.$e->getMessage()); }
            }
            if ($ok) { $user = (object)['id'=>$row->id,'nome'=>$row->nome,'perfil'=>$row->perfil]; }
        }

        if ($user) {
            $_SESSION['user_id'] = $user->id;
            $_SESSION['user_name'] = $user->nome;
            $_SESSION['user_profile'] = $user->perfil;
            if ($user->perfil === 'admin') {
                $_SESSION['permissions'] = [
                    'view_suppliers' => 1,
                    'add_supplier' => 1,
                    'toggle_supplier_status' => 1,
                    'add_product' => 1,
                    'edit_product_name' => 1,
                    'update_stock' => 1,
                    'toggle_product_status' => 1,
                    'manage_permissions' => 1
                ];
            } else {
                $pstmt = $conn->prepare("SELECT view_suppliers, add_supplier, toggle_supplier_status, add_product, edit_product_name, update_stock, toggle_product_status, delete_product, manage_permissions FROM permissions WHERE perfil = :perfil");
                $pstmt->bindParam(':perfil', $user->perfil);
                $pstmt->execute();
                $perms = $pstmt->fetch();
                $_SESSION['permissions'] = $perms ? (array)$perms : [];
            }
            if (isset($_POST['remember_me'])) {
                try {
                    $token = bin2hex(random_bytes(32));
                    $hash = hash('sha256', $token);
                    $expires = date('Y-m-d H:i:s', time() + 60*60*24*30);
                    $st = $conn->prepare("INSERT INTO remember_tokens (user_id, token_hash, expires_at) VALUES (:uid, :h, :e)");
                    $st->bindParam(':uid', $user->id);
                    $st->bindParam(':h', $hash);
                    $st->bindParam(':e', $expires);
                    $st->execute();
                    setcookie('remember_token', $token, time() + 60*60*24*30, '/', '', false, true);
                } catch (PDOException $e) {
                    error_log('Lembrar login falhou: ' . $e->getMessage());
                }
            }
            header("Location: suppliers.php");
            exit();
        } else {
            $error = "Email ou senha incorretos.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Controle de Estoque</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-header">
            <div class="brand">ControlaEstoque</div>
            <div class="subtitle">Acesse sua conta para continuar</div>
        </div>
        <?php if ($error): ?>
            <p class="error-message"><?php echo $error; ?></p>
        <?php endif; ?>
        <form method="POST" action="index.php">
            <div class="form-group">
                <label for="email" class="sr-only">Email</label>
                <input type="email" id="email" name="email" placeholder="seu.email@exemplo.com" required>
            </div>
            <div class="form-group">
                <label for="senha" class="sr-only">Senha</label>
                <div class="input-with-icon">
                    <input type="password" id="senha" name="senha" placeholder="Sua senha" required>
                    <button type="button" id="togglePassBtn" class="toggle-pass"><i class="fas fa-eye"></i></button>
                </div>
            </div>
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:0.5rem">
                    <input type="checkbox" name="remember_me" id="remember_me"> Manter conectado
                </label>
            </div>
            <script>
            document.getElementById('togglePassBtn').addEventListener('click', function(){
                const inp = document.getElementById('senha');
                const isPwd = inp.type === 'password';
                inp.type = isPwd ? 'text' : 'password';
                this.innerHTML = isPwd ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
            });
            </script>
            <button type="submit" class="button" style="width:100%">Entrar</button>
        </form>
    </div>
</body>
</html>