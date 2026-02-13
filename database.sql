-- Création de la base de données
CREATE DATABASE IF NOT EXISTS portail_sites CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE portail_sites;

-- Table des sites et projets
CREATE TABLE IF NOT EXISTS sites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('site', 'project') NOT NULL DEFAULT 'site',
    name VARCHAR(255) NOT NULL,
    url VARCHAR(500) NOT NULL,
    description TEXT NOT NULL,
    image_path VARCHAR(500) NULL,
    cms VARCHAR(100) NOT NULL,
    version VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertion d'un site exemple
INSERT INTO sites (type, name, url, description, image_path, cms, version) VALUES
('site', 'Dynveo', 'https://v2.dynveo.fr', 'Laboratoire français expert en nutraceutique au service de votre santé grâce à des produits purs, efficaces et innovants.', NULL, 'PrestaShop', '1.7.8.10');
