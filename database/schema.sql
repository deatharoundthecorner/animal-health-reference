-- Animal Health & Anatomy Reference — database schema
-- Compatible with MySQL 5.7+/8 and MariaDB 10.3+ (XAMPP).
--
-- Import with:  php tools/install.php        (creates the database, schema and sample data)
-- Or manually:  in phpMyAdmin import, in this order:
--               schema.sql, schema-feeding.sql, seed.sql, seed-feeding.sql
--               (the two seed files are optional sample data)
--
-- Design notes
--   * Anatomy (body_parts) and diseases are first-class tables, linked to animals
--     through many-to-many tables so one disease can affect many species and many
--     body parts, and every link can carry a species-specific note.
--   * Ontology identifiers (NCBI Taxonomy, Uberon, Disease Ontology) are stored so the
--     data stays interoperable with other biological databases.
--   * Every animal, disease and body part has a review_status:
--       draft       - visible to admins only
--       unreviewed  - public, shown with an "not yet expert-reviewed" badge
--       reviewed    - public, checked by a qualified reviewer
--   * Every animal, disease and body part can cite one or more rows in `sources`.

CREATE DATABASE IF NOT EXISTS biodiversity_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE biodiversity_system;

SET FOREIGN_KEY_CHECKS = 0;
-- Feeding guide tables (created by schema-feeding.sql)
DROP TABLE IF EXISTS feeding_plan_sources;
DROP TABLE IF EXISTS feed_sources;
DROP TABLE IF EXISTS animal_unsafe_foods;
DROP TABLE IF EXISTS feeding_plan_items;
DROP TABLE IF EXISTS feeding_plan_points;
DROP TABLE IF EXISTS feeding_plans;
DROP TABLE IF EXISTS feeds;
DROP TABLE IF EXISTS body_part_sources;
DROP TABLE IF EXISTS disease_sources;
DROP TABLE IF EXISTS animal_sources;
DROP TABLE IF EXISTS disease_body_parts;
DROP TABLE IF EXISTS animal_diseases;
DROP TABLE IF EXISTS animal_body_parts;
DROP TABLE IF EXISTS disease_symptoms;
DROP TABLE IF EXISTS sources;
DROP TABLE IF EXISTS symptoms;
DROP TABLE IF EXISTS diseases;
DROP TABLE IF EXISTS body_parts;
DROP TABLE IF EXISTS body_systems;
DROP TABLE IF EXISTS animals;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS admins;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------------
-- Administration
-- ---------------------------------------------------------------------------
-- No default account is shipped. Create one with:  php tools/create-admin.php <username>
CREATE TABLE admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Animals
-- ---------------------------------------------------------------------------
CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE animals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    common_name VARCHAR(150) NOT NULL,
    scientific_name VARCHAR(200) NOT NULL,
    taxon_rank ENUM('species','subspecies','genus','subfamily','family') NOT NULL DEFAULT 'species',
    ncbi_taxon_id INT UNSIGNED NULL,
    category_id INT UNSIGNED NOT NULL,
    animal_type ENUM('companion','livestock','wildlife','other') NOT NULL DEFAULT 'other',
    class_name VARCHAR(100) NULL,
    order_name VARCHAR(100) NULL,
    family_name VARCHAR(100) NULL,
    habitat VARCHAR(255) NOT NULL DEFAULT '',
    diet VARCHAR(255) NOT NULL DEFAULT '',
    country VARCHAR(255) NOT NULL DEFAULT '',
    lifespan VARCHAR(100) NOT NULL DEFAULT '',
    conservation_status VARCHAR(100) NOT NULL DEFAULT '',
    description TEXT NOT NULL,
    image VARCHAR(255) NULL,
    review_status ENUM('draft','unreviewed','reviewed') NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_animals_ncbi_taxon (ncbi_taxon_id),
    KEY idx_animals_type (animal_type),
    KEY idx_animals_status (review_status),
    KEY idx_animals_name (common_name),
    CONSTRAINT fk_animals_category
        FOREIGN KEY (category_id) REFERENCES categories(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Anatomy
-- ---------------------------------------------------------------------------
CREATE TABLE body_systems (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    system_name VARCHAR(100) NOT NULL UNIQUE,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE body_parts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    system_id INT UNSIGNED NOT NULL,
    part_name VARCHAR(150) NOT NULL UNIQUE,
    uberon_id VARCHAR(20) NULL,
    description TEXT NOT NULL,
    review_status ENUM('draft','unreviewed','reviewed') NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_body_parts_uberon (uberon_id),
    KEY idx_body_parts_status (review_status),
    CONSTRAINT fk_body_parts_system
        FOREIGN KEY (system_id) REFERENCES body_systems(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Which body parts an animal has, with an optional species-specific note
-- (e.g. "Ruminant: four-compartment stomach").
CREATE TABLE animal_body_parts (
    animal_id INT UNSIGNED NOT NULL,
    body_part_id INT UNSIGNED NOT NULL,
    notes TEXT NULL,
    PRIMARY KEY (animal_id, body_part_id),
    CONSTRAINT fk_abp_animal FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_abp_part FOREIGN KEY (body_part_id) REFERENCES body_parts(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Diseases
-- ---------------------------------------------------------------------------
CREATE TABLE symptoms (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    symptom_name VARCHAR(150) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE diseases (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    disease_name VARCHAR(200) NOT NULL UNIQUE,
    pathogen_type ENUM('virus','bacteria','parasite','fungus','non-infectious','multiple') NOT NULL,
    pathogen_name VARCHAR(255) NULL,
    disease_ontology_id VARCHAR(20) NULL,
    is_zoonotic TINYINT(1) NOT NULL DEFAULT 0,
    woah_listed TINYINT(1) NOT NULL DEFAULT 0,
    summary TEXT NOT NULL,
    transmission TEXT NULL,
    prevention TEXT NULL,
    review_status ENUM('draft','unreviewed','reviewed') NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_diseases_pathogen (pathogen_type),
    KEY idx_diseases_status (review_status)
) ENGINE=InnoDB;

CREATE TABLE disease_symptoms (
    disease_id INT UNSIGNED NOT NULL,
    symptom_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (disease_id, symptom_id),
    CONSTRAINT fk_ds_disease FOREIGN KEY (disease_id) REFERENCES diseases(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_ds_symptom FOREIGN KEY (symptom_id) REFERENCES symptoms(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Which species a disease affects, with an optional species-specific note.
CREATE TABLE animal_diseases (
    animal_id INT UNSIGNED NOT NULL,
    disease_id INT UNSIGNED NOT NULL,
    notes TEXT NULL,
    PRIMARY KEY (animal_id, disease_id),
    CONSTRAINT fk_ad_animal FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_ad_disease FOREIGN KEY (disease_id) REFERENCES diseases(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Which body parts a disease affects, with an optional note on how.
CREATE TABLE disease_body_parts (
    disease_id INT UNSIGNED NOT NULL,
    body_part_id INT UNSIGNED NOT NULL,
    notes TEXT NULL,
    PRIMARY KEY (disease_id, body_part_id),
    CONSTRAINT fk_dbp_disease FOREIGN KEY (disease_id) REFERENCES diseases(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_dbp_part FOREIGN KEY (body_part_id) REFERENCES body_parts(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Sources / citations
-- ---------------------------------------------------------------------------
CREATE TABLE sources (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL UNIQUE,
    publisher VARCHAR(255) NULL,
    url VARCHAR(500) NULL,
    license VARCHAR(150) NULL,
    source_type ENUM('database','ontology','guideline','article','manual','other') NOT NULL DEFAULT 'other',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE animal_sources (
    animal_id INT UNSIGNED NOT NULL,
    source_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (animal_id, source_id),
    CONSTRAINT fk_as_animal FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_as_source FOREIGN KEY (source_id) REFERENCES sources(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE disease_sources (
    disease_id INT UNSIGNED NOT NULL,
    source_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (disease_id, source_id),
    CONSTRAINT fk_dsrc_disease FOREIGN KEY (disease_id) REFERENCES diseases(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_dsrc_source FOREIGN KEY (source_id) REFERENCES sources(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE body_part_sources (
    body_part_id INT UNSIGNED NOT NULL,
    source_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (body_part_id, source_id),
    CONSTRAINT fk_bps_part FOREIGN KEY (body_part_id) REFERENCES body_parts(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_bps_source FOREIGN KEY (source_id) REFERENCES sources(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;
