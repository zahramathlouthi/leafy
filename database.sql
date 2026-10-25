CREATE DATABASE IF NOT EXISTS `uber_cueillette` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `uber_cueillette`;

-- Table: type_fruit
CREATE TABLE `type_fruit` (
  `id_type_fruit` INT AUTO_INCREMENT PRIMARY KEY,
  `libelle` VARCHAR(20) NOT NULL
) ENGINE=InnoDB;

INSERT INTO `type_fruit` (`libelle`) VALUES
('Olives'), ('Agrumes'), ('Tomates'), ('Dattes'), ('Raisins'), ('Pommes'), ('Poires'), ('Figues');

-- Table: gouvernorat
CREATE TABLE `gouvernorat` (
  `id_gouvernorat` INT AUTO_INCREMENT PRIMARY KEY,
  `libelle` VARCHAR(30) NOT NULL
) ENGINE=InnoDB;

INSERT INTO `gouvernorat` (`libelle`) VALUES
('Ariana'), ('Béja'), ('Ben Arous'), ('Bizerte'), ('Gabès'),
('Gafsa'), ('Jendouba'), ('Kairouan'), ('Kasserine'), ('Kébili'),
('Kef'), ('Mahdia'), ('Manouba'), ('Médenine'), ('Monastir'),
('Nabeul'), ('Sfax'), ('Sidi Bouzid'), ('Siliana'), ('Sousse'),
('Tataouine'), ('Tozeur'), ('Tunis'), ('Zaghouan');

-- Table: agriculteur
CREATE TABLE `agriculteur` (
  `id_agriculteur` INT AUTO_INCREMENT PRIMARY KEY,
  `nom` VARCHAR(30) NOT NULL,
  `prenom` VARCHAR(30) NOT NULL,
  `CIN` VARCHAR(10) NOT NULL UNIQUE,
  `email` VARCHAR(30) NOT NULL UNIQUE,
  `adresse` VARCHAR(50),
  `pseudo` VARCHAR(30) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- Table: ouvrier
CREATE TABLE `ouvrier` (
  `id_ouvrier` INT AUTO_INCREMENT PRIMARY KEY,
  `nom` VARCHAR(30) NOT NULL,
  `prenom` VARCHAR(30) NOT NULL,
  `CIN` VARCHAR(30) NOT NULL UNIQUE,
  `email` VARCHAR(30) NOT NULL UNIQUE,
  `photo` LONGBLOB,
  `description` VARCHAR(100),
  `pseudo` VARCHAR(30) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- Table: offre
CREATE TABLE `offre` (
  `id_offre` INT AUTO_INCREMENT PRIMARY KEY,
  `id_type_fruit` INT NOT NULL,
  `id_gouvernorat` INT NOT NULL,
  `adresse` VARCHAR(50),
  `date_debut` DATE NOT NULL,
  `date_fin` DATE NOT NULL,
  `nombre_ouvriers` INT NOT NULL,
  `prix_journee` FLOAT NOT NULL,
  `date_limite` DATE NOT NULL,
  `id_agriculteur` INT NOT NULL,
  FOREIGN KEY (`id_type_fruit`) REFERENCES `type_fruit`(`id_type_fruit`),
  FOREIGN KEY (`id_gouvernorat`) REFERENCES `gouvernorat`(`id_gouvernorat`),
  FOREIGN KEY (`id_agriculteur`) REFERENCES `agriculteur`(`id_agriculteur`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table: candidature
CREATE TABLE `candidature` (
  `id_candidature` INT AUTO_INCREMENT PRIMARY KEY,
  `id_offre` INT NOT NULL,
  `id_ouvrier` INT NOT NULL,
  `decision` VARCHAR(10) DEFAULT 'encours',
  `date_candidature` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `note` INT DEFAULT NULL,
  `commentaire` VARCHAR(50) DEFAULT NULL,
  `remuneration` FLOAT DEFAULT NULL,
  FOREIGN KEY (`id_offre`) REFERENCES `offre`(`id_offre`) ON DELETE CASCADE,
  FOREIGN KEY (`id_ouvrier`) REFERENCES `ouvrier`(`id_ouvrier`) ON DELETE CASCADE,
  UNIQUE KEY `unique_candidature` (`id_offre`, `id_ouvrier`)
) ENGINE=InnoDB;
