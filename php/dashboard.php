<?php
include 'config.php';
session_start();

// CORRIGÉ : Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Récupérer les infos utilisateur pour vérifier le statut MFA
$user_sql = "SELECT mfa_enabled FROM users WHERE id = :id";
$user_stmt = $pdo->prepare($user_sql);
$user_stmt->bindParam(':id', $_SESSION['user_id'], PDO::PARAM_INT);
$user_stmt->execute();
$current_user = $user_stmt->fetch(PDO::FETCH_ASSOC);

// CORRIGÉ : Récupération sécurisée des projets
$query = "SELECT * FROM projets";
$projets = $pdo->query($query)->fetchAll();
?>

<h1>Bienvenue, <?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?> !</h1>
<p>Votre rôle : <?php echo htmlspecialchars($_SESSION['role'], ENT_QUOTES, 'UTF-8'); ?></p>

<?php if ($current_user['mfa_enabled']): ?>
    <p style="color: green;">🔐 Authentification à deux facteurs activée</p>
<?php else: ?>
    <p style="color: orange;">⚠️ Authentification à deux facteurs désactivée - <a href="mfa_setup.php">Activer maintenant</a></p>
<?php endif; ?>

<p>
    <a href="mfa_setup.php">⚙️ Gérer l'authentification à deux facteurs</a>
</p>

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