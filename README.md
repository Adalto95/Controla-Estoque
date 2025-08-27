# Sistema de Controle de Estoque de Pisos

![Badge de Status](https://img.shields.io/badge/status-em%20desenvolvimento-yellow)
![Badge de Licença](https://img.shields.io/badge/license-MIT-blue)

---

## 📄 Sobre o Projeto

Este é um sistema web básico de controle de estoque, desenvolvido para gerenciar o inventário de pisos. O sistema foi projetado para ser intuitivo e eficiente, permitindo que diferentes tipos de usuários — Vendedor, Gerente e Administrador — executem tarefas específicas e com níveis de acesso bem definidos.

O estoque é segmentado em até **quatro locais diferentes**, oferecendo uma visão detalhada e precisa da localização de cada produto.

---

## 🚀 Tecnologias Utilizadas

* **Linguagem de Programação:** PHP
* **Estilização:** CSS
* **Banco de Dados:** MySQL

---

## 🔑 Funcionalidades e Níveis de Acesso

O sistema possui uma estrutura de acesso baseada em perfis de usuário, garantindo que cada pessoa tenha acesso apenas às funcionalidades necessárias para sua função.

### Vendedor
* **Consulta de Fornecedores:** Visualiza a lista de todos os fornecedores cadastrados.
* **Consulta de Estoque:** Checa a quantidade disponível de um modelo de piso em todos os estoques.

### Gerente
* **Cadastro de Fornecedores:** Adiciona novos fornecedores ao sistema.
* **Cadastro de Produtos:** Cadastra novos modelos de pisos com suas especificações.
* **Alteração de Estoque:** Atualiza as quantidades de pisos diretamente na grade de visualização.

### Administrador
O Administrador tem controle total do sistema e acesso a todas as funcionalidades acima, além de:
* **Gestão de Usuários:** Cria, edita e gerencia contas de usuário.
* **Troca de Senhas:** Redefine senhas de qualquer usuário.
* **Inativação de Produtos:** Desativa a exibição de produtos que não estão mais disponíveis para venda, sem removê-los permanentemente do banco de dados.

---

## 🛠️ Instalação e Configuração

Para rodar este projeto localmente, siga os passos abaixo:

1.  Clone o repositório:
    ```bash
    git clone [[URL_DO_controla_estoque](https://github.com/Adalto95/Controla-Estoque.git)]
    ```

2.  Importe o banco de dados `banco.sql` para o seu servidor MySQL.

3.  Configure as credenciais de acesso ao banco de dados no arquivo `db.php` .

4.  Inicie o servidor local (ex: XAMPP, WAMP, MAMP).

---

## 🤝 Contribuição

Contribuições são sempre bem-vindas! Se você tiver sugestões, ideias de melhoria ou quiser reportar um bug, sinta-se à vontade para abrir uma *issue* ou enviar um *pull request*.

---

## 📝 Licença

Este projeto está licenciado sob a Licença MIT.

---

## ✉️ Contato

* **Seu Nome/Usuário do GitHub** -[adaltor95](http://github.com/adalto95/)
* **Email:** adaltor20@gmail.com
