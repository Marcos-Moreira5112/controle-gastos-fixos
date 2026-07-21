<?php
require_once 'conexao.php';

// 1. Descobre o mês ativo e a data de hoje para os alertas
$mes_atual_sistema = date('m/Y');
$mes_ativo = isset($_GET['mes']) ? $_GET['mes'] : $mes_atual_sistema;
$dia_hoje = (int)date('d');

// =========================================================================
// AUTOMAÇÃO DE VIRADA DE MÊS
// =========================================================================
try {
    $check_mes = $pdo->prepare("SELECT COUNT(*) FROM gastos WHERE mes_ano = :mes_ano");
    $check_mes->execute([':mes_ano' => $mes_atual_sistema]);
    $ja_tem_gastos = $check_mes->fetchColumn();

    if ($ja_tem_gastos == 0) {
        $mes_anterior = date('m/Y', strtotime("-1 month"));
        
        $stmt_ant = $pdo->prepare("SELECT nome, valor, dia_vencimento FROM gastos WHERE mes_ano = :mes_anterior");
        $stmt_ant->execute([':mes_anterior' => $mes_anterior]);
        $gastos_copiar = $stmt_ant->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($gastos_copiar)) {
            foreach ($gastos_copiar as $gasto) {
                $insere = $pdo->prepare("INSERT INTO gastos (nome, valor, dia_vencimento, status, mes_ano) VALUES (:nome, :valor, :dia_vencimento, 'Pendente', :mes_ano)");
                $insere->execute([
                    ':nome' => $gasto['nome'],
                    ':valor' => $gasto['valor'],
                    ':dia_vencimento' => $gasto['dia_vencimento'],
                    ':mes_ano' => $mes_atual_sistema
                ]);
            }
        }
    }
} catch (PDOException $e) {
    die("Erro ao processar virada de mês: " . $e->getMessage());
}
// =========================================================================

// 2. Salva uma nova conta enviada pelo formulário (via AJAX/Fetch)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $valor = $_POST['valor'] ?? '';
    $dia_vencimento = $_POST['dia_vencimento'] ?? '';

    if (!empty($nome) && !empty($valor) && !empty($dia_vencimento)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO gastos (nome, valor, dia_vencimento, mes_ano) VALUES (:nome, :valor, :dia_vencimento, :mes_ano)");
            $stmt->execute([
                ':nome' => $nome,
                ':valor' => $valor,
                ':dia_vencimento' => $dia_vencimento,
                ':mes_ano' => $mes_ativo
            ]);
            header("Location: index.php?mes=" . $mes_ativo);
            exit;
        } catch (PDOException $e) {
            echo "Erro ao salvar: " . $e->getMessage();
        }
    }
}

// 3. Busca a lista de todos os meses para o histórico
try {
    $stmt_meses = $pdo->query("SELECT DISTINCT mes_ano FROM gastos ORDER BY id DESC");
    $lista_meses = $stmt_meses->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array($mes_atual_sistema, $lista_meses)) {
        array_unshift($lista_meses, $mes_atual_sistema);
    }
} catch (PDOException $e) {
    die("Erro ao buscar histórico de meses: " . $e->getMessage());
}

// 4. Busca os gastos ativos do mês selecionado
try {
    $stmt = $pdo->prepare("SELECT * FROM gastos WHERE mes_ano = :mes_ano ORDER BY id ASC");
    $stmt->execute([':mes_ano' => $mes_ativo]);
    $todos_gastos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao buscar gastos: " . $e->getMessage());
}

// 5. Separa os totais (Painel de Restante a Pagar)
$total_geral = 0;
$total_pago = 0;
$total_restante = 0;

foreach ($todos_gastos as $gasto) {
    $total_geral += $gasto['valor'];
    if ($gasto['status'] === 'Pago') {
        $total_pago += $gasto['valor'];
    } else {
        $total_restante += $gasto['valor'];
    }
}

