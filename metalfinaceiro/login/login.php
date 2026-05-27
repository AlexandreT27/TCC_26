<?php
session_start();

// CONEXÃO COM O BANCO
$host = "";
$usuario_bd = "";
$senha_bd = "";
$banco = "";

$conn = new mysqli($host, $usuario_bd, $senha_bd, $banco);

// VERIFICA CONEXÃO
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}


if (isset($_POST["login"])) {

    $usuario = $_POST["usuario"] ?? "";
    $senha = $_POST["senha"] ?? "";

    $sql = "SELECT * FROM  WHERE  = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $usuario);
    $stmt->execute();

    $resultado = $stmt->get_result();

    // VERIFICA USUÁRIO
    if ($resultado->num_rows > 0) {

        $dados = $resultado->fetch_assoc();

        // VERIFICA SENHA
        if (password_verify($senha, $dados["senha"])) {

            $_SESSION["usuario"] = $dados["usuario"];

            header("Location: painel.php");
            exit();

        } else {
            $mensagem = "Senha incorreta!";
        }

    } else {
        $mensagem = "Usuário não encontrado!";
    }
}
?>