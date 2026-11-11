<?php 
require_once 'config.php';

verificarLogin();

// Lógica de Sair / Logout Corrigida
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campeonato Blue Lock ⚽🏆 - Painel Principal</title>
</head>
<body>

    <div class="header">
        <h1> Sistema Blue Lock </h1>
        <div>
            <span>Usuário: <strong><?= htmlspecialchars($_SESSION['usuario_nome'] ?? 'Técnico') ?></strong></span> | 
            <a href="?logout=1" class="btn-sair">Sair ⚽</a>
        </div>
    </div>

    <hr>

    <h2>Main Menu</h2>
    <ul class="menu-list">
        <li><a href="agendamento.php">"Gestão de Partidas, Agendamentos e Pontuação.</a></li>
        <li><a href="jogadores.php">"Gestão de Atletas e jogadores.</a></li>

    </ul>

</body>
</html>