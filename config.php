<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$port = '3306';
$dbname = 'campeonato_db';
$usuario = 'root';
$senha = 'mysql';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;port=$port;charset=utf8mb4", $usuario, $senha);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}

function verificarLogin() {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: login.php');
        exit();
    }
}
?>