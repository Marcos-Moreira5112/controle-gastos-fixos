<?php
require_once 'conexao.php';

// 1. Descobre o mês, a data e a ABA ativa
$mes_atual_sistema = date('m/Y');
$mes_ativo = isset($_GET['mes']) ? $_GET['mes'] : $mes_atual_sistema;
$dia_hoje = (int)date('d');

// Define qual aba está aberta (Padrão: Pessoal)
$tipo_ativo = isset($_GET['tipo']) ? $_GET['tipo'] : 'Pessoal';

// =========================================================================
// AUTOMAÇÃO DE VIRADA DE MÊS
// =========================================================================
try {
    $check_mes = $pdo->prepare("SELECT COUNT(*) FROM gastos WHERE mes_ano = :mes_ano");
    $check_mes->execute([':mes_ano' => $mes_atual_sistema]);
    $ja_tem_gastos = $check_mes->fetchColumn();

    if ($ja_tem_gastos == 0) {
        $mes_anterior = date('m/Y', strtotime("-1 month"));
        $stmt_ant = $pdo->prepare("SELECT nome, valor, dia_vencimento, tipo_conta FROM gastos WHERE mes_ano = :mes_anterior");
        $stmt_ant->execute([':mes_anterior' => $mes_anterior]);
        $gastos_copiar = $stmt_ant->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($gastos_copiar)) {
            foreach ($gastos_copiar as $gasto) {
                $insere = $pdo->prepare("INSERT INTO gastos (nome, valor, dia_vencimento, status, mes_ano, tipo_conta) VALUES (:nome, :valor, :dia_vencimento, 'Pendente', :mes_ano, :tipo_conta)");
                $insere->execute([
                    ':nome' => $gasto['nome'],
                    ':valor' => $gasto['valor'],
                    ':dia_vencimento' => $gasto['dia_vencimento'],
                    ':mes_ano' => $mes_atual_sistema,
                    ':tipo_conta' => $gasto['tipo_conta']
                ]);
            }
        }
    }
} catch (PDOException $e) {
    die("Erro ao processar virada de mês: " . $e->getMessage());
}

// =========================================================================
// 2. Salva uma nova conta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $valor = $_POST['valor'] ?? '';
    $dia_vencimento = $_POST['dia_vencimento'] ?? '';

    if (!empty($nome) && !empty($valor) && !empty($dia_vencimento)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO gastos (nome, valor, dia_vencimento, mes_ano, tipo_conta) VALUES (:nome, :valor, :dia_vencimento, :mes_ano, :tipo_conta)");
            $stmt->execute([
                ':nome' => $nome,
                ':valor' => $valor,
                ':dia_vencimento' => $dia_vencimento,
                ':mes_ano' => $mes_ativo,
                ':tipo_conta' => $tipo_ativo
            ]);
            header("Location: index.php?mes=" . $mes_ativo . "&tipo=" . $tipo_ativo);
            exit;
        } catch (PDOException $e) {
            echo "Erro ao salvar: " . $e->getMessage();
        }
    }
}

// 3. Busca histórico de meses
try {
    $stmt_meses = $pdo->query("SELECT DISTINCT mes_ano FROM gastos ORDER BY id DESC");
    $lista_meses = $stmt_meses->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array($mes_atual_sistema, $lista_meses)) {
        array_unshift($lista_meses, $mes_atual_sistema);
    }
} catch (PDOException $e) {
    die("Erro ao buscar histórico: " . $e->getMessage());
}

// 4. Busca os gastos
try {
    $stmt = $pdo->prepare("SELECT * FROM gastos WHERE mes_ano = :mes_ano AND tipo_conta = :tipo_conta ORDER BY id ASC");
    $stmt->execute([':mes_ano' => $mes_ativo, ':tipo_conta' => $tipo_ativo]);
    $todos_gastos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao buscar gastos: " . $e->getMessage());
}

