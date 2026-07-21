<?php
require_once 'conexao.php';

$dados = json_decode(file_get_contents("php://input"), true);

if (isset($dados['id'])) {
    $id = $dados['id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM gastos WHERE id = :id");
        $stmt->execute([':id' => $id]);
        
        echo json_encode(['sucesso' => true]);
    } catch (PDOException $e) {
        echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
    }
}
?>