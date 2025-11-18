<?php
// products_by_supplier.php - Lista e gerencia produtos de um fornecedor específico
require_once 'auth_check.php'; // Inclui a verificação de autenticação
require_once 'db.php';         // Inclui a conexão com o banco de dados

$is_admin = ($_SESSION['user_profile'] === 'admin');
$permissions = isset($_SESSION['permissions']) ? $_SESSION['permissions'] : [];
$can_edit_name = ($is_admin || (!empty($permissions['edit_product_name']) && $permissions['edit_product_name'] == 1));
$can_update_stock = ($is_admin || (!empty($permissions['update_stock']) && $permissions['update_stock'] == 1));
$can_add_product = ($is_admin || (!empty($permissions['add_product']) && $permissions['add_product'] == 1));
$can_delete_product = ($is_admin || (!empty($permissions['delete_product']) && $permissions['delete_product'] == 1));

$supplier_id = filter_input(INPUT_GET, 'supplier_id', FILTER_VALIDATE_INT);
$supplier_name = 'Carregando...';

if (!$supplier_id) {
    // Redireciona se não houver ID de fornecedor válido
    header("Location: suppliers.php");
    exit();
}

// Busca o nome do fornecedor
try {
    $stmt = $conn->prepare("SELECT nome FROM fornecedores WHERE id = :supplier_id");
    $stmt->bindParam(':supplier_id', $supplier_id);
    $stmt->execute();
    $supplier = $stmt->fetch();
    if ($supplier) {
        $supplier_name = $supplier->nome;
    } else {
        header("Location: suppliers.php"); // Fornecedor não encontrado
        exit();
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar nome do fornecedor: " . $e->getMessage());
    $supplier_name = 'Erro ao carregar nome';
}

// Variáveis para armazenar fornecedores (para o dropdown do modal de adicionar produto)
$fornecedores_para_modal = [];
try {
    $stmt = $conn->query("SELECT id, nome FROM fornecedores ORDER BY nome ASC");
    $fornecedores_para_modal = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao carregar fornecedores para modal de produto: " . $e->getMessage());
    $fornecedores_para_modal = []; 
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtos de <?php echo htmlspecialchars($supplier_name); ?> - Controle de Estoque</title>
    <link rel="stylesheet" href="style.css">
    <!-- Font Awesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="main-container">
        <header>
            <h1>Produtos de <?php echo htmlspecialchars($supplier_name); ?></h1>
            <nav>
                <a href="suppliers.php" class="button back-button"><i class="fas fa-arrow-left"></i> Voltar aos Fornecedores</a>
                <a href="logout.php" class="button logout-button">Sair <i class="fas fa-sign-out-alt"></i></a>
            </nav>
        </header>

        <main>
            <h2>Estoque de Pisos</h2>
            <div class="product-controls">
                <input type="text" id="product-search" placeholder="Pesquisar produto em tempo real..." class="search-input">
                <select id="product-sort" class="search-input" style="max-width:200px">
                    <option value="name_asc">A–Z</option>
                    <option value="name_desc">Z–A</option>
                </select>
                <div class="control-buttons">
                    <?php if ($is_admin): ?>
                        <button id="toggle-inactive-products" type="button" class="button back-button">
                            <i class="fas fa-eye-slash"></i> Mostrar Inativos
                        </button>
                    <?php endif; ?>
                    <?php if ($can_add_product): ?>
                        <button type="button" class="button add-inline-button" onclick="openAddProductModal(<?php echo $supplier_id; ?>)">Adicionar Produto <i class="fas fa-plus"></i></button>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="product-table">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Estoque 1 (m²)</th>
                            <th>Estoque 2 (m²)</th>
                            <th>Estoque 3 (m²)</th>
                            <th>Estoque 4 (m²)</th>
                            <?php if ($is_admin || $can_delete_product): ?>
                                <th>Ações</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="product-list-body">
                        <!-- Produtos serão carregados aqui via JavaScript/AJAX -->
                    </tbody>
                </table>
            </div>
            <p id="no-products-message" class="info-message" style="display: none;">Nenhum produto encontrado para este fornecedor.</p>
        </main>
    </div>

    <!-- Modal para Adicionar Produto -->
    <div id="addProductModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeAddProductModal()">&times;</span>
            <h2>Adicionar Novo Produto</h2>
            <form id="addProductForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="modal_fornecedor_id">Fornecedor:</label>
                        <select id="modal_fornecedor_id" name="fornecedor_id" required>
                            <option value="">Selecione um Fornecedor</option>
                            <?php foreach ($fornecedores_para_modal as $fornecedor): ?>
                                <option value="<?php echo $fornecedor->id; ?>">
                                    <?php echo htmlspecialchars($fornecedor->nome); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="modal_fornecedor_id_error" class="validation-error"></div>
                    </div>
                    <div class="form-group flex-grow">
                        <label for="modal_nome_produto">Nome do Produto:</label>
                        <input type="text" id="modal_nome_produto" name="nome_produto" required>
                        <div id="modal_nome_produto_error" class="validation-error"></div>
                    </div>
                </div>
                <div class="form-row stock-row">
                    <div class="form-group stock-col">
                        <label for="modal_estoque1">Est. 1 (m²):</label>
                        <input type="number" step="0.01" id="modal_estoque1" name="estoque1" value="0.00" min="0">
                        <div id="modal_estoque1_error" class="validation-error"></div>
                    </div>
                    <div class="form-group stock-col">
                        <label for="modal_estoque2">Est. 2 (m²):</label>
                        <input type="number" step="0.01" id="modal_estoque2" name="estoque2" value="0.00" min="0">
                        <div id="modal_estoque2_error" class="validation-error"></div>
                    </div>
                    <div class="form-group stock-col">
                        <label for="modal_estoque3">Est. 3 (m²):</label>
                        <input type="number" step="0.01" id="modal_estoque3" name="estoque3" value="0.00" min="0">
                        <div id="modal_estoque3_error" class="validation-error"></div>
                    </div>
                    <div class="form-group stock-col">
                        <label for="modal_estoque4">Est. 4 (m²):</label>
                        <input type="number" step="0.01" id="modal_estoque4" name="estoque4" value="0.00" min="0">
                        <div id="modal_estoque4_error" class="validation-error"></div>
                    </div>
                </div>
                <button type="submit" class="button">Adicionar</button>
                <div id="add_product_message" class="form-message" style="display: none;"></div>
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
            const currentSupplierId = <?php echo json_encode($supplier_id); ?>;
            let isAdmin = <?php echo json_encode($is_admin); ?>;
            let canEditName = <?php echo json_encode($can_edit_name); ?>;
            let canUpdateStock = <?php echo json_encode($can_update_stock); ?>;
            let showInactive = false;
            let currentSort = 'name_asc';

            // Função para carregar e exibir produtos
            async function loadProducts(supplierId, searchTerm = '') {
                const productListBody = document.getElementById('product-list-body');
                const noProductsMessage = document.getElementById('no-products-message');
                productListBody.innerHTML = '<tr><td colspan="6" style="text-align: center;">Carregando produtos...</td></tr>';
                noProductsMessage.style.display = 'none';

                let url = `api/get_products.php?supplier_id=${supplierId}&search=${searchTerm}&sort=${currentSort}`;
                if (isAdmin) {
                    url += `&show_inactive=${showInactive ? '1' : '0'}`;
                }

                try {
                    const response = await fetch(url);
                    const data = await response.json();

                    productListBody.innerHTML = ''; // Limpa a lista
                    if (data.success && data.products.length > 0) {
                        data.products.forEach(product => {
                            // Converte valores de estoque para float para comparação
                            const isZeroStock = (parseFloat(product.estoque1) === 0 && parseFloat(product.estoque2) === 0 && parseFloat(product.estoque3) === 0 && parseFloat(product.estoque4) === 0);
                            const rowClass = `${isZeroStock ? 'zero-stock' : ''} ${product.ativo == 0 ? 'inactive-row' : ''}`;
                            
                            let rowHtml = `<tr data-product-id="${product.id}" class="${rowClass}">`;
                            if (canEditName) {
                                rowHtml += `<td class="product-name-cell"><input type="text" class="product-name-input" value="${escapeHtml(product.produto_nome)}"></td>`;
                            } else {
                                rowHtml += `<td>${escapeHtml(product.produto_nome)}</td>`;
                            }

                            for (let i = 1; i <= 4; i++) {
                                const stockValue = parseFloat(product['estoque' + i]).toFixed(2); // Formata para 2 casas decimais
                                if (canUpdateStock) {
                                    rowHtml += `<td class="stock-cell">
                                                    <input type="number" step="0.01"
                                                           name="estoque${i}" 
                                                           value="${stockValue}" 
                                                           data-stock-field="estoque${i}" 
                                                           class="stock-input" min="0">
                                                </td>`;
                                } else {
                                    rowHtml += `<td class="stock-cell">${stockValue}</td>`;
                                }
                            }
                            
                            if (isAdmin || <?php echo json_encode($can_delete_product); ?>) {
                                rowHtml += `
                                    <td class="action-cell">
                                        ${isAdmin ? `<button class="toggle-status-btn ${product.ativo == 1 ? 'btn-inativar' : 'btn-ativar'}" data-product-id="${product.id}" data-status="${product.ativo}">${product.ativo == 1 ? 'Inativar' : 'Ativar'}</button>` : ''}
                                        <button class="button" style="background-color:#dc3545" data-action="delete-product" data-product-id="${product.id}" ${!isZeroStock ? 'disabled' : ''}>Excluir</button>
                                    </td>
                                `;

                            }

                            rowHtml += `</tr>`;
                            productListBody.innerHTML += rowHtml;
                        });

                        // Re-adiciona os event listeners para os inputs de estoque se for admin ou gerente
                        if (canUpdateStock) {
                            addStockInputListeners();
                        }
                        if (canEditName) {
                            addProductNameListeners();
                        }
                        
                        // Adiciona listeners para os botões de inativação
                        if (isAdmin) {
                             document.querySelectorAll('.toggle-status-btn').forEach(button => {
                                button.addEventListener('click', function() {
                                    const productId = this.dataset.productId;
                                    const currentStatus = this.dataset.status;
                                    const newStatus = currentStatus == 1 ? 0 : 1;
                                    const action = newStatus === 0 ? 'inativar' : 'ativar';
                                    const message = `Você tem certeza que deseja ${action} o produto "${this.closest('tr').querySelector('td').textContent}"?`;
                                    
                                    openConfirmModal(action, message, async () => {
                                        try {
                                            const response = await fetch('api/toggle_product_status.php', {
                                                method: 'POST',
                                                headers: {
                                                    'Content-Type': 'application/x-www-form-urlencoded',
                                                },
                                                body: `id=${productId}&status=${newStatus}`
                                            });
                                            const result = await response.json();
                                            if (result.success) {
                                                loadProducts(currentSupplierId, productSearchInput.value);
                                            } else {
                                                alert(result.message);
                                            }
                                        } catch (error) {
                                            console.error('Erro ao alternar status do produto:', error);
                                            alert('Erro de conexão ao alternar status do produto.');
                                        }
                                        closeConfirmModal();
                                    });
                                });
                            });
                        }
                        document.querySelectorAll('button[data-action="delete-product"]').forEach(button => {
                            button.addEventListener('click', function() {
                                const productId = this.dataset.productId;
                                const nameCell = this.closest('tr').querySelector('td');
                                const message = `Excluir o produto "${nameCell.textContent}"? Estoque precisa estar zerado.`;
                                openConfirmModal('excluir', message, async () => {
                                    try {
                                        const resp = await fetch('api/delete_product.php', {
                                            method: 'POST',
                                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                            body: `id=${productId}`
                                        });
                                        const data = await resp.json();
                                        if (data.success) {
                                            loadProducts(currentSupplierId, productSearchInput.value);
                                        } else {
                                            alert(data.message);
                                        }
                                    } catch (e) {
                                        alert('Erro de conexão ao excluir.');
                                    }
                                    closeConfirmModal();
                                });
                            });
                        });

                    } else {
                        noProductsMessage.style.display = 'block';
                    }
                } catch (error) {
                    console.error('Erro ao carregar produtos:', error);
                    productListBody.innerHTML = '<tr><td colspan="6" class="error-message" style="text-align: center;">Erro ao carregar produtos. Verifique o console para mais detalhes.</td></tr>';
                }
            }

            // Event listener para a busca em tempo real
            const productSearchInput = document.getElementById('product-search');
            productSearchInput.addEventListener('keyup', function() {
                loadProducts(currentSupplierId, this.value);
            });

            const productSortSelect = document.getElementById('product-sort');
            productSortSelect.addEventListener('change', function() {
                currentSort = this.value;
                loadProducts(currentSupplierId, productSearchInput.value);
            });

            // Event listener para o botão de alternar inativos
            const toggleInactiveBtn = document.getElementById('toggle-inactive-products');
            if (toggleInactiveBtn) {
                toggleInactiveBtn.addEventListener('click', () => {
                    showInactive = !showInactive;
                    toggleInactiveBtn.innerHTML = showInactive ? '<i class="fas fa-eye"></i> Mostrar Ativos' : '<i class="fas fa-eye-slash"></i> Mostrar Inativos';
                    toggleInactiveBtn.classList.toggle('active', showInactive);
                    loadProducts(currentSupplierId, productSearchInput.value);
                });
            }

            // Função para adicionar listeners aos inputs de estoque se for admin ou gerente
            function addStockInputListeners() {
                const stockInputs = document.querySelectorAll('.stock-input');
                stockInputs.forEach(input => {
                    let originalValue = parseFloat(input.value).toFixed(2); // Usar float para valor original

                    input.addEventListener('focus', function() {
                        originalValue = parseFloat(this.value).toFixed(2);
                    });

                    input.addEventListener('change', async function() {
                        const productId = this.closest('tr').dataset.productId;
                        const stockField = this.dataset.stockField;
                        const newValue = parseFloat(this.value).toFixed(2); // Usar float para o novo valor

                        if (newValue === originalValue || newValue.trim() === '' || isNaN(parseFloat(newValue))) {
                            this.value = originalValue; // Garante que o input mantenha o valor anterior ou o valor formatado
                            return;
                        }

                        if (parseFloat(newValue) < 0) {
                            alert('Por favor, insira um número válido para o estoque (não negativo).');
                            this.value = originalValue; 
                            return;
                        }

                        try {
                            const response = await fetch('api/update_stock.php', { // Caminho corrigido
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: `product_id=${productId}&stock_field=${stockField}&new_value=${newValue}`
                            });
                            const data = await response.json();

                            if (data.success) {
                                this.style.backgroundColor = '#d4edda'; 
                                setTimeout(() => {
                                    this.style.backgroundColor = ''; 
                                }, 1000);
                                originalValue = newValue; 

                                // Re-avaliar a cor da linha após a atualização do estoque
                                const row = this.closest('tr');
                                const allStockInputsInRow = row.querySelectorAll('.stock-input');
                                let allZero = true;
                                if (allStockInputsInRow.length > 0) { 
                                    allStockInputsInRow.forEach(sInput => {
                                        if (parseFloat(sInput.value) > 0) { 
                                            allZero = false;
                                        }
                                    });
                                } else { 
                                    const stockCells = row.querySelectorAll('.stock-cell');
                                    let tempAllZero = true;
                                    stockCells.forEach(cell => {
                                        if (parseFloat(cell.textContent) > 0) {
                                            tempAllZero = false;
                                        }
                                    });
                                    allZero = tempAllZero;
                                }

                                if (allZero) {
                                    row.classList.add('zero-stock');
                                } else {
                                    row.classList.remove('zero-stock');
                                }

                            } else {
                                this.style.backgroundColor = '#f8d7da'; 
                                alert('Erro ao atualizar estoque: ' + data.message);
                                this.value = originalValue; 
                            }
                        } catch (error) {
                            console.error('Erro na requisição AJAX de atualização de estoque:', error);
                            this.style.backgroundColor = '#f8d7da'; 
                            alert('Erro de conexão ao atualizar o estoque. Verifique o console para mais detalhes.');
                            this.value = originalValue; 
                        }
                    });
                });
            }

            function addProductNameListeners() {
                const nameInputs = document.querySelectorAll('.product-name-input');
                nameInputs.forEach(input => {
                    let originalValue = input.value;
                    input.addEventListener('focus', function() {
                        originalValue = this.value;
                    });
                    async function commitChange(el) {
                        const productId = el.closest('tr').dataset.productId;
                        const newName = el.value.trim();
                        if (newName === '' || newName === originalValue) {
                            el.value = originalValue;
                            return;
                        }
                        try {
                            const response = await fetch('api/update_product_name.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: `product_id=${productId}&new_name=${encodeURIComponent(newName)}`
                            });
                            const data = await response.json();
                            if (data.success) {
                                el.style.backgroundColor = '#d4edda';
                                setTimeout(() => { el.style.backgroundColor = ''; }, 1000);
                                originalValue = newName;
                            } else {
                                el.style.backgroundColor = '#f8d7da';
                                alert('Erro ao atualizar nome: ' + data.message);
                                el.value = originalValue;
                            }
                        } catch (error) {
                            console.error('Erro ao atualizar nome do produto:', error);
                            el.style.backgroundColor = '#f8d7da';
                            alert('Erro de conexão ao atualizar o nome.');
                            el.value = originalValue;
                        }
                    }
                    input.addEventListener('change', function() { commitChange(this); });
                    input.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter') { e.preventDefault(); commitChange(this); }
                        if (e.key === 'Escape') { this.value = originalValue; this.blur(); }
                    });
                });
            }

            // Inicializa o carregamento de produtos para o fornecedor atual
            loadProducts(currentSupplierId);

            // --- Funções do Modal Adicionar Produto ---
            const addProductModal = document.getElementById('addProductModal');
            const addProductForm = document.getElementById('addProductForm');
            const addProductMessage = document.getElementById('add_product_message');

            window.openAddProductModal = function() {
                addProductForm.reset(); // Limpa o formulário
                addProductMessage.style.display = 'none';
                document.querySelectorAll('#addProductForm .validation-error').forEach(div => div.textContent = '');
                // Pré-seleciona o fornecedor atual no modal
                document.getElementById('modal_fornecedor_id').value = currentSupplierId;
                // Desabilita o select se um fornecedor já está selecionado na URL
                document.getElementById('modal_fornecedor_id').disabled = true; 
                addProductModal.style.display = 'flex'; // Usar flex para centralizar
            }

            window.closeAddProductModal = function() {
                document.getElementById('modal_fornecedor_id').disabled = false; // Reabilita
                addProductModal.style.display = 'none';
            }

            addProductForm.addEventListener('submit', async function(event) {
                event.preventDefault();
                addProductMessage.style.display = 'none';
                document.querySelectorAll('#addProductForm .validation-error').forEach(div => div.textContent = '');

                let isValid = true;
                const modalFornecedorId = document.getElementById('modal_fornecedor_id');
                const modalNomeProduto = document.getElementById('modal_nome_produto');
                const modalEstoqueInputs = [
                    document.getElementById('modal_estoque1'),
                    document.getElementById('modal_estoque2'),
                    document.getElementById('modal_estoque3'),
                    document.getElementById('modal_estoque4')
                ];

                if (modalFornecedorId.value === "") {
                    document.getElementById('modal_fornecedor_id_error').textContent = 'Selecione um fornecedor.';
                    isValid = false;
                }
                if (modalNomeProduto.value.trim() === "") {
                    document.getElementById('modal_nome_produto_error').textContent = 'O nome do produto não pode ser vazio.';
                    isValid = false;
                }
                modalEstoqueInputs.forEach((input, index) => {
                    const value = parseFloat(input.value); // Usar parseFloat
                    if (isNaN(value) || value < 0) {
                        document.getElementById(`modal_estoque${index + 1}_error`).textContent = 'Estoque deve ser um número não negativo.';
                        isValid = false;
                    }
                });

                if (!isValid) {
                    addProductMessage.textContent = 'Por favor, corrija os erros no formulário.';
                    addProductMessage.className = 'form-message error-message';
                    addProductMessage.style.display = 'block';
                    return;
                }

                // FormData para enviar o valor desabilitado
                const formData = new FormData(this);
                // Adiciona o fornecedor_id, pois ele pode estar desabilitado no formulário
                formData.set('fornecedor_id', modalFornecedorId.value); 
                
                try {
                    const response = await fetch('api/add_product.php', {
                        method: 'POST',
                        body: new URLSearchParams(formData).toString(),
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                    });
                    const data = await response.json();

                    if (data.success) {
                        addProductMessage.textContent = data.message;
                        addProductMessage.className = 'form-message success-message';
                        addProductForm.reset();
                        document.getElementById('modal_fornecedor_id').value = currentSupplierId; // Mantém pré-seleção
                        loadProducts(currentSupplierId, productSearchInput.value); // Recarrega produtos
                    } else {
                        addProductMessage.textContent = data.message;
                        addProductMessage.className = 'form-message error-message';
                    }
                    addProductMessage.style.display = 'block';
                } catch (error) {
                    console.error('Erro ao adicionar produto:', error);
                    addProductMessage.textContent = 'Erro de conexão ao adicionar produto. Verifique o console para mais detalhes.';
                    addProductMessage.className = 'form-message error-message';
                    addProductMessage.style.display = 'block';
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
                if (event.target == addProductModal) {
                    closeAddProductModal();
                }
                if (event.target == confirmActionModal) {
                    closeConfirmModal();
                }
            }
        });
    </script>
</body>
</html>