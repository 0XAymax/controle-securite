<?php
include 'config.php';
session_start();

// CORRIGÉ : Vérification de l'authentification et de l'autorisation
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

$users = $pdo->query("SELECT id, username, role FROM users")->fetchAll();
?>
<h2>Administration des utilisateurs</h2>
<table border="1">
    <tr><th>ID</th><th>Username</th><th>Rôle</th></tr>
    <?php foreach($users as $u): ?>
    <tr>
        <td><?= htmlspecialchars($u['id'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<a href="dashboard.php">Retour</a>