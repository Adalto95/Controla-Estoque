<?php
// db.php - Conexão com o banco de dados
// Este arquivo deve ser incluído em todas as páginas que precisam de acesso ao DB.

$host = getenv('DB_HOST') ?: "localhost";
$user = getenv('DB_USER') ?: "controlaestoque";
$pass = getenv('DB_PASS') ?: "senha";
$db_name = getenv('DB_NAME') ?: "controlaestoque";


try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $user, $pass);
    // Configura o PDO para lançar exceções em caso de erros
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Define o modo de busca padrão para objetos, facilitando o acesso aos dados
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
} catch (PDOException $e) {
    // Em um ambiente de produção, desabilite display_errors no php.ini para evitar vazamento de informações.
    // Em vez disso, logue o erro em um arquivo e exiba uma mensagem genérica para o usuário.
    error_log("Erro na conexão com o banco de dados: " . $e->getMessage());
    die("Ocorreu um erro ao conectar-se ao banco de dados. Por favor, tente novamente mais tarde.");
}
?>