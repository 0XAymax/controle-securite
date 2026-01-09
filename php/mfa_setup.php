<?php
require_once 'config.php';
require_once __DIR__ . '/vendor/autoload.php';

use Sonata\GoogleAuthenticator\GoogleAuthenticator;
use Sonata\GoogleAuthenticator\GoogleQrUrl;

session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$g = new GoogleAuthenticator();
$message = "";
$error = "";

// Récupérer les informations de l'utilisateur
$sql = "SELECT * FROM users WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':id', $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Traiter l'activation de MFA
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['enable_mfa'])) {
        // Générer un nouveau secret
        $secret = $g->generateSecret();
        
        // Stocker temporairement dans la session
        $_SESSION['temp_mfa_secret'] = $secret;
        
    } elseif (isset($_POST['verify_code'])) {
        // Vérifier le code pour activer MFA
        $code = $_POST['verification_code'];
        $secret = $_SESSION['temp_mfa_secret'] ?? '';
        
        if ($g->checkCode($secret, $code)) {
            // Code correct, activer MFA
            $update_sql = "UPDATE users SET mfa_secret = :secret, mfa_enabled = 1 WHERE id = :id";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->bindParam(':secret', $secret, PDO::PARAM_STR);
            $update_stmt->bindParam(':id', $_SESSION['user_id'], PDO::PARAM_INT);
            $update_stmt->execute();
            
            unset($_SESSION['temp_mfa_secret']);
            $message = "Authentification à deux facteurs activée avec succès !";
            
            // Recharger les données utilisateur
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } else {
            $error = "Code de vérification incorrect. Veuillez réessayer.";
        }
    } elseif (isset($_POST['disable_mfa'])) {
        // Désactiver MFA
        $update_sql = "UPDATE users SET mfa_secret = NULL, mfa_enabled = 0 WHERE id = :id";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->bindParam(':id', $_SESSION['user_id'], PDO::PARAM_INT);
        $update_stmt->execute();
        
        $message = "Authentification à deux facteurs désactivée.";
        
        // Recharger les données utilisateur
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Générer le QR code si un secret temporaire existe
$qrCodeUrl = '';
if (isset($_SESSION['temp_mfa_secret'])) {
    $secret = $_SESSION['temp_mfa_secret'];
    $qrCodeUrl = GoogleQrUrl::generate($user['username'], $secret, 'Portail GI2');
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Configuration MFA - Portail GI2</title>
    <style>
        body { font-family: sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { color: #333; }
        .status { padding: 15px; margin: 15px 0; border-radius: 5px; }
        .status.enabled { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status.disabled { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .message { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .qr-code { text-align: center; margin: 20px 0; }
        .qr-code img { border: 2px solid #ddd; padding: 10px; }
        input[type="text"] { width: 100%; padding: 10px; margin: 10px 0; box-sizing: border-box; font-size: 18px; text-align: center; letter-spacing: 5px; }
        button { padding: 10px 20px; margin: 5px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; }
        .btn-primary { background: #007bff; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .instructions { background: #e7f3ff; padding: 15px; border-left: 4px solid #007bff; margin: 15px 0; }
        .back-link { display: inline-block; margin-top: 20px; color: #007bff; text-decoration: none; }
        .secret-code { font-family: monospace; font-size: 16px; background: #f8f9fa; padding: 10px; border-radius: 5px; word-break: break-all; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Configuration de l'Authentification à Deux Facteurs (2FA)</h2>
        
        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        
        <?php if ($user['mfa_enabled']): ?>
            <div class="status enabled">
                <strong>✓ Statut :</strong> Authentification à deux facteurs activée
            </div>
            
            <p>Votre compte est protégé par l'authentification à deux facteurs. Un code à 6 chiffres sera requis à chaque connexion.</p>
            
            <form method="POST">
                <button type="submit" name="disable_mfa" class="btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir désactiver l\'authentification à deux facteurs ?')">Désactiver MFA</button>
            </form>
            
        <?php elseif (isset($_SESSION['temp_mfa_secret'])): ?>
            <div class="status disabled">
                <strong>!</strong> Configuration en cours...
            </div>
            
            <div class="instructions">
                <h3>Instructions :</h3>
                <ol>
                    <li>Téléchargez une application d'authentification (Google Authenticator, Microsoft Authenticator, Authy, etc.)</li>
                    <li>Scannez le QR code ci-dessous avec votre application</li>
                    <li>Entrez le code à 6 chiffres généré par l'application pour vérifier</li>
                </ol>
            </div>
            
            <div class="qr-code">
                <img src="<?php echo htmlspecialchars($qrCodeUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="QR Code">
                <p><strong>Clé secrète :</strong></p>
                <div class="secret-code"><?php echo htmlspecialchars($_SESSION['temp_mfa_secret'], ENT_QUOTES, 'UTF-8'); ?></div>
                <p><em>Vous pouvez également saisir cette clé manuellement dans votre application</em></p>
            </div>
            
            <form method="POST">
                <label for="verification_code"><strong>Code de vérification :</strong></label>
                <input type="text" name="verification_code" id="verification_code" placeholder="000000" maxlength="6" pattern="[0-9]{6}" required autofocus>
                <button type="submit" name="verify_code" class="btn-primary">Vérifier et activer</button>
                <a href="mfa_setup.php" class="btn-secondary" style="text-decoration: none; display: inline-block;">Annuler</a>
            </form>
            
        <?php else: ?>
            <div class="status disabled">
                <strong>!</strong> Authentification à deux facteurs désactivée
            </div>
            
            <p>L'authentification à deux facteurs ajoute une couche de sécurité supplémentaire à votre compte. Une fois activée, vous devrez fournir un code à 6 chiffres généré par votre application d'authentification en plus de votre mot de passe.</p>
            
            <form method="POST">
                <button type="submit" name="enable_mfa" class="btn-primary">Activer MFA</button>
            </form>
        <?php endif; ?>
        
        <a href="dashboard.php" class="back-link">← Retour au tableau de bord</a>
    </div>
</body>
</html>
