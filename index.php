<?php
// index.php - Página de Login

session_start();
require_once 'db.php'; // Inclui o arquivo de conexão com o banco de dados

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $senha = trim($_POST['senha']);

    if (empty($email) || empty($senha)) {
        $error = "Por favor, preencha todos os campos.";
    } else {
        // Usa MD5 para a senha, conforme seu esquema de banco de dados
        // NOTA DE SEGURANÇA: MD5 não é seguro para senhas. Recomenda-se usar password_hash() e password_verify().
        $senha_hash = MD5($senha); 

        $stmt = $conn->prepare("SELECT id, nome, perfil FROM usuarios WHERE email = :email AND senha = :senha");
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':senha', $senha_hash);
        $stmt->execute();
        $user = $stmt->fetch();

        if ($user) {
            $_SESSION['user_id'] = $user->id;
            $_SESSION['user_name'] = $user->nome;
            $_SESSION['user_profile'] = $user->perfil;
            header("Location: suppliers.php"); // Redireciona para a página de fornecedores
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
</head>
<body class="login-body">
    <div class="login-container">
        <h2>Controle de Estoque de Pisos</h2>
        <h3>Login</h3>
        <?php if ($error): ?>
            <p class="error-message"><?php echo $error; ?></p>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="senha">Senha:</label>
                <input type="password" id="senha" name="senha" required>
            </div>
            <button type="submit">Entrar</button>
        </form>
    </div>
</body>
</html>