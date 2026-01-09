-- Base de données du projet
CREATE DATABASE IF NOT EXISTS gi2_securite;
USE gi2_securite;

-- Table users (Admin en ligne 1, SHA1, pas d'état)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50),
    password VARCHAR(50), 
    role VARCHAR(10)
);

-- Table Projets (Entités à afficher)
CREATE TABLE projets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titre VARCHAR(100),
    description TEXT,
    budget DECIMAL(10,2),
    user_id INT,
    FOREIGN KEY (user_id) REFERENCES users(id)
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