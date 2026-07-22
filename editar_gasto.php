<?php
require_once 'conexao.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
    $valor = isset($_POST['valor']) ? (float)$_POST['valor'] : 0;
    $dia_vencimento = isset($_POST['dia_vencimento']) ? (int)$_POST['dia_vencimento'] : 0;

    if ($id > 0 && !empty($nome) && $valor > 0 && $dia_vencimento > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE gastos SET nome = :nome, valor = :valor, dia_vencimento = :dia_vencimento WHERE id = :id");
            $stmt->execute([
                ':nome' => $nome,
                ':valor' => $valor,
                ':dia_vencimento' => $dia_vencimento,
                ':id' => $id
            ]);

            echo json_encode(['success' => true]);
            exit;
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
}

echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
exit;