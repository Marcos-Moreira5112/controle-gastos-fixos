<?php
// =======================================================
// CONEXÃO LOCAL (Ativa para testes)
// =======================================================
$host = 'localhost';
$db   = 'financeiro_db'; // Lembre de conferir se o nome do banco local é esse mesmo
$user = 'root';
$pass = '';

/* 
=======================================================
CONEXÃO DA NUVEM (Comentada - O PHP ignora tudo aqui dentro)
=======================================================
$host = 'sqlXXX.epizy.com'; // O host da InfinityFree
$db   = 'if0_42456347_gastos';
$user = 'if0_42456347';
$pass = 'sua_senha_da_nuvem_aqui';
*/

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro ao conectar com o banco de dados: " . $e->getMessage());
}
?>