CREATE DATABASE controlaestoque

USE controlaestoque;



CREATE TABLE usuarios (

     id INT AUTO_INCREMENT PRIMARY KEY,

     nome VARCHAR(100) NOT NULL,

    email VARCHAR(100) NOT NULL UNIQUE,

     senha VARCHAR(255) NOT NULL,

     perfil ENUM('admin','vendedor', 'gerente') NOT NULL

);

ALTER TABLE usuarios ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1;



CREATE TABLE fornecedores (
     id INT AUTO_INCREMENT PRIMARY KEY,
     nome VARCHAR(100) NOT NULL,
     ativo TINYINT(1) NOT NULL DEFAULT 1
);



CREATE TABLE produtos (
     id INT AUTO_INCREMENT PRIMARY KEY,
     fornecedor_id INT NOT NULL,
     nome VARCHAR(100) NOT NULL,
     estoque1 DECIMAL(10,2) DEFAULT 0.00,
     estoque2 DECIMAL(10,2) DEFAULT 0.00,
     estoque3 DECIMAL(10,2) DEFAULT 0.00,
     estoque4 DECIMAL(10,2) DEFAULT 0.00,
     ativo TINYINT(1) NOT NULL DEFAULT 1,
     FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id)
);



-- Usuários iniciais

INSERT INTO usuarios (nome,email,senha,perfil)

VALUES ('Admin','admin@teste.com',MD5('123456'),'admin');

INSERT INTO usuarios (nome,email,senha,perfil)

VALUES ('Gerente','gerente@teste.com',MD5('123456'),'gerente');



INSERT INTO usuarios (nome,email,senha,perfil)

VALUES ('Vendedor','vendedor@teste.com',MD5('123456'),'vendedor');

CREATE TABLE remember_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES usuarios(id)
);

CREATE TABLE permissions (
    perfil ENUM('gerente','vendedor') PRIMARY KEY,
    view_suppliers TINYINT(1) NOT NULL DEFAULT 1,
    add_supplier TINYINT(1) NOT NULL DEFAULT 0,
    toggle_supplier_status TINYINT(1) NOT NULL DEFAULT 0,
    add_product TINYINT(1) NOT NULL DEFAULT 0,
    edit_product_name TINYINT(1) NOT NULL DEFAULT 0,
    update_stock TINYINT(1) NOT NULL DEFAULT 0,
    toggle_product_status TINYINT(1) NOT NULL DEFAULT 0,
    delete_product TINYINT(1) NOT NULL DEFAULT 0,
    manage_permissions TINYINT(1) NOT NULL DEFAULT 0
);

INSERT INTO permissions (perfil, view_suppliers, add_supplier, toggle_supplier_status, add_product, edit_product_name, update_stock, toggle_product_status, delete_product, manage_permissions)
VALUES
('gerente', 1, 1, 0, 1, 1, 1, 0, 0, 1),
('vendedor', 1, 0, 0, 0, 0, 0, 0, 0, 0);