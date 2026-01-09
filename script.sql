-- Base de données du projet
CREATE DATABASE IF NOT EXISTS gi2_securite;
USE gi2_securite;

-- Table users (Admin en ligne 1, SHA1, pas d'état)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL, 
    role VARCHAR(10) NOT NULL,
    failed_attempts INT DEFAULT 0,
    lockout_until DATETIME NULL,
    mfa_secret VARCHAR(255) NULL,
    mfa_enabled TINYINT(1) DEFAULT 0,
    UNIQUE KEY unique_username (username)
);

-- Table Projets (Entités à afficher)
CREATE TABLE projets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titre VARCHAR(100) NOT NULL,
    description TEXT,
    budget DECIMAL(10,2) NOT NULL,
    user_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Base bidon
CREATE DATABASE IF NOT EXISTS archive_old_data;
USE archive_old_data;
CREATE TABLE secrets_obsoletes (id INT, secret_key TEXT);

-- Insertion des données
USE gi2_securite;
INSERT INTO users (username, password, role) VALUES 
('admin', 'd033e22ae348aeb5660fc2140aec35850c4da997', 'admin'),
('etudiant', 'f35f2998399e526a1f816c52a09c2a6d7f023f03', 'user');

INSERT INTO projets (titre, description, budget, user_id) VALUES 
('Système de Paie', 'Projet confidentiel RH', 50000.00, 1), -- Projet de l'admin
('Application Mobile', 'App de test étudiant', 500.00, 2);    -- Projet de l'user