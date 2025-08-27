<?php
// manage_users.php - Página de gerenciamento de usuários (somente para Admin)
require_once 'auth_check.php';
require_once 'db.php';
checkProfile(['admin']); // Apenas o perfil 'admin' pode acessar esta página

// Carrega a lista de usuários
$users = [];
try {
    $stmt = $conn->query("SELECT id, nome, email, perfil FROM usuarios ORDER BY nome ASC");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao carregar usuários: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="[https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css](https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css)">
</head>
<body>
    <div class="main-container">
        <header>
            <h1>Gerenciar Usuários</h1>
            <nav>
                <a href="suppliers.php" class="button back-button"><i class="fas fa-arrow-left"></i> Voltar</a>
                <a href="logout.php" class="button logout-button">Sair <i class="fas fa-sign-out-alt"></i></a>
            </nav>
        </header>

        <main>
            <div class="user-controls">
                <button type="button" class="button add-inline-button" onclick="openAddUserModal()">Adicionar Novo Usuário <i class="fas fa-user-plus"></i></button>
            </div>
            
            <div class="table-responsive">
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Perfil</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user->nome); ?></td>
                                <td><?php echo htmlspecialchars($user->email); ?></td>
                                <td><?php echo htmlspecialchars($user->perfil); ?></td>
                                <td class="action-cell">
                                    <button class="icon-button change-password-btn" data-user-id="<?php echo $user->id; ?>" data-user-name="<?php echo htmlspecialchars($user->nome); ?>">
                                        <i class="fas fa-key"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Modal para Adicionar Usuário -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeAddUserModal()">&times;</span>
            <h2>Adicionar Novo Usuário</h2>
            <form id="addUserForm">
                <div class="form-group">
                    <label for="modal_user_name">Nome:</label>
                    <input type="text" id="modal_user_name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="modal_user_email">Email:</label>
                    <input type="email" id="modal_user_email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="modal_user_password">Senha:</label>
                    <input type="password" id="modal_user_password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="modal_user_profile">Perfil:</label>
                    <select id="modal_user_profile" name="profile" required>
                        <option value="vendedor">Vendedor</option>
                        <option value="gerente">Gerente</option>
                    </select>
                </div>
                <button type="submit" class="button">Adicionar Usuário</button>
                <div id="add_user_message" class="form-message" style="display: none;"></div>
            </form>
        </div>
    </div>
    
    <!-- Modal para Trocar Senha -->
    <div id="changePasswordModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeChangePasswordModal()">&times;</span>
            <h2 id="changePasswordTitle">Trocar Senha para ...</h2>
            <form id="changePasswordForm">
                <input type="hidden" id="change_password_user_id" name="user_id">
                <div class="form-group">
                    <label for="modal_new_password">Nova Senha:</label>
                    <input type="password" id="modal_new_password" name="new_password" required>
                </div>
                <button type="submit" class="button">Salvar Nova Senha</button>
                <div id="change_password_message" class="form-message" style="display: none;"></div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // --- Funções do Modal Adicionar Usuário ---
            const addUserModal = document.getElementById('addUserModal');
            const addUserForm = document.getElementById('addUserForm');
            const addUserMessage = document.getElementById('add_user_message');

            window.openAddUserModal = function() {
                addUserForm.reset();
                addUserMessage.style.display = 'none';
                addUserModal.style.display = 'flex';
            }

            window.closeAddUserModal = function() {
                addUserModal.style.display = 'none';
            }

            addUserForm.addEventListener('submit', async function(event) {
                event.preventDefault();
                addUserMessage.style.display = 'none';
                
                const formData = new FormData(this);
                
                try {
                    const response = await fetch('api/add_user.php', {
                        method: 'POST',
                        body: new URLSearchParams(formData).toString(),
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    });
                    const data = await response.json();

                    if (data.success) {
                        addUserMessage.textContent = data.message;
                        addUserMessage.className = 'form-message success-message';
                        addUserForm.reset();
                        setTimeout(() => window.location.reload(), 1500); // Recarrega a página para mostrar o novo usuário
                    } else {
                        addUserMessage.textContent = data.message;
                        addUserMessage.className = 'form-message error-message';
                    }
                    addUserMessage.style.display = 'block';
                } catch (error) {
                    console.error('Erro ao adicionar usuário:', error);
                    addUserMessage.textContent = 'Erro de conexão ao adicionar usuário.';
                    addUserMessage.className = 'form-message error-message';
                    addUserMessage.style.display = 'block';
                }
            });

            // --- Funções do Modal Trocar Senha ---
            const changePasswordModal = document.getElementById('changePasswordModal');
            const changePasswordTitle = document.getElementById('changePasswordTitle');
            const changePasswordForm = document.getElementById('changePasswordForm');
            const changePasswordMessage = document.getElementById('change_password_message');
            const changePasswordUserIdInput = document.getElementById('change_password_user_id');

            window.openChangePasswordModal = function(userId, userName) {
                changePasswordForm.reset();
                changePasswordMessage.style.display = 'none';
                changePasswordTitle.textContent = `Trocar Senha para ${userName}`;
                changePasswordUserIdInput.value = userId;
                changePasswordModal.style.display = 'flex';
            }

            window.closeChangePasswordModal = function() {
                changePasswordModal.style.display = 'none';
            }

            document.querySelectorAll('.change-password-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.dataset.userId;
                    const userName = this.dataset.userName;
                    openChangePasswordModal(userId, userName);
                });
            });

            changePasswordForm.addEventListener('submit', async function(event) {
                event.preventDefault();
                changePasswordMessage.style.display = 'none';

                const formData = new FormData(this);
                
                try {
                    const response = await fetch('api/update_user_password.php', {
                        method: 'POST',
                        body: new URLSearchParams(formData).toString(),
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    });
                    const data = await response.json();

                    if (data.success) {
                        changePasswordMessage.textContent = data.message;
                        changePasswordMessage.className = 'form-message success-message';
                        setTimeout(() => closeChangePasswordModal(), 1500);
                    } else {
                        changePasswordMessage.textContent = data.message;
                        changePasswordMessage.className = 'form-message error-message';
                    }
                    changePasswordMessage.style.display = 'block';
                } catch (error) {
                    console.error('Erro ao trocar senha:', error);
                    changePasswordMessage.textContent = 'Erro de conexão ao trocar senha.';
                    changePasswordMessage.className = 'form-message error-message';
                    changePasswordMessage.style.display = 'block';
                }
            });

            // Fechar modais ao clicar fora
            window.onclick = function(event) {
                if (event.target == addUserModal) {
                    closeAddUserModal();
                }
                if (event.target == changePasswordModal) {
                    closeChangePasswordModal();
                }
            }
        });
    </script>
</body>
</html>