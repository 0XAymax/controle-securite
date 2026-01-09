<?php
include 'config.php';
session_start();

// CORRIGÉ : Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// CORRIGÉ : Validation et utilisation de requêtes préparées pour prévenir l'injection SQL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID de projet invalide.");
}

$id = (int)$_GET['id'];

// CORRIGÉ : Utilisation de requêtes préparées
$query = "SELECT * FROM projets WHERE id = :id";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$projet = $stmt->fetch();

if (!$projet) {
    die("Projet introuvable.");
}
?>
<h3>Détails du projet : <?php echo htmlspecialchars($projet['titre'], ENT_QUOTES, 'UTF-8'); ?></h3>
<p><?php echo htmlspecialchars($projet['description'], ENT_QUOTES, 'UTF-8'); ?></p>
<p>Budget : <?php echo htmlspecialchars($projet['budget'], ENT_QUOTES, 'UTF-8'); ?> €</p>
<a href="dashboard.php">Retour</a>