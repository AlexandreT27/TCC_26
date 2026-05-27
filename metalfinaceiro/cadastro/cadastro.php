<?php
// Conexão com o banco (ajuste conforme seu ambiente)
$host = "";
$user = "";
$pass = "";
$db = "";

$conn = new mysqli($host, $user, $pass, $db);

// Verifica conexão
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

// Recebendo dados do formulário
$nome       = $_POST['nome'] ?? '';
$sobrenome  = $_POST['sobrenome'] ?? '';
$email      = $_POST['email'] ?? '';
$senha      = $_POST['senha'] ?? '';
$numero     = $_POST['numero'] ?? '';
$data       = $_POST['data'] ?? '';
$genero     = $_POST['genero'] ?? '';
$cidade     = $_POST['cidade'] ?? '';
$estado     = $_POST['estado'] ?? '';

// Criptografar senha
$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

// Inserir no banco
$sql = "INSERT INTO 
VALUES ";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "",
    $nome,
    $sobrenome,
    $email,
    $senhaHash,
    $numero,
    $data,
    $genero,
    $cidade,
    $estado
);

// Executa
if ($stmt->execute()) {
    echo "Cadastro realizado com sucesso!";
} else {
    echo "Erro ao cadastrar: " . $stmt->error;
}

// Fechar conexão
$stmt->close();
$conn->close();
?>