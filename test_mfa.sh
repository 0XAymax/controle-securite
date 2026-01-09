#!/bin/bash

echo "====================================="
echo "Test de l'implémentation MFA (2FA)"
echo "====================================="
echo ""

echo "1. Vérification de la structure de la base de données..."
docker-compose exec db mysql -uroot gi2_securite -e "DESCRIBE users;" | grep -E "mfa_secret|mfa_enabled"

echo ""
echo "2. Vérification de l'installation de la bibliothèque..."
docker-compose exec web php -r "require_once '/var/www/html/vendor/autoload.php'; use Sonata\GoogleAuthenticator\GoogleAuthenticator; echo 'GoogleAuthenticator library loaded successfully!' . PHP_EOL;"

echo ""
echo "3. Test de génération de secret..."
docker-compose exec web php -r "require_once '/var/www/html/vendor/autoload.php'; use Sonata\GoogleAuthenticator\GoogleAuthenticator; \$g = new GoogleAuthenticator(); \$secret = \$g->generateSecret(); echo 'Secret généré: ' . \$secret . PHP_EOL;"

echo ""
echo "4. Test de vérification de code..."
docker-compose exec web php -r "
require_once '/var/www/html/vendor/autoload.php';
use Sonata\GoogleAuthenticator\GoogleAuthenticator;
\$g = new GoogleAuthenticator();
\$secret = \$g->generateSecret();
\$code = \$g->getCode(\$secret);
\$isValid = \$g->checkCode(\$secret, \$code);
echo 'Code généré: ' . \$code . PHP_EOL;
echo 'Validation: ' . (\$isValid ? 'SUCCÈS' : 'ÉCHEC') . PHP_EOL;
"

echo ""
echo "5. Vérification des fichiers PHP..."
for file in index.php mfa_setup.php mfa_verify.php dashboard.php; do
    result=$(docker-compose exec web php -l /var/www/html/$file 2>&1)
    if [[ $result == *"No syntax errors"* ]]; then
        echo "✓ $file - OK"
    else
        echo "✗ $file - ERREUR"
        echo "$result"
    fi
done

echo ""
echo "6. Statut MFA des utilisateurs..."
docker-compose exec db mysql -uroot gi2_securite -e "SELECT username, mfa_enabled, CASE WHEN mfa_secret IS NOT NULL THEN 'Configuré' ELSE 'Non configuré' END as statut_mfa FROM users;"

echo ""
echo "====================================="
echo "Test terminé!"
echo "====================================="
echo ""
echo "Pour tester manuellement:"
echo "1. Connectez-vous à http://localhost:8080"
echo "2. Utilisez: admin / admin"
echo "3. Allez sur 'Gérer l'authentification à deux facteurs'"
echo "4. Activez MFA et scannez le QR code avec Google Authenticator"
echo "5. Déconnectez-vous et reconnectez-vous pour tester la vérification MFA"
