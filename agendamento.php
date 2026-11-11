<?php
require_once 'config.php';
if (function_exists('verificarLogin')) {
    verificarLogin();
}

$mensagem = '';
$termoBusca = trim($_GET['busca'] ?? '');

// 1. CANCELAR / EXCLUIR PARTIDA
if (isset($_GET['excluir'])) {
    $idPartida = (int)$_GET['excluir'];
    try {
        $stmt = $conn->prepare("DELETE FROM partidas WHERE id_partida = ?");
        $stmt->execute([$idPartida]);
        $mensagem = "Partida removida com sucesso!";
    } catch (PDOException $e) {
        $mensagem = "Erro ao excluir partida: " . $e->getMessage();
    }
}

// 2. AGENDAR NOVA PARTIDA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agendar_partida'])) {
    $fase = $_POST['fase'];
    $dataPrevista = $_POST['data_prevista'];
    $idTimeCasa = (int)$_POST['id_time_casa'];
    $idTimeVisitante = (int)$_POST['id_time_visitante'];
    $idArbitro = (int)$_POST['id_arbitro'];
    $observacoes = trim($_POST['observacoes'] ?? '');

    if ($idTimeCasa === $idTimeVisitante) {
        $mensagem = "Erro: O time da casa e o visitante não podem ser o mesmo!";
    } elseif (!empty($fase) && !empty($dataPrevista) && $idTimeCasa > 0 && $idTimeVisitante > 0 && $idArbitro > 0) {
        try {
            $stmt = $conn->prepare("INSERT INTO partidas (fase, data_prevista, status, id_time_casa, id_time_visitante, id_arbitro, observacoes) VALUES (?, ?, 'AGENDADA', ?, ?, ?, ?)");
            $stmt->execute([$fase, $dataPrevista, $idTimeCasa, $idTimeVisitante, $idArbitro, $observacoes]);
            $mensagem = "Partida agendada com sucesso!";
        } catch (PDOException $e) {
            $mensagem = "Erro ao agendar partida: " . $e->getMessage();
        }
    } else {
        $mensagem = "Preencha todos os campos obrigatórios (*)!";
    }
}

// 3. REGISTRAR SÚMULA / FINALIZAR PARTIDA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_sumula'])) {
    $idPartida = (int)$_POST['id_partida'];
    $placarCasa = (int)$_POST['placar_casa'];
    $placarVisitante = (int)$_POST['placar_visitante'];
    $dataRealizacao = $_POST['data_realizacao'] ?? date('Y-m-d H:i:s');
    $observacoes = trim($_POST['observacoes'] ?? '');

    try {
        $stmt = $conn->prepare("UPDATE partidas SET placar_casa = ?, placar_visitante = ?, data_realizacao = ?, status = 'CONCLUIDA', observacoes = ? WHERE id_partida = ?");
        $stmt->execute([$placarCasa, $placarVisitante, $dataRealizacao, $observacoes, $idPartida]);
        $mensagem = "Súmula registrada e partida concluída!";
    } catch (PDOException $e) {
        $mensagem = "Erro ao registrar súmula: " . $e->getMessage();
    }
}

