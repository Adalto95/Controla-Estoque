<?php
// suppliers.php - Página principal com lista de fornecedores
require_once 'auth_check.php'; // Inclui a verificação de autenticação
require_once 'db.php';         // Inclui a conexão com o banco de dados

$is_admin = ($_SESSION['user_profile'] === 'admin');
$can_manage_data = ($is_admin || $_SESSION['user_profile'] === 'gerente');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fornecedores - Controle de Estoque</title>
    <link rel="stylesheet" href="style.css">
    <!-- Font Awesome para ícones -->
    <link rel="stylesheet" href="[https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css](https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css)">
</head>
<body>
    <div class="main-container">
        <header>
            <h1>Bem-vindo, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
            <nav>
                <?php if ($is_admin): ?>
                    <a href="manage_users.php" class="button"><i class="fas fa-users-cog"></i> Gerenciar Usuários</a>
                <?php endif; ?>
                <a href="logout.php" class="button logout-button">Sair <i class="fas fa-sign-out-alt"></i></a>
            </nav>
        </header>

        <main>
            <h2>Gerenciar Fornecedores</h2>
            <div class="supplier-controls">
                <input type="text" id="supplier-search" placeholder="Buscar fornecedor em tempo real..." class="search-input">
                <div class="control-buttons">
                    <?php if ($is_admin): ?>
                        <button id="toggle-inactive-suppliers" type="button" class="button back-button">
                            <i class="fas fa-eye-slash"></i> Mostrar Inativos
                        </button>
                    <?php endif; ?>
                    <?php if ($can_manage_data): ?>
                        <button type="button" class="button add-inline-button" onclick="openAddSupplierModal()">Adicionar Fornecedor <i class="fas fa-plus"></i></button>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="supplier-list-grid" id="supplier-list-grid">
                <!-- Fornecedores serão carregados aqui via JavaScript/AJAX -->
            </div>
            <p id="no-suppliers-message" class="info-message" style="display: none;">Nenhum fornecedor encontrado.</p>
        </main>
    </div>

    <!-- Modal para Adicionar Fornecedor -->
    <div id="addSupplierModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeAddSupplierModal()">&times;</span>
            <h2>Adicionar Novo Fornecedor</h2>
            <form id="addSupplierForm">
                <div class="form-group">
                    <label for="modal_nome_fornecedor">Nome do Fornecedor:</label>
                    <input type="text" id="modal_nome_fornecedor" name="nome_fornecedor" required>
                    <div id="modal_nome_fornecedor_error" class="validation-error"></div>
                </div>
                <button type="submit" class="button">Adicionar</button>
                <div id="add_supplier_message" class="form-message" style="display: none;"></div>
            </form>
        </div>
    </div>

    <!-- Modal para confirmação de inativação -->
    <div id="confirmActionModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeConfirmModal()">&times;</span>
            <h2 id="confirmTitle"></h2>
            <p id="confirmMessage"></p>
            <div class="modal-buttons">
                <button id="cancelConfirm" class="button back-button">Cancelar</button>
                <button id="confirmAction" class="button logout-button">Confirmar</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            let isAdmin = <?php echo json_encode($is_admin); ?>;
            let showInactive = false;

            // Função para carregar e exibir fornecedores
            async function loadSuppliers(searchTerm = '') {
                const supplierListGrid = document.getElementById('supplier-list-grid');
                const noSuppliersMessage = document.getElementById('no-suppliers-message');
                supplierListGrid.innerHTML = '<div class="loading-message">Carregando fornecedores...</div>';
                noSuppliersMessage.style.display = 'none';

                let url = `api/search_suppliers.php?search=${searchTerm}`;
                if (isAdmin) {
                    url += `&show_inactive=${showInactive ? '1' : '0'}`;
                }

                try {
                    const response = await fetch(url);
                    const data = await response.json();

                    supplierListGrid.innerHTML = ''; // Limpa a lista
                    if (data.success && data.suppliers.length > 0) {
                        data.suppliers.forEach(supplier => {
                            const supplierCard = document.createElement('div');
                            supplierCard.className = `supplier-card ${supplier.ativo == 0 ? 'inactive-card' : ''}`;
                            
                            let cardContent = `
                                <h3> ${escapeHtml(supplier.nome)}</h3>
                            `;

                            if (isAdmin) {
                                cardContent += `
                                    <div class="card-actions">
                                        <button class="icon-button toggle-status-btn" data-supplier-id="${supplier.id}" data-status="${supplier.ativo}">
                                            ${supplier.ativo == 1 ? '<i class="fas fa-ban"></i>' : '<i class="fas fa-check-circle"></i>'}
                                        </button>
                                    </div>
                                `;
                            }

                            // Apenas cria o link se o fornecedor estiver ativo
                            if (supplier.ativo == 1) {
                                const supplierLink = document.createElement('a');
                                supplierLink.href = `products_by_supplier.php?supplier_id=${supplier.id}`;
                                supplierLink.innerHTML = cardContent;
                                supplierLink.className = `supplier-card-link`;
                                supplierCard.appendChild(supplierLink);
                            } else {
                                supplierCard.innerHTML = cardContent;
                            }
                            
                            supplierListGrid.appendChild(supplierCard);
                        });

                        // Adiciona listeners para os botões de inativação
                        if (isAdmin) {
                             document.querySelectorAll('.toggle-status-btn').forEach(button => {
                                button.addEventListener('click', function(event) {
                                    event.stopPropagation();
                                    event.preventDefault();
                                    const supplierId = this.dataset.supplierId;
                                    const currentStatus = this.dataset.status;
                                    const newStatus = currentStatus == 1 ? 0 : 1;
                                    const action = newStatus === 0 ? 'inativar' : 'ativar';
                                    const message = `Você tem certeza que deseja ${action} o fornecedor "${this.closest('.supplier-card').querySelector('h3').textContent}"?`;
                                    
                                    openConfirmModal(action, message, async () => {
                                        try {
                                            const response = await fetch('api/toggle_supplier_status.php', {
                                                method: 'POST',
                                                headers: {
                                                    'Content-Type': 'application/x-www-form-urlencoded',
                                                },
                                                body: `id=${supplierId}&status=${newStatus}`
                                            });
                                            const result = await response.json();
                                            if (result.success) {
                                                loadSuppliers(supplierSearchInput.value);
                                            } else {
                                                alert(result.message);
                                            }
                                        } catch (error) {
                                            console.error('Erro ao alternar status do fornecedor:', error);
                                            alert('Erro de conexão ao alternar status do fornecedor.');
                                        }
                                        closeConfirmModal();
                                    });
                                });
                            });
                        }

                    } else {
                        noSuppliersMessage.style.display = 'block';
                    }
                } catch (error) {
                    console.error('Erro ao carregar fornecedores:', error);
                    supplierListGrid.innerHTML = '<div class="error-message">Erro ao carregar fornecedores. Verifique o console para detalhes.</div>';
                }
            }

            // Event listener para a busca em tempo real de fornecedores
            const supplierSearchInput = document.getElementById('supplier-search');
            supplierSearchInput.addEventListener('keyup', function() {
                loadSuppliers(this.value);
            });

            // Event listener para o botão de alternar inativos
            const toggleInactiveBtn = document.getElementById('toggle-inactive-suppliers');
            if (toggleInactiveBtn) {
                toggleInactiveBtn.addEventListener('click', () => {
                    showInactive = !showInactive;
                    toggleInactiveBtn.innerHTML = showInactive ? '<i class="fas fa-eye"></i> Mostrar Ativos' : '<i class="fas fa-eye-slash"></i> Mostrar Inativos';
                    toggleInactiveBtn.classList.toggle('active', showInactive);
                    loadSuppliers(supplierSearchInput.value);
                });
            }

            // Inicializa o carregamento de fornecedores
            loadSuppliers();

            // --- Funções do Modal Adicionar Fornecedor ---
            const addSupplierModal = document.getElementById('addSupplierModal');
            const addSupplierForm = document.getElementById('addSupplierForm');
            const addSupplierMessage = document.getElementById('add_supplier_message');

            window.openAddSupplierModal = function() {
                addSupplierForm.reset();
                addSupplierMessage.style.display = 'none';
                document.getElementById('modal_nome_fornecedor_error').textContent = '';
                addSupplierModal.style.display = 'flex'; // Usar flex para centralizar
            }

            window.closeAddSupplierModal = function() {
                addSupplierModal.style.display = 'none';
            }

            addSupplierForm.addEventListener('submit', async function(event) {
                event.preventDefault();
                addSupplierMessage.style.display = 'none';
                document.getElementById('modal_nome_fornecedor_error').textContent = '';

                let isValid = true;
                const modalNomeFornecedor = document.getElementById('modal_nome_fornecedor');

                if (modalNomeFornecedor.value.trim() === "") {
                    document.getElementById('modal_nome_fornecedor_error').textContent = 'O nome do fornecedor não pode ser vazio.';
                    isValid = false;
                }

                if (!isValid) {
                    addSupplierMessage.textContent = 'Por favor, corrija os erros no formulário.';
                    addSupplierMessage.className = 'form-message error-message';
                    addSupplierMessage.style.display = 'block';
                    return;
                }

                const formData = new URLSearchParams(new FormData(this)).toString();
                try {
                    const response = await fetch('api/add_supplier.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: formData
                    });
                    const data = await response.json();

                    if (data.success) {
                        addSupplierMessage.textContent = data.message;
                        addSupplierMessage.className = 'form-message success-message';
                        addSupplierForm.reset();
                        loadSuppliers(supplierSearchInput.value); // Recarrega a lista de fornecedores
                    } else {
                        addSupplierMessage.textContent = data.message;
                        addSupplierMessage.className = 'form-message error-message';
                    }
                    addSupplierMessage.style.display = 'block';
                } catch (error) {
                    console.error('Erro ao adicionar fornecedor:', error);
                    addSupplierMessage.textContent = 'Erro de conexão ao adicionar fornecedor. Verifique o console para mais detalhes.';
                    addSupplierMessage.className = 'form-message error-message';
                    addSupplierMessage.style.display = 'block';
                }
            });
            
            // --- Funções do Modal de Confirmação ---
            const confirmActionModal = document.getElementById('confirmActionModal');
            const confirmTitle = document.getElementById('confirmTitle');
            const confirmMessage = document.getElementById('confirmMessage');
            const cancelConfirmBtn = document.getElementById('cancelConfirm');
            const confirmActionBtn = document.getElementById('confirmAction');

            window.openConfirmModal = function(title, message, onConfirm) {
                confirmTitle.textContent = title.charAt(0).toUpperCase() + title.slice(1);
                confirmMessage.textContent = message;
                confirmActionBtn.onclick = onConfirm; // Atribui a função de confirmação ao clique
                confirmActionModal.style.display = 'flex';
            }

            window.closeConfirmModal = function() {
                confirmActionModal.style.display = 'none';
                confirmActionBtn.onclick = null; // Limpa o evento
            }
            
            cancelConfirmBtn.addEventListener('click', closeConfirmModal);

            // Função utilitária para escapar HTML
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.appendChild(document.createTextNode(text));
                return div.innerHTML;
            }

            // Fechar modais ao clicar fora
            window.onclick = function(event) {
                if (event.target == addSupplierModal) {
                    closeAddSupplierModal();
                }
                if (event.target == confirmActionModal) {
                    closeConfirmModal();
                }
            }
        });
    </script>
</body>
</html>