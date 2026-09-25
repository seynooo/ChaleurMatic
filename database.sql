-- Créer la table des demandes RDV
CREATE TABLE IF NOT EXISTS demandes_rdv (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    date_envoi      DATETIME DEFAULT CURRENT_TIMESTAMP,
    service         VARCHAR(100),
    prenom          VARCHAR(100),
    nom             VARCHAR(100),
    telephone       VARCHAR(30),
    email           VARCHAR(150),
    adresse         VARCHAR(255),
    type_chaudiere  VARCHAR(50),
    marque          VARCHAR(50),
    modele          VARCHAR(100),
    annee           VARCHAR(10),
    date_souhaitee  VARCHAR(20),
    creneau         VARCHAR(50),
    message         TEXT,
    statut          ENUM('en_attente','confirme','termine') DEFAULT 'en_attente',
    date_confirmee  DATETIME NULL
);

-- Si la table existe déjà, ajouter les nouvelles colonnes
ALTER TABLE demandes_rdv ADD COLUMN IF NOT EXISTS statut ENUM('en_attente','confirme','termine') DEFAULT 'en_attente';
ALTER TABLE demandes_rdv ADD COLUMN IF NOT EXISTS date_confirmee DATETIME NULL;