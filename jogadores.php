<?php
require_once 'config.php';
if (function_exists('verificarLogin')) {
    verificarLogin();
}

$mensagem = '';
$termoBusca = trim($_GET['busca'] ?? '');

// 1. EXCLUSÃO DE JOGADOR
if (isset($_GET['excluir'])) {
    $idJogador = (int)$_GET['excluir'];
    try {
        $stmt = $conn->prepare("DELETE FROM jogadores WHERE id_jogador = ?");
        $stmt->execute([$idJogador]);
        $mensagem = "Jogador excluído com sucesso!";
    } catch (PDOException $e) {
        $mensagem = "Erro ao excluir jogador: " . $e->getMessage();
    }
}

// 2. CADASTRO DE NOVO JOGADOR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_jogador'])) {
    $nome = trim($_POST['nome']);
    $documento = trim($_POST['documento']);
    $posicao = trim($_POST['posicao_funcao']);
    $idTime = (int)$_POST['id_time'];

    if (!empty($nome) && !empty($documento) && $idTime > 0) {
        try {
            $stmt = $conn->prepare("INSERT INTO jogadores (nome, documento, posicao_funcao, id_time) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nome, $documento, $posicao, $idTime]);
            $mensagem = "Jogador cadastrado com sucesso!";
        } catch (PDOException $e) {
            $mensagem = "Erro ao cadastrar jogador (verifique se o documento é único): " . $e->getMessage();
        }
    } else {
        $mensagem = "Preencha todos os campos obrigatórios (*)!";
    }
}

// 3. CONSULTA DE TIMES PARA O CAMPO SELECT
$times = $conn->query("SELECT id_time, nome FROM times ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

// 4. LISTAGEM DOS JOGADORES COM FILTRO DE BUSCA
$sql = "
    SELECT j.*, t.nome AS nome_time 
    FROM jogadores j
    JOIN times t ON j.id_time = t.id_time
    WHERE j.nome LIKE :busca OR j.documento LIKE :busca OR t.nome LIKE :busca
    ORDER BY j.nome ASC
";
$stmt = $conn->prepare($sql);
$stmt->execute(['busca' => "%$termoBusca%"]);
$jogadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Gestão de Jogadores</title>
</head>
<body>
    <div class="top-bar">
        <a href="index.php">← Voltar ao Menu Principal</a>
    </div>

    <h2>Gestão de Atletas</h2>

    <?php if (!empty($mensagem)): ?>
        <div class="msg"><?= htmlspecialchars($mensagem) ?></div>
    <?php endif; ?>

    <!-- FORMULÁRIO DE CADASTRO -->
    <div class="form-section">
        <h3>+ Novo Jogador</h3>
        <form method="POST">
            <label>Nome Completo *</label><br>
            <input type="text" name="nome" placeholder="Ex: Lucas Mendes" required style="width: 100%; margin-bottom: 10px;"><br>

            <label>Documento / CPF *</label><br>
            <input type="text" name="documento" placeholder="000.000.000-00" required style="width: 100%; margin-bottom: 10px;"><br>

            <label>Posição ou Função no Jogo</label><br>
            <input type="text" name="posicao_funcao" placeholder="Ex: Atacante, Mid Laner, Levantador" style="width: 100%; margin-bottom: 10px;"><br>

            <label>Time / Equipe *</label><br>
            <select name="id_time" required style="width: 100%; margin-bottom: 15px;">
                <option value="">-- Selecione o Time --</option>
                <?php foreach ($times as $t): ?>
                    <option value="<?= $t['id_time'] ?>"><?= htmlspecialchars($t['nome']) ?></option>
                <?php endforeach; ?>
            </select><br>

            <button type="submit" name="salvar_jogador">Cadastrar Atleta</button>
        </form>
    </div>

    <!-- CAMPO DE BUSCA -->
    <form method="GET" style="margin-bottom: 20px;">
        <input type="text" name="busca" placeholder="Buscar por jogador, CPF ou time..." value="<?= htmlspecialchars($termoBusca) ?>">
        <button type="submit">Buscar</button>
        <a href="jogadores.php">Limpar Filtro</a>
    </form>

    <!-- LISTAGEM -->
    <h3>Jogadores Cadastrados</h3>
    <table>
        <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>Documento</th>
            <th>Posição / Função</th>
            <th>Time</th>
            <th>Ações</th>
        </tr>
        <?php foreach ($jogadores as $j): ?>
        <tr>
            <td><?= $j['id_jogador'] ?></td>
            <td><?= htmlspecialchars($j['nome']) ?></td>
            <td><?= htmlspecialchars($j['documento']) ?></td>
            <td><?= htmlspecialchars($j['posicao_funcao'] ?? '-') ?></td>
            <td><strong><?= htmlspecialchars($j['nome_time']) ?></strong></td>
            <td>
                <a href="?excluir=<?= $j['id_jogador'] ?>" onclick="return confirm('Tem certeza que deseja excluir este jogador?')">Excluir</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>