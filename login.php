<?php 
require_once 'config.php';

if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit();
}

$mensagemErro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        $mensagemErro = 'Por favor, preencha todos os campos.';
    } else {
        try {
            $sql = "SELECT * FROM usuarios WHERE email = :email";
            $stmt = $conn->prepare($sql);
            $stmt->execute(['email' => $email]);
            $usuario = $stmt->fetch();

            if ($usuario && $senha === $usuario['senha']) {
                $_SESSION['usuario_id'] = $usuario['id_usuario'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                $_SESSION['usuario_email'] = $usuario['email'];
                header('Location: index.php');
                exit();
            } else {
                $mensagemErro = 'E-mail ou senha incorretos.';
            }
        } catch (PDOException $e) {
            $mensagemErro = 'Erro ao acessar o banco: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Login - Gestão de Manutenção</title>
</head>
<body>
    <div class="login-card">
        <h2>Acesso ao Sistema</h2>
        <?php if (!empty($mensagemErro)): ?>
            <div class="erro"><?= htmlspecialchars($mensagemErro) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="email">E-mail:</label>
                <input type="email" name="email" id="email" required>
            </div>
            <div class="form-group">
                <label for="senha">Password:</label>
                <input type="password" name="senha" id="senha" required>
            </div>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>