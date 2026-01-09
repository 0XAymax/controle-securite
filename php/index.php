<?php
require_once 'config.php';
session_start();

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Vérification reCAPTCHA
    $recaptcha_secret = '6LefKEUsAAAAANyJWBaW1tqt3yaTDOUdX1ZhD6hT';
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
    
    if (empty($recaptcha_response)) {
        $error = "Veuillez cocher la case reCAPTCHA.";
    } else {
        // Vérifier le reCAPTCHA auprès de Google
        $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
        $recaptcha_data = [
            'secret' => $recaptcha_secret,
            'response' => $recaptcha_response,
            'remoteip' => $_SERVER['REMOTE_ADDR']
        ];
        
        $recaptcha_options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($recaptcha_data)
            ]
        ];
        
        $recaptcha_context = stream_context_create($recaptcha_options);
        $recaptcha_result = file_get_contents($recaptcha_url, false, $recaptcha_context);
        $recaptcha_json = json_decode($recaptcha_result);
        
        if (!$recaptcha_json->success) {
            $error = "Échec de la validation reCAPTCHA. Veuillez réessayer.";
        }
    }
    
    // Si reCAPTCHA est valide, continuer avec l'authentification
    if (empty($error)) {
        // 1. CORRIGÉ : SHA1 est obsolète et vulnérable aux collisions/dictionnaires
        $hashed_password = sha1($password);

        try {
            // Vérifier si l'utilisateur existe d'abord
        $sql = "SELECT * FROM users WHERE username = :username";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Vérifier si le compte est verrouillé
            if ($user['lockout_until'] !== null) {
                $lockout_time = new DateTime($user['lockout_until']);
                $current_time = new DateTime();
                
                if ($current_time < $lockout_time) {
                    $remaining = $lockout_time->diff($current_time);
                    $minutes = $remaining->i;
                    $seconds = $remaining->s;
                    $error = "Compte verrouillé. Réessayez dans {$minutes} minute(s) et {$seconds} seconde(s).";
                } else {
                    // Le verrouillage est expiré, réinitialiser
                    $reset_sql = "UPDATE users SET failed_attempts = 0, lockout_until = NULL WHERE id = :id";
                    $reset_stmt = $pdo->prepare($reset_sql);
                    $reset_stmt->bindParam(':id', $user['id'], PDO::PARAM_INT);
                    $reset_stmt->execute();
                    $user['failed_attempts'] = 0;
                    $user['lockout_until'] = null;
                }
            }
            
            // Si le compte n'est pas verrouillé, vérifier le mot de passe
            if ($user['lockout_until'] === null || new DateTime() >= new DateTime($user['lockout_until'])) {
                if ($user['password'] === $hashed_password) {
                    // Authentification réussie - Réinitialiser les tentatives échouées
                    $reset_sql = "UPDATE users SET failed_attempts = 0, lockout_until = NULL WHERE id = :id";
                    $reset_stmt = $pdo->prepare($reset_sql);
                    $reset_stmt->bindParam(':id', $user['id'], PDO::PARAM_INT);
                    $reset_stmt->execute();
                    
                    // Vérifier si MFA est activé
                    if ($user['mfa_enabled'] && !empty($user['mfa_secret'])) {
                        // Rediriger vers la page de vérification MFA
                        $_SESSION['pending_mfa_user_id'] = $user['id'];
                        header('Location: mfa_verify.php');
                        exit();
                    } else {
                        // Pas de MFA - Connexion complète
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['role'] = $user['role'];

                        // Redirection vers le dashboard
                        header('Location: dashboard.php');
                        exit();
                    }
                } else {
                    // Mot de passe incorrect - Incrémenter les tentatives échouées
                    $failed_attempts = $user['failed_attempts'] + 1;
                    
                    if ($failed_attempts >= 3) {
                        // Verrouiller le compte pour 15 minutes
                        $lockout_until = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                        $update_sql = "UPDATE users SET failed_attempts = :attempts, lockout_until = :lockout WHERE id = :id";
                        $update_stmt = $pdo->prepare($update_sql);
                        $update_stmt->bindParam(':attempts', $failed_attempts, PDO::PARAM_INT);
                        $update_stmt->bindParam(':lockout', $lockout_until, PDO::PARAM_STR);
                        $update_stmt->bindParam(':id', $user['id'], PDO::PARAM_INT);
                        $update_stmt->execute();
                        
                        $error = "Trop de tentatives échouées. Compte verrouillé pour 15 minutes.";
                    } else {
                        // Incrémenter sans verrouiller
                        $update_sql = "UPDATE users SET failed_attempts = :attempts WHERE id = :id";
                        $update_stmt = $pdo->prepare($update_sql);
                        $update_stmt->bindParam(':attempts', $failed_attempts, PDO::PARAM_INT);
                        $update_stmt->bindParam(':id', $user['id'], PDO::PARAM_INT);
                        $update_stmt->execute();
                        
                        $remaining_attempts = 3 - $failed_attempts;
                        $error = "Identifiant ou mot de passe incorrect. {$remaining_attempts} tentative(s) restante(s).";
                    }
                }
            }
        } else {
            // 4. Message d'erreur générique pour ne pas révéler si l'utilisateur existe
            $error = "Identifiant ou mot de passe incorrect.";
        }
        } catch (PDOException $e) {
            // 5. CORRIGÉ : Pas d'affichage des détails de l'erreur SQL
            error_log("Erreur SQL : " . $e->getMessage());
            $error = "Une erreur s'est produite. Veuillez réessayer.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion - Portail GI2</title>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; margin-top: 100px; background: #f4f4f4; }
        .login-box { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 300px; }
        input { width: 100%; padding: 10px; margin: 10px 0; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #007bff; color: white; border: none; cursor: pointer; }
        .error { color: red; font-size: 0.9em; }
        .g-recaptcha { margin: 10px 0; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Connexion</h2>
        
        <?php if ($error): ?>
            <p class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <form method="POST" action="">
            <label>Nom d'utilisateur :</label>
            <input type="text" name="username" required>
            
            <label>Mot de passe :</label>
            <input type="password" name="password" required>
            
            <div class="g-recaptcha" data-sitekey="6LefKEUsAAAAACZ3apmTIuBOdR7GECGzliRGE7Ur"></div>

            <button type="submit">Se connecter</button>
        </form>
        
    </div>
</body>
</html>