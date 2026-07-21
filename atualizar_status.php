<?php
require_once 'conexao.php';

// Recebe os dados enviados pelo JavaScript
$dados = json_decode(file_get_contents("php://input"), true);

if (isset($dados['id']) && isset($dados['status'])) {
    $id = $dados['id'];
    $status = $dados['status']; // Será 'Pago' ou 'Pendente'

    try {
        $stmt = $pdo->prepare("UPDATE gastos SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $status, ':id' => $id]);
        
        // Responde para o JavaScript que deu certo
        echo json_encode(['sucesso' => true]);
    } catch (PDOException $e) {
        echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
    }
}
?>