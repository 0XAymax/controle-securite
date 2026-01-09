<?php
// config.php
$host = 'db'; 
$user = 'root'; // ERREUR : Usage de root
$pass = '';     // ERREUR : Pas de mot de passe
$dbname = 'gi2_securite';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    // CORRIGÉ : Mode d'erreur configuré pour ne pas afficher les détails en production
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // CORRIGÉ : Utilisation d'UTF-8 pour prévenir certaines attaques d'encodage
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    // CORRIGÉ : Pas de divulgation d'informations sensibles
    error_log("Erreur de connexion à la DB : " . $e->getMessage());
    die("Erreur de connexion à la base de données. Veuillez contacter l'administrateur.");
}
?>