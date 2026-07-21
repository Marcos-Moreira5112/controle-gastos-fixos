<?php
// Configurações do banco de dados na nuvem (InfinityFree)
$host = "sql308.infinityfree.com";
$usuario = "if0_42456347";
$senha = "SUA_SENHA_DO_VPANEL"; // Substitua aqui pela senha que você copiou com o 'olhinho' 👁️
$banco = "if0_42456347_gastos";

// Criando a conexão usando o PDO (Mantendo a estrutura original do seu sistema)
try {
    $pdo = new PDO("mysql:host=$host;dbname=$banco;charset=utf8mb4", $usuario, $senha);
    // Configura o PDO para mostrar erros caso algo dê errado
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Se der erro na conexão, o sistema para aqui e avisa o que aconteceu
    die("Erro ao conectar com o banco de dados: " . $e->getMessage());
}
?>