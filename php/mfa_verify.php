<?php
require_once 'config.php';
require_once __DIR__ . '/vendor/autoload.php';

use Sonata\GoogleAuthenticator\GoogleAuthenticator;

session_start();

// Vérifier si l'utilisateur doit vérifier le MFA
if (!isset($_SESSION['pending_mfa_user_id'])) {
    header('Location: index.php');
    exit();
}

$error = "";
$g = new GoogleAuthenticator();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['mfa_code'] ?? '';
    $user_id = $_SESSION['pending_mfa_user_id'];
    
    // Récupérer le secret MFA de l'utilisateur
    $sql = "SELECT * FROM users WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && $user['mfa_enabled'] && $user['mfa_secret']) {
        // Vérifier le code MFA
        if ($g->checkCode($user['mfa_secret'], $code)) {
            // Code correct - Authentification complète
            unset($_SESSION['pending_mfa_user_id']);
            
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Réinitialiser les tentatives échouées
            $reset_sql = "UPDATE users SET failed_attempts = 0, lockout_until = NULL WHERE id = :id";
            $reset_stmt = $pdo->prepare($reset_sql);
            $reset_stmt->bindParam(':id', $user['id'], PDO::PARAM_INT);
            $reset_stmt->execute();
            
            header('Location: dashboard.php');
            exit();
        } else {
            $error = "Code MFA incorrect. Veuillez réessayer.";
        }
    } else {
        $error = "Une erreur s'est produite. Veuillez vous reconnecter.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Vérification MFA - Portail GI2</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; margin-top: 100px; background: #f4f4f4; }
        .mfa-box { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 350px; }
        h2 { color: #333; text-align: center; }
        .icon { text-align: center; font-size: 48px; margin: 20px 0; }
        input[type="text"] { width: 100%; padding: 15px; margin: 15px 0; box-sizing: border-box; font-size: 24px; text-align: center; letter-spacing: 10px; }
        button { width: 100%; padding: 12px; background: #007bff; color: white; border: none; cursor: pointer; font-size: 16px; border-radius: 5px; }
        button:hover { background: #0056b3; }
        .error { color: #dc3545; font-size: 0.9em; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; text-align: center; }
        .instructions { color: #666; font-size: 0.9em; text-align: center; margin: 15px 0; }
        .cancel-link { display: block; text-align: center; margin-top: 15px; color: #6c757d; text-decoration: none; }
        .cancel-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="mfa-box">
        <div class="icon">🔐</div>
        <h2>Vérification en deux étapes</h2>
        
        <p class="instructions">Entrez le code à 6 chiffres généré par votre application d'authentification</p>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="text" name="mfa_code" placeholder="000000" maxlength="6" pattern="[0-9]{6}" required autofocus autocomplete="off">
            <button type="submit">Vérifier</button>
        </form>
        
        <a href="logout.php" class="cancel-link">Annuler et se déconnecter</a>
    </div>
</body>
</html>
