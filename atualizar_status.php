<?php
require_once 'conexao.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : '';

    if ($id > 0 && in_array($status, ['Pago', 'Pendente'])) {
        try {
            $stmt = $pdo->prepare("UPDATE gastos SET status = :status WHERE id = :id");
            $stmt->execute([
                ':status' => $status,
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

echo json_encode(['success' => false, 'error' => 'Parâmetros inválidos']);
exit;