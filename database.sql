-- ============================================
-- PLATEFORME DE LIVRAISON LOCALE - BASE DE DONNÉES
-- Importez ce fichier dans phpMyAdmin
-- ============================================

CREATE DATABASE IF NOT EXISTS livraison_locale CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE livraison_locale;

CREATE TABLE utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('client','gerant','livreur','admin') NOT NULL DEFAULT 'client',
    telephone VARCHAR(20),
    vehicule VARCHAR(100),
    note DECIMAL(3,1) DEFAULT 5.0,
    gains DECIMAL(10,2) DEFAULT 0,
    missions INT DEFAULT 0,
    statut_livreur ENUM('Disponible','En livraison','Hors ligne') DEFAULT 'Disponible',
    restaurant_id INT DEFAULT NULL,
    created_at DATETIME DEFAULT NOW()
);

CREATE TABLE restaurants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    cuisine VARCHAR(50),
    note DECIMAL(3,1) DEFAULT 5.0,
    zone VARCHAR(50),
    description TEXT,
    emoji VARCHAR(10) DEFAULT '🍽️'
);

CREATE TABLE plats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    restaurant_id INT NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prix DECIMAL(10,2) NOT NULL,
    description TEXT,
    disponible TINYINT(1) DEFAULT 1,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
);

CREATE TABLE zones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    tarif_livraison INT NOT NULL
);

CREATE TABLE commandes (
    id VARCHAR(20) PRIMARY KEY,
    client_id INT NOT NULL,
    restaurant_id INT NOT NULL,
    restaurant_nom VARCHAR(100),
    adresse VARCHAR(200) NOT NULL,
    zone VARCHAR(50),
    sous_total DECIMAL(10,2) NOT NULL,
    frais_livraison DECIMAL(10,2) DEFAULT 500,
    total DECIMAL(10,2) NOT NULL,
    statut ENUM('En attente','Validée','En préparation','Assignée','En cours de livraison','Livrée','Annulée','Incident signalé') DEFAULT 'En attente',
    livreur_id INT DEFAULT NULL,
    heure DATETIME DEFAULT NOW(),
    FOREIGN KEY (client_id) REFERENCES utilisateurs(id),
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id)
);

CREATE TABLE commande_plats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commande_id VARCHAR(20) NOT NULL,
    plat_id INT NOT NULL,
    plat_nom VARCHAR(100),
    qte INT DEFAULT 1,
    prix_unitaire DECIMAL(10,2),
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE
);

CREATE TABLE evaluations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commande_id VARCHAR(20) NOT NULL,
    client_id INT NOT NULL,
    livreur_id INT NOT NULL,
    note INT CHECK (note BETWEEN 1 AND 5),
    commentaire TEXT,
    created_at DATETIME DEFAULT NOW()
);

INSERT INTO zones (nom, tarif_livraison) VALUES
('Centre', 500), ('Nord', 750), ('Sud', 750), ('Est', 1000), ('Périphérie', 1500);

INSERT INTO restaurants (id, nom, cuisine, note, zone, description, emoji) VALUES
(1, 'Le Petit Dakar', 'Sénégalaise', 4.8, 'Centre', 'Spécialités locales authentiques', '🍲'),
(2, 'Pizza Royale',   'Italienne',   4.5, 'Nord',   'Pizzas artisanales au feu de bois',        '🍕'),
(3, 'Wok Express',    'Asiatique',   4.3, 'Sud',    'Cuisine asiatique rapide et savoureuse',    '🥡');

