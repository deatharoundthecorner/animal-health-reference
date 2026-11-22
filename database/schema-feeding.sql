-- Feeding guide tables (feed types, feeding plans, foods to avoid).
--
-- This file only uses CREATE TABLE IF NOT EXISTS, so it is safe to run on an
-- existing installation:  php tools/upgrade.php
-- A fresh install loads it automatically (php tools/install.php).
--
-- How amounts are calculated (see includes/feeding.php):
--   method 'energy'    amount = factor x RER, where RER = 70 x body weight(kg)^0.75 kcal/day.
--                      Used for dogs and cats. Grams of food = kcal / energy density of the food.
--   method 'pct_dm'    amount = % of body weight as DRY MATTER per day (ruminants, horses).
--                      Grams as fed = dry matter / dry-matter % of the feed.
--   method 'pct_asfed' amount = % of body weight as fed per day (e.g. rabbit pellets).
--   method 'fixed'     amount = grams per animal per day (e.g. a laying hen).
--   method 'table'     amount = grams per day read from feeding_plan_points and interpolated by
--                      body weight (e.g. growing pigs, layer pullets).

CREATE TABLE IF NOT EXISTS feeds (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    feed_name VARCHAR(150) NOT NULL UNIQUE,
    feed_category ENUM('green_forage','dry_forage','concentrate','complete_feed','kitchen_food','supplement') NOT NULL,
    scientific_name VARCHAR(150) NULL,
    local_name_si VARCHAR(150) NULL,
    local_name_ta VARCHAR(150) NULL,
    dry_matter_percent DECIMAL(5,2) NULL,
    crude_protein_percent DECIMAL(5,2) NULL,
    me_mj_per_kg_dm DECIMAL(5,2) NULL,
    energy_kcal_per_kg INT UNSIGNED NULL,
    description TEXT NOT NULL,
    availability_note TEXT NULL,
    cautions TEXT NULL,
    review_status ENUM('draft','unreviewed','reviewed') NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_feeds_category (feed_category),
    KEY idx_feeds_status (review_status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS feeding_plans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    animal_id INT UNSIGNED NOT NULL,
    life_stage VARCHAR(120) NOT NULL,
    method ENUM('energy','pct_dm','pct_asfed','fixed','table') NOT NULL,
    amount_min DECIMAL(8,2) NULL,
    amount_max DECIMAL(8,2) NULL,
    amount_typical DECIMAL(8,2) NULL,
    min_weight_kg DECIMAL(8,3) NULL,
    max_weight_kg DECIMAL(8,3) NULL,
    meals_per_day TINYINT UNSIGNED NOT NULL DEFAULT 2,
    target_cp_min DECIMAL(5,2) NULL,
    notes TEXT NULL,
    sort_order SMALLINT NOT NULL DEFAULT 0,
    review_status ENUM('draft','unreviewed','reviewed') NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_plans_animal (animal_id, sort_order),
    KEY idx_plans_status (review_status),
    CONSTRAINT fk_plans_animal FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Weight/amount pairs for plans with method 'table' (amount in grams per day, as fed).
CREATE TABLE IF NOT EXISTS feeding_plan_points (
    plan_id INT UNSIGNED NOT NULL,
    weight_kg DECIMAL(8,3) NOT NULL,
    amount_g DECIMAL(9,2) NOT NULL,
    PRIMARY KEY (plan_id, weight_kg),
    CONSTRAINT fk_points_plan FOREIGN KEY (plan_id) REFERENCES feeding_plans(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- The feeds that make up each diet option of a plan.
--   share_percent  share of the plan's daily energy / dry matter / grams (shares of one diet add up to 100)
--   NULL share and NULL fixed amounts = "free choice" (as much as the animal wants, e.g. hay)
--   fixed_min_g / fixed_max_g = an absolute amount per animal per day, not scaled by weight (e.g. a mineral mix)
CREATE TABLE IF NOT EXISTS feeding_plan_items (
    plan_id INT UNSIGNED NOT NULL,
    diet_name VARCHAR(100) NOT NULL,
    feed_id INT UNSIGNED NOT NULL,
    share_percent DECIMAL(5,2) NULL,
    fixed_min_g DECIMAL(8,2) NULL,
    fixed_max_g DECIMAL(8,2) NULL,
    notes VARCHAR(500) NULL,
    sort_order SMALLINT NOT NULL DEFAULT 0,
    PRIMARY KEY (plan_id, diet_name, feed_id),
    KEY idx_items_feed (feed_id),
    CONSTRAINT fk_items_plan FOREIGN KEY (plan_id) REFERENCES feeding_plans(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_items_feed FOREIGN KEY (feed_id) REFERENCES feeds(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Foods that are dangerous or should be limited for a species.
CREATE TABLE IF NOT EXISTS animal_unsafe_foods (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    animal_id INT UNSIGNED NOT NULL,
    food_name VARCHAR(150) NOT NULL,
    severity ENUM('toxic','avoid','limit') NOT NULL DEFAULT 'avoid',
    reason TEXT NOT NULL,
    source_id INT UNSIGNED NULL,
    UNIQUE KEY uq_unsafe_animal_food (animal_id, food_name),
    CONSTRAINT fk_unsafe_animal FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_unsafe_source FOREIGN KEY (source_id) REFERENCES sources(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS feed_sources (
    feed_id INT UNSIGNED NOT NULL,
    source_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (feed_id, source_id),
    CONSTRAINT fk_fs_feed FOREIGN KEY (feed_id) REFERENCES feeds(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_fs_source FOREIGN KEY (source_id) REFERENCES sources(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS feeding_plan_sources (
    plan_id INT UNSIGNED NOT NULL,
    source_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (plan_id, source_id),
    CONSTRAINT fk_fps_plan FOREIGN KEY (plan_id) REFERENCES feeding_plans(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_fps_source FOREIGN KEY (source_id) REFERENCES sources(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;
