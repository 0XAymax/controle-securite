<?php
include 'config.php';
session_start();

// CORRIGÉ : Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// CORRIGÉ : Récupération sécurisée des projets
$query = "SELECT * FROM projets";
$projets = $pdo->query($query)->fetchAll();
?>

<h1>Bienvenue, <?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?> !</h1>
<p>Votre rôle : <?php echo htmlspecialchars($_SESSION['role'], ENT_QUOTES, 'UTF-8'); ?></p>

<hr>
<h2>Liste des Projets du Système</h2>
<table border="1">
    <tr>
        <th>ID</th>
        <th>Titre</th>
        <th>Budget</th>
        <th>Action</th>
    </tr>
    <?php foreach ($projets as $p): ?>
    <tr>
        <td><?php echo htmlspecialchars($p['id'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($p['titre'], ENT_QUOTES, 'UTF-8'); ?></td> 
        <td><?php echo htmlspecialchars($p['budget'], ENT_QUOTES, 'UTF-8'); ?> €</td>
        <td>
            <a href="project_detail.php?id=<?php echo htmlspecialchars($p['id'], ENT_QUOTES, 'UTF-8'); ?>">Voir détails</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<br>
<a href="logout.php">Déconnexion</a>