// CONSULTAS AUXILIARES PARA POPULAR OS CAMPOS SELECT
$times = $conn->query("SELECT id_time, nome FROM times ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$arbitros = $conn->query("SELECT id_usuario, nome FROM usuarios WHERE perfil = 'ARBITRO' OR perfil = 'ADMIN' ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

// VERIFICAÇÃO DE MATERIAIS COM ESTOQUE BAIXO PARA ALERTAS
$estoqueBaixo = $conn->query("SELECT nome, quantidade_estoque, estoque_minimo FROM equipamentos WHERE quantidade_estoque <= estoque_minimo")->fetchAll(PDO::FETCH_ASSOC);

// LISTAGEM DE PARTIDAS EM ORDEM CRONOLÓGICA
$sqlPartidas = "
    SELECT p.*, 
           tc.nome AS time_casa, 
           tv.nome AS time_visitante, 
           u.nome AS arbitro
    FROM partidas p
    JOIN times tc ON p.id_time_casa = tc.id_time
    JOIN times tv ON p.id_time_visitante = tv.id_time
    JOIN usuarios u ON p.id_arbitro = u.id_usuario
    WHERE tc.nome LIKE :busca OR tv.nome LIKE :busca OR p.fase LIKE :busca
    ORDER BY p.data_prevista ASC
";
$stmtP = $conn->prepare($sqlPartidas);
$stmtP->execute(['busca' => "%$termoBusca%"]);
$partidas = $stmtP->fetchAll(PDO::FETCH_ASSOC);

$dataAtual = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Gestão de Partidas e Súmulas</title>
</head>
<body>
    <div class="top-bar">
        <a href="index.php">← Voltar ao Menu Principal</a>
    </div>

    <h2>Gestão de Partidas e Súmulas</h2>

    <!-- EXIBIÇÃO DE MENSAGENS -->
    <?php if (!empty($mensagem)): ?>
        <div class="msg"><?= htmlspecialchars($mensagem) ?></div>
    <?php endif; ?>

    <!-- ALERTAS AUTOMÁTICOS DE ESTOQUE MÍNIMO -->
    <?php if (!empty($estoqueBaixo)): ?>
        <div class="alerta-box">
            <strong>⚠️ Atenção! Materiais abaixo do estoque mínimo:</strong>
            <ul>
                <?php foreach ($estoqueBaixo as $item): ?>
                    <li><?= htmlspecialchars($item['nome']) ?> — Estoque: <strong><?= $item['quantidade_estoque'] ?></strong> (Mínimo: <?= $item['estoque_minimo'] ?>)</li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- CAMPO DE BUSCA -->
    <form method="GET" style="margin-bottom: 20px;">
        <input type="text" name="busca" placeholder="Buscar por time ou fase..." value="<?= htmlspecialchars($termoBusca) ?>">
        <button type="submit">Buscar</button>
        <a href="partidas.php">Limpar Filtro</a>
    </form>

    <div style="display: flex; gap: 20px;">
        <!-- FORMULÁRIO DE AGENDAMENTO -->
        <div class="form-section" style="flex: 1;">
            <h3>+ Agendar Nova Partida</h3>
            <form method="POST">
                <label>Fase do Campeonato *</label><br>
                <select name="fase" required style="width: 100%; margin-bottom: 10px;">
                    <option value="CLASSIFICATORIA">Classificatória</option>
                    <option value="ELIMINATORIA">Eliminatória</option>
                </select><br>

                <label>Data e Hora Prevista *</label><br>
                <input type="datetime-local" name="data_prevista" required style="width: 100%; margin-bottom: 10px;"><br>

                <label>Time Mandante (Casa) *</label><br>
                <select name="id_time_casa" required style="width: 100%; margin-bottom: 10px;">
                    <option value="">-- Selecione --</option>
                    <?php foreach ($times as $t): ?>
                        <option value="<?= $t['id_time'] ?>"><?= htmlspecialchars($t['nome']) ?></option>
                    <?php endforeach; ?>
                </select><br>

                <label>Time Visitante *</label><br>
                <select name="id_time_visitante" required style="width: 100%; margin-bottom: 10px;">
                    <option value="">-- Selecione --</option>
                    <?php foreach ($times as $t): ?>
                        <option value="<?= $t['id_time'] ?>"><?= htmlspecialchars($t['nome']) ?></option>
                    <?php endforeach; ?>
                </select><br>

                <label>Árbitro Responsável *</label><br>
                <select name="id_arbitro" required style="width: 100%; margin-bottom: 10px;">
                    <option value="">-- Selecione --</option>
                    <?php foreach ($arbitros as $a): ?>
                        <option value="<?= $a['id_usuario'] ?>"><?= htmlspecialchars($a['nome']) ?></option>
                    <?php endforeach; ?>
                </select><br>

                <label>Observações / Local</label><br>
                <textarea name="observacoes" style="width: 100%; margin-bottom: 10px;" rows="2"></textarea><br>

                <button type="submit" name="agendar_partida">Salvar Agendamento</button>
            </form>
        </div>

        <!-- REGISTRO DE SÚMULA DE PARTIDA -->
        <div class="form-section" style="flex: 1;">
            <h3>📝 Registrar Súmula / Resultado</h3>
            <form method="POST">
                <label>Selecione a Partida Agendada *</label><br>
                <select name="id_partida" required style="width: 100%; margin-bottom: 10px;">
                    <option value="">-- Selecione a Partida --</option>
                    <?php foreach ($partidas as $p): ?>
                        <?php if ($p['status'] !== 'CONCLUIDA'): ?>
                            <option value="<?= $p['id_partida'] ?>">
                                [#<?= $p['id_partida'] ?>] <?= htmlspecialchars($p['time_casa']) ?> x <?= htmlspecialchars($p['time_visitante']) ?> (<?= date('d/m/Y H:i', strtotime($p['data_prevista'])) ?>)
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select><br>

                <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                    <div style="flex: 1;">
                        <label>Placar Casa</label>
                        <input type="number" name="placar_casa" value="0" min="0" required style="width: 100%;">
                    </div>
                    <div style="flex: 1;">
                        <label>Placar Visitante</label>
                        <input type="number" name="placar_visitante" value="0" min="0" required style="width: 100%;">
                    </div>
                </div>

                <label>Data/Hora Real de Conclusão</label><br>
                <input type="datetime-local" name="data_realizacao" value="<?= date('Y-m-d\TH:i') ?>" style="width: 100%; margin-bottom: 10px;"><br>

                <label>Observações da Súmula</label><br>
                <textarea name="observacoes" style="width: 100%; margin-bottom: 10px;" rows="2" placeholder="Ocorrências da partida..."></textarea><br>

                <button type="submit" name="salvar_sumula">Finalizar Partida</button>
            </form>
        </div>
    </div>

    <!-- LISTAGEM CRONOLÓGICA DAS PARTIDAS E ALERTAS VISUAIS -->
    <h3>Tabela de Partidas e Confrontos</h3>
    <table>
        <tr>
            <th>ID</th>
            <th>Fase</th>
            <th>Data Prevista</th>
            <th>Confronto (Casa x Visitante)</th>
            <th>Placar</th>
            <th>Árbitro</th>
            <th>Status / Alerta</th>
            <th>Ação</th>
        </tr>
        <?php foreach ($partidas as $p): 
            $dataPrevistaApenas = date('Y-m-d', strtotime($p['data_prevista']));
            $isAtrasada = ($p['status'] === 'AGENDADA' && $p['data_prevista'] < date('Y-m-d H:i:s'));
            $isHoje = ($p['status'] === 'AGENDADA' && $dataPrevistaApenas === $dataAtual);
            
            $trClass = '';
            if ($isAtrasada) $trClass = 'status-atrasada';
            elseif ($isHoje) $trClass = 'status-hoje';
            elseif ($p['status'] === 'CONCLUIDA') $trClass = 'status-concluida';
        ?>
        <tr class="<?= $trClass ?>">
            <td><?= $p['id_partida'] ?></td>
            <td><?= htmlspecialchars($p['fase']) ?></td>
            <td><?= date('d/m/Y H:i', strtotime($p['data_prevista'])) ?></td>
            <td><strong><?= htmlspecialchars($p['time_casa']) ?></strong> x <strong><?= htmlspecialchars($p['time_visitante']) ?></strong></td>
            <td>
                <?php if ($p['status'] === 'CONCLUIDA'): ?>
                    <strong><?= $p['placar_casa'] ?> - <?= $p['placar_visitante'] ?></strong>
                <?php else: ?>
                    <span style="color: #6c757d;">- x -</span>
                <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($p['arbitro']) ?></td>
            <td>
                <?php if ($p['status'] === 'CONCLUIDA'): ?>
                    <span class="badge badge-concluido">Concluída</span>
                <?php elseif ($isAtrasada): ?>
                    <span class="badge badge-atrasado">⚠️ Atrasada / Pendente</span>
                <?php elseif ($isHoje): ?>
                    <span class="badge badge-hoje">📅 Hoje</span>
                <?php else: ?>
                    <span class="badge badge-agendado">Agendada</span>
                <?php endif; ?>
            </td>
            <td>
                <a href="?excluir=<?= $p['id_partida'] ?>" onclick="return confirm('Tem certeza que deseja cancelar/remover esta partida?')">Excluir</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>