INSERT INTO plats (restaurant_id, nom, prix, description, disponible) VALUES
(1, 'Thiéboudienne',  3500, 'Riz au poisson traditionnel',              1),
(1, 'Yassa Poulet',   2800, 'Poulet à la sauce yassa',                  1),
(1, 'Mafé',           2600, 'Ragoût à la pâte d''arachide',             1),
(1, 'Bissap',          500, 'Jus d''hibiscus frais',                    1),
(2, 'Margherita',     4200, 'Tomate, mozzarella, basilic',              1),
(2, 'Quatre Fromages',5500, 'Gorgonzola, parmesan, emmental, chèvre',  1),
(2, 'Calzone',        4800, 'Pizza chausson jambon',                    0),
(3, 'Nouilles Sautées',3200,'Nouilles aux légumes et crevettes',        1),
(3, 'Riz Cantonnais', 2900, 'Riz sauté à l''oeuf',                     1),
(3, 'Nems (6 pcs)',   2200, 'Rouleaux frits au porc',                   1);

-- Mot de passe = "password" pour tous
INSERT INTO utilisateurs (id, nom, email, mot_de_passe, role, telephone, restaurant_id) VALUES
(1,'Fatou Diallo',   'fatou@email.sn',           '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','client', '77 111 00 00',NULL),
(2,'Moussa Ndiaye',  'moussa@email.sn',          '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','client', '77 222 00 00',NULL),
(3,'Mamadou Diallo', 'gerant@petitdakar.sn',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','gerant', '77 111 22 33',1),
(4,'Alioune Sarr',   'gerant@pizzaroyale.sn',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','gerant', '77 222 33 44',2),
(5,'Fatou Ndiaye',   'gerant@wokexpress.sn',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','gerant', '77 333 44 55',3),
(6,'Ibrahim Seck',   'ibrahim.seck@livreur.sn',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','livreur','77 123 45 67',NULL),
(7,'Cheikh Fall',    'cheikh.fall@livreur.sn',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','livreur','77 234 56 78',NULL),
(8,'Abdou Diop',     'abdou.diop@livreur.sn',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','livreur','77 345 67 89',NULL),
(9,'Admin Système',  'admin@livraison.sn',       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin',  NULL,          NULL);

UPDATE utilisateurs SET vehicule='Moto - Honda',    note=4.7, missions=4, gains=6000 WHERE id=6;
UPDATE utilisateurs SET vehicule='Moto - Yamaha',   note=4.9, missions=6, gains=9000, statut_livreur='En livraison' WHERE id=7;
UPDATE utilisateurs SET vehicule='Vélo électrique', note=4.5, missions=3, gains=4500 WHERE id=8;

INSERT INTO commandes (id,client_id,restaurant_id,restaurant_nom,adresse,zone,sous_total,frais_livraison,total,statut,livreur_id,heure) VALUES
('CMD-001',1,1,'Le Petit Dakar','12 Rue des Baobabs',   'Centre',4000,500,4500,'En attente',           NULL,NOW()-INTERVAL 30 MINUTE),
('CMD-002',2,2,'Pizza Royale',  '45 Avenue Léopold',    'Nord',  9000,750,9750,'En préparation',       NULL,NOW()-INTERVAL 20 MINUTE),
('CMD-003',1,3,'Wok Express',   '8 Cité Keur Gorgui',   'Sud',   5400,750,6150,'En cours de livraison',7,   NOW()-INTERVAL 45 MINUTE),
('CMD-004',2,1,'Le Petit Dakar','22 Rue Carnot',        'Centre',2800,500,3300,'Livrée',               8,   NOW()-INTERVAL 2 HOUR),
('CMD-005',1,2,'Pizza Royale',  '3 Allée des Manguiers','Nord',  5500,750,6250,'Validée',              NULL,NOW()-INTERVAL 10 MINUTE);

INSERT INTO commande_plats (commande_id,plat_id,plat_nom,qte,prix_unitaire) VALUES
('CMD-001',1,'Thiéboudienne',   1,3500),
('CMD-001',4,'Bissap',          1,500),
('CMD-002',5,'Margherita',      1,4200),
('CMD-002',7,'Calzone',         1,4800),
('CMD-003',8,'Nouilles Sautées',1,3200),
('CMD-003',10,'Nems (6 pcs)',   1,2200),
('CMD-004',2,'Yassa Poulet',    1,2800),
('CMD-005',6,'Quatre Fromages', 1,5500);