// 6. Função interna para exibir a lista e checar atrasos de vencimento
function exibirBlocoVencimento($lista_gastos, $dia_alvo, $dia_hoje, $mes_ativo, $mes_atual_sistema) {
    $encontrou = false;
    $tem_atrasado = false;
    $html_itens = '';
    
    foreach ($lista_gastos as $gasto) {
        if ((int)$gasto['dia_vencimento'] === (int)$dia_alvo) {
            $encontrou = true;
            $classe_pago = ($gasto['status'] === 'Pago') ? 'pago' : '';
            $checked = ($gasto['status'] === 'Pago') ? 'checked' : '';
            $valor_formatado = "R$ " . number_format($gasto['valor'], 2, ',', '.');
            
            // Regra do Alerta: Se o dia de hoje passou do vencimento, está pendente E é o mês atual do calendário
            if ($dia_hoje > $dia_alvo && $gasto['status'] !== 'Pago' && $mes_ativo === $mes_atual_sistema) {
                $tem_atrasado = true;
            }
            
            $html_itens .= '
            <div class="item-gasto ' . $classe_pago . '">
                <div class="gasto-info">
                    <input type="checkbox" ' . $checked . ' data-id="' . $gasto['id'] . '">
                    <span class="gasto-nome">' . htmlspecialchars($gasto['nome']) . '</span>
                </div>
                <div class="gasto-acoes">
                    <span class="gasto-valor">' . $valor_formatado . '</span>
                    <button class="btn-excluir" data-id="' . $gasto['id'] . '" title="Excluir Gasto">🗑️</button>
                </div>
            </div>';
        }
    }
    
    // Define a classe de alerta caso tenha conta pendente atrasada
    $classe_bloco_alerta = $tem_atrasado ? 'bloco-atrasado' : '';
    $tag_atraso = $tem_atrasado ? '<span class="tag-atraso">⚠️ Atrasado</span>' : '';

    echo '<div class="bloco-vencimento ' . $classe_bloco_alerta . '">';
    echo '<h3><span>📅 Vencimento - Dia ' . str_pad($dia_alvo, 2, '0', STR_PAD_LEFT) . '</span>' . $tag_atraso . '</h3>';
    
    if ($encontrou) {
        echo $html_itens;
    } else {
        echo '<p style="color: #a0aec0; font-size: 14px; font-style: italic;">Nenhuma conta para este vencimento.</p>';
    }
    echo '</div>';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Financeiro - Gastos Fixos</title>
    <!-- CSS linkado com controle de versão para burlar o cache do navegador -->
    <link rel="stylesheet" href="estilo.css?v=2.1">
</head>
<body>

    <div class="container">
        
        <header>
            <h1>Finanças Pessoais</h1>
            <p>Controle Mensal de Gastos Fixos</p>
        </header>

        <div class="filtro-historico">
            <label for="seletor-mes">Visualizar Mês:</label>
            <select id="seletor-mes">
                <?php foreach ($lista_meses as $mes_opcao): ?>
                    <option value="<?= $mes_opcao ?>" <?= $mes_opcao === $mes_ativo ? 'selected' : '' ?>>
                        <?= $mes_opcao ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Painel de Resumo Totalmente Clean e Reconectado ao estilo.css -->
        <div class="dashboard-resumo">
            <h2>Total do Mês (<?= $mes_ativo ?>)</h2>
            <div class="total-valor">R$ <?= number_format($total_geral, 2, ',', '.') ?></div>
            
            <div class="sub-valores">
                <div class="sub-box pago">
                    <p>🎉 Já Pago</p>
                    <span>R$ <?= number_format($total_pago, 2, ',', '.') ?></span>
                </div>
                <div class="sub-box restante">
                    <p>💸 Falta Pagar</p>
                    <span>R$ <?= number_format($total_restante, 2, ',', '.') ?></span>
                </div>
            </div>
        </div>

        <!-- Bloco de Cadastro Retrátil -->
        <div class="container-cadastro">
            <button id="btn-toggle" class="btn-toggle-cadastro">➕ Adicionar Novo Gasto</button>
            
            <div id="box-formulario" class="formulario-cadastro">
                <form id="form-cadastro">
                    <div class="form-group">
                        <label for="nome">Descrição / Nome da Conta</label>
                        <input type="text" id="nome" name="nome" placeholder="Ex: Aluguel, Internet..." required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="valor">Valor (R$)</label>
                            <input type="number" id="valor" name="valor" step="0.01" placeholder="0.00" required>
                        </div>

                        <div class="form-group">
                            <label for="dia_vencimento">Dia do Vencimento</label>
                            <select id="dia_vencimento" name="dia_vencimento" required>
                                <option value="5">Dia 05</option>
                                <option value="10">Dia 10</option>
                                <option value="20">Dia 20</option>
                                <option value="30">Dia 30</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn-salvar">Confirmar e Salvar</button>
                </form>
            </div>
        </div>

        <!-- Blocos Dinâmicos de Vencimento -->
        <?php exibirBlocoVencimento($todos_gastos, 5, $dia_hoje, $mes_ativo, $mes_atual_sistema); ?>
        <?php exibirBlocoVencimento($todos_gastos, 10, $dia_hoje, $mes_ativo, $mes_atual_sistema); ?>
        <?php exibirBlocoVencimento($todos_gastos, 20, $dia_hoje, $mes_ativo, $mes_atual_sistema); ?>
        <?php exibirBlocoVencimento($todos_gastos, 30, $dia_hoje, $mes_ativo, $mes_atual_sistema); ?>

    </div>

    <script src="script.js"></script>
</body>
</html>