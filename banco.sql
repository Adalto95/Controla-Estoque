CREATE DATABASE controlaestoque

USE controlaestoque;



CREATE TABLE usuarios (

     id INT AUTO_INCREMENT PRIMARY KEY,

     nome VARCHAR(100) NOT NULL,

    email VARCHAR(100) NOT NULL UNIQUE,

     senha VARCHAR(255) NOT NULL,

     perfil ENUM('admin','vendedor', 'gerente') NOT NULL

);



CREATE TABLE fornecedores (

     id INT AUTO_INCREMENT PRIMARY KEY,

     nome VARCHAR(100) NOT NULL

);



CREATE TABLE produtos (

     id INT AUTO_INCREMENT PRIMARY KEY,

     fornecedor_id INT NOT NULL,

     nome VARCHAR(100) NOT NULL,

     estoque1 DECIMAL(10,2) DEFAULT 0.00,

     estoque2 DECIMAL(10,2) DEFAULT 0.00,

     estoque3 DECIMAL(10,2) DEFAULT 0.00,

    estoque4 DECIMAL(10,2) DEFAULT 0.00,

     FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id)

);



-- Usuários iniciais

INSERT INTO usuarios (nome,email,senha,perfil)

VALUES ('Admin','admin@teste.com',MD5('123456'),'admin');

INSERT INTO usuarios (nome,email,senha,perfil)

VALUES ('Gerente','gerente@teste.com',MD5('123456'),'gerente');



INSERT INTO usuarios (nome,email,senha,perfil)

VALUES ('Vendedor','vendedor@teste.com',MD5('123456'),'vendedor');