// 5. Separa os totais
$total_geral = 0; $total_pago = 0; $total_restante = 0;
foreach ($todos_gastos as $gasto) {
    $total_geral += $gasto['valor'];
    if ($gasto['status'] === 'Pago') {
        $total_pago += $gasto['valor'];
    } else {
        $total_restante += $gasto['valor'];
    }
}
$progresso = $total_geral > 0 ? ($total_pago / $total_geral) * 100 : 0;

// 6. Função para exibir as colunas
function exibirBlocoVencimento($lista_gastos, $dia_alvo, $dia_hoje, $mes_ativo, $mes_atual_sistema) {
    $encontrou = false;
    $tem_atrasado = false;
    $html_itens = '';
    
    foreach ($lista_gastos as $gasto) {
        if ((int)$gasto['dia_vencimento'] === (int)$dia_alvo) {
            $encontrou = true;
            $pago = ($gasto['status'] === 'Pago');
            $classe_pago = $pago ? 'item-pago' : '';
            $checked = $pago ? 'checked' : '';
            $valor_formatado = "R$ " . number_format($gasto['valor'], 2, ',', '.');
            
            if ($dia_hoje > $dia_alvo && !$pago && $mes_ativo === $mes_atual_sistema) {
                $tem_atrasado = true;
            }
            
            $nome_escapado = htmlspecialchars($gasto['nome'], ENT_QUOTES);

            $html_itens .= '
            <div class="gasto-card ' . $classe_pago . '">
                <div class="gasto-main">
                    <label class="custom-checkbox">
                        <input type="checkbox" ' . $checked . ' data-id="' . $gasto['id'] . '">
                        <span class="checkmark"></span>
                    </label>
                    <div class="gasto-detalhes">
                        <span class="gasto-nome">' . $nome_escapado . '</span>
                        <span class="gasto-valor">' . $valor_formatado . '</span>
                    </div>
                </div>
                
                <!-- MENU DE 3 PONTINHOS -->
                <div class="menu-container">
                    <button class="btn-icon btn-dots" title="Opções">⋮</button>
                    <div class="dropdown-menu">
                        <button class="dropdown-item btn-editar" 
                            data-id="' . $gasto['id'] . '" 
                            data-nome="' . $nome_escapado . '" 
                            data-valor="' . $gasto['valor'] . '" 
                            data-vencimento="' . $gasto['dia_vencimento'] . '">
                            ✏️ Editar
                        </button>
                        <button class="dropdown-item btn-excluir" data-id="' . $gasto['id'] . '">
                            🗑️ Excluir
                        </button>
                    </div>
                </div>
            </div>';
        }
    }
    
    $classe_coluna = $tem_atrasado ? 'coluna-atrasada' : '';
    $badge_atraso = $tem_atrasado ? '<span class="badge badge-danger">Atrasado</span>' : '';

    echo '<div class="board-column ' . $classe_coluna . '">';
    echo '<div class="column-header">';
    echo '<h2>Dia ' . str_pad($dia_alvo, 2, '0', STR_PAD_LEFT) . '</h2>';
    echo $badge_atraso;
    echo '</div>';
    
    echo '<div class="column-content">';
    if ($encontrou) {
        echo $html_itens;
    } else {
        echo '<div class="empty-state">Nenhuma conta</div>';
    }
    echo '</div></div>';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Financeiro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="estilo.css?v=6.0">
</head>
<body>

    <nav class="topbar">
        <div class="topbar-logo">
            <div class="logo-icon">💸</div>
            <h1>Financeiro</h1>
        </div>
        <div class="topbar-tabs">
            <a href="index.php?mes=<?= $mes_ativo ?>&tipo=Pessoal" class="tab <?= $tipo_ativo === 'Pessoal' ? 'active' : '' ?>">👤 Pessoal</a>
            <a href="index.php?mes=<?= $mes_ativo ?>&tipo=Empresa" class="tab <?= $tipo_ativo === 'Empresa' ? 'active' : '' ?>">💼 Empresa</a>
        </div>
        <div class="topbar-actions">
            <select class="mes-selector" onchange="window.location.href='index.php?tipo=<?= $tipo_ativo ?>&mes=' + this.value">
                <?php foreach ($lista_meses as $mes_opcao): ?>
                    <option value="<?= $mes_opcao ?>" <?= $mes_opcao === $mes_ativo ? 'selected' : '' ?>><?= $mes_opcao ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn-primary" onclick="abrirModal()">+ Nova Conta</button>
        </div>
    </nav>

    <main class="app-container">
        <section class="stats-grid">
            <div class="stat-card stat-total">
                <div class="stat-icon">📊</div>
                <div class="stat-info">
                    <p>Total do Mês</p>
                    <h3>R$ <?= number_format($total_geral, 2, ',', '.') ?></h3>
                </div>
            </div>
            
            <div class="stat-card stat-pago">
                <div class="stat-icon">✓</div>
                <div class="stat-info">
                    <p>Contas Pagas</p>
                    <h3>R$ <?= number_format($total_pago, 2, ',', '.') ?></h3>
                </div>
                <div class="progress-bar-bg"><div class="progress-bar-fill" style="width: <?= $progresso ?>%"></div></div>
            </div>

            <div class="stat-card stat-pendente">
                <div class="stat-icon">⏳</div>
                <div class="stat-info">
                    <p>Falta Pagar</p>
                    <h3>R$ <?= number_format($total_restante, 2, ',', '.') ?></h3>
                </div>
            </div>
        </section>

        <section class="kanban-board">
            <?php exibirBlocoVencimento($todos_gastos, 5, $dia_hoje, $mes_ativo, $mes_atual_sistema); ?>
            <?php exibirBlocoVencimento($todos_gastos, 10, $dia_hoje, $mes_ativo, $mes_atual_sistema); ?>
            <?php exibirBlocoVencimento($todos_gastos, 20, $dia_hoje, $mes_ativo, $mes_atual_sistema); ?>
            <?php exibirBlocoVencimento($todos_gastos, 30, $dia_hoje, $mes_ativo, $mes_atual_sistema); ?>
        </section>
    </main>

    <!-- MODAL DE CADASTRO -->
    <div id="modal-cadastro" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h2>Adicionar Conta <?= $tipo_ativo ?></h2>
                <button class="btn-close" onclick="fecharModal()">×</button>
            </div>
            <form method="POST" class="modal-form">
                <div class="form-group">
                    <label>Descrição da Conta</label>
                    <input type="text" name="nome" placeholder="Ex: Conta de Luz, Internet..." required autofocus>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Valor (R$)</label>
                        <input type="number" name="valor" step="0.01" placeholder="0,00" required>
                    </div>
                    <div class="form-group">
                        <label>Vencimento</label>
                        <select name="dia_vencimento" required>
                            <option value="5">Dia 05</option>
                            <option value="10">Dia 10</option>
                            <option value="20">Dia 20</option>
                            <option value="30">Dia 30</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn-submit">Salvar Conta</button>
            </form>
        </div>
    </div>

    <!-- MODAL DE EDIÇÃO -->
    <div id="modal-editar" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h2>Editar Conta</h2>
                <button class="btn-close" onclick="fecharModalEditar()">×</button>
            </div>
            <form id="form-editar" class="modal-form">
                <input type="hidden" id="edit-id" name="id">
                <div class="form-group">
                    <label>Descrição da Conta</label>
                    <input type="text" id="edit-nome" name="nome" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Valor (R$)</label>
                        <input type="number" id="edit-valor" name="valor" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Vencimento</label>
                        <select id="edit-vencimento" name="dia_vencimento" required>
                            <option value="5">Dia 05</option>
                            <option value="10">Dia 10</option>
                            <option value="20">Dia 20</option>
                            <option value="30">Dia 30</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn-submit">Salvar Alterações</button>
            </form>
        </div>
    </div>

    <script src="script.js?v=6.0"></script>
</body>
</html>