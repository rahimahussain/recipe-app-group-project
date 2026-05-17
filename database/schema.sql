-- =========================================================
-- ENTERPRISE RECIPE APPLICATION DATABASE SCHEMA
-- =========================================================

CREATE DATABASE IF NOT EXISTS recipe_app
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE recipe_app;

-- =========================================================
-- USERS
-- =========================================================

CREATE TABLE users (
                       id INT AUTO_INCREMENT PRIMARY KEY,

                       username VARCHAR(50) UNIQUE NOT NULL,
                       email VARCHAR(100) UNIQUE NOT NULL,
                       password_hash VARCHAR(255) NOT NULL,

                       first_name VARCHAR(50) NOT NULL,
                       last_name VARCHAR(50) NOT NULL,

                       bio TEXT,

                       dietary_preference ENUM(
                           'Vegetarian',
                           'Vegan',
                           'Non-vegetarian',
                           'None'
                           ) DEFAULT 'None',

                       role ENUM(
                           'Admin',
                           'Moderator',
                           'Chef',
                           'User'
                           ) DEFAULT 'User',

                       account_status ENUM(
                           'Active',
                           'Locked',
                           'Disabled',
                           'Pending'
                           ) DEFAULT 'Active',

                       profile_image_url VARCHAR(500),

                       phone_number VARCHAR(30),

                       last_login TIMESTAMP NULL,

                       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                       updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                           ON UPDATE CURRENT_TIMESTAMP,

                       INDEX idx_role (role),
                       INDEX idx_status (account_status),
                       INDEX idx_email (email)
) ENGINE=InnoDB;

-- =========================================================
-- CATEGORIES
-- =========================================================

CREATE TABLE categories (
                            id INT AUTO_INCREMENT PRIMARY KEY,

                            name VARCHAR(100) UNIQUE NOT NULL,

                            slug VARCHAR(120) UNIQUE NOT NULL,

                            description TEXT,

                            image_url VARCHAR(500),

                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                                ON UPDATE CURRENT_TIMESTAMP,

                            INDEX idx_slug (slug)
) ENGINE=InnoDB;

-- =========================================================
-- RECIPES
-- =========================================================

CREATE TABLE recipes (
                         id INT AUTO_INCREMENT PRIMARY KEY,

                         author_id INT NOT NULL,

                         title VARCHAR(255) NOT NULL,

                         slug VARCHAR(255) UNIQUE NOT NULL,

                         description TEXT,

                         image_url VARCHAR(500)
                                                        DEFAULT 'default-recipe.jpg',

                         prep_time_minutes INT NOT NULL DEFAULT 0,

                         cook_time_minutes INT NOT NULL DEFAULT 0,

                         total_time_minutes INT GENERATED ALWAYS AS
                             (prep_time_minutes + cook_time_minutes) STORED,

                         servings INT NOT NULL DEFAULT 1,

                         difficulty ENUM(
                             'Easy',
                             'Medium',
                             'Hard'
                             ) DEFAULT 'Medium',

                         cuisine_type VARCHAR(100),

                         source_url VARCHAR(500),

                         tips TEXT,

                         calories INT,

                         protein_g DECIMAL(10,2),

                         carbs_g DECIMAL(10,2),

                         fats_g DECIMAL(10,2),

                         fibre_g DECIMAL(10,2),

                         sugar_g DECIMAL(10,2),

                         sodium_mg DECIMAL(10,2),

                         visibility ENUM(
                             'Public',
                             'Private',
                             'Draft'
                             ) DEFAULT 'Public',

                         moderation_status ENUM(
                             'Pending',
                             'Approved',
                             'Rejected'
                             ) DEFAULT 'Approved',

                         average_rating DECIMAL(3,2) DEFAULT 0,

                         total_ratings INT DEFAULT 0,

                         view_count BIGINT DEFAULT 0,

                         is_featured BOOLEAN DEFAULT FALSE,

                         created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                         updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                             ON UPDATE CURRENT_TIMESTAMP,

                         FOREIGN KEY (author_id)
                             REFERENCES users(id)
                             ON DELETE CASCADE,

                         FULLTEXT INDEX ft_recipe_search
                             (title, description),

                         INDEX idx_author (author_id),

                         INDEX idx_visibility (visibility),

                         INDEX idx_moderation (moderation_status),

                         INDEX idx_difficulty (difficulty),

                         INDEX idx_created (created_at),

                         INDEX idx_recipe_visibility_created
                             (visibility, created_at)
) ENGINE=InnoDB;

-- =========================================================
-- RECIPE MEDIA
-- =========================================================

CREATE TABLE recipe_media (
                              id BIGINT AUTO_INCREMENT PRIMARY KEY,

                              recipe_id INT NOT NULL,

                              media_type ENUM(
                                  'Image',
                                  'Video'
                                  ) NOT NULL,

                              media_url VARCHAR(500) NOT NULL,

                              caption VARCHAR(255),

                              is_primary BOOLEAN DEFAULT FALSE,

                              sort_order INT DEFAULT 0,

                              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                              FOREIGN KEY (recipe_id)
                                  REFERENCES recipes(id)
                                  ON DELETE CASCADE,

                              INDEX idx_recipe_media (recipe_id)
) ENGINE=InnoDB;

-- =========================================================
-- RECIPE CATEGORIES
-- =========================================================

CREATE TABLE recipe_categories (
                                   recipe_id INT NOT NULL,

                                   category_id INT NOT NULL,

                                   PRIMARY KEY (recipe_id, category_id),

                                   FOREIGN KEY (recipe_id)
                                       REFERENCES recipes(id)
                                       ON DELETE CASCADE,

                                   FOREIGN KEY (category_id)
                                       REFERENCES categories(id)
                                       ON DELETE CASCADE,

                                   INDEX idx_category (category_id)
) ENGINE=InnoDB;

-- =========================================================
-- INGREDIENTS MASTER
-- =========================================================

CREATE TABLE ingredients (
                             id INT AUTO_INCREMENT PRIMARY KEY,

                             name VARCHAR(200) UNIQUE NOT NULL,

                             default_unit VARCHAR(50),

                             calories_per_100g DECIMAL(10,2),

                             allergens TEXT,

                             storage_instructions TEXT,

                             created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                             INDEX idx_ingredient_name (name)
) ENGINE=InnoDB;

-- =========================================================
-- RECIPE INGREDIENTS
-- =========================================================

CREATE TABLE recipe_ingredients (
                                    recipe_id INT NOT NULL,

                                    ingredient_id INT NOT NULL,

                                    quantity DECIMAL(10,2),

                                    unit VARCHAR(50),

                                    order_index INT DEFAULT 0,

                                    notes VARCHAR(255),

                                    PRIMARY KEY (recipe_id, ingredient_id),

                                    FOREIGN KEY (recipe_id)
                                        REFERENCES recipes(id)
                                        ON DELETE CASCADE,

                                    FOREIGN KEY (ingredient_id)
                                        REFERENCES ingredients(id),

                                    INDEX idx_recipe_ingredient (recipe_id),

                                    INDEX idx_ingredient_recipe (ingredient_id)
) ENGINE=InnoDB;

-- =========================================================
-- RECIPE STEPS
-- =========================================================

CREATE TABLE recipe_steps (
                              id INT AUTO_INCREMENT PRIMARY KEY,

                              recipe_id INT NOT NULL,

                              step_number INT NOT NULL,

                              instruction TEXT NOT NULL,

                              duration_minutes INT DEFAULT 0,

                              image_url VARCHAR(500),

                              video_url VARCHAR(500),

                              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                              FOREIGN KEY (recipe_id)
                                  REFERENCES recipes(id)
                                  ON DELETE CASCADE,

                              CONSTRAINT chk_step_number
                                  CHECK (step_number > 0),

                              UNIQUE KEY uk_recipe_step
                                  (recipe_id, step_number),

                              INDEX idx_recipe_steps
                                  (recipe_id, step_number)
) ENGINE=InnoDB;

-- =========================================================
-- FAVOURITES
-- =========================================================

CREATE TABLE favourites (
                            user_id INT NOT NULL,

                            recipe_id INT NOT NULL,

                            folder_name VARCHAR(100),

                            saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                            PRIMARY KEY (user_id, recipe_id),

                            FOREIGN KEY (user_id)
                                REFERENCES users(id)
                                ON DELETE CASCADE,

                            FOREIGN KEY (recipe_id)
                                REFERENCES recipes(id)
                                ON DELETE CASCADE,

                            INDEX idx_favourite_recipe (recipe_id)
) ENGINE=InnoDB;

-- =========================================================
-- RATINGS
-- =========================================================

CREATE TABLE ratings (
                         id BIGINT AUTO_INCREMENT PRIMARY KEY,

                         user_id INT NOT NULL,

                         recipe_id INT NOT NULL,

                         overall_rating TINYINT NOT NULL
                             CHECK (overall_rating BETWEEN 1 AND 5),

                         taste_rating TINYINT
                             CHECK (taste_rating BETWEEN 1 AND 5),

                         difficulty_rating TINYINT
                             CHECK (difficulty_rating BETWEEN 1 AND 5),

                         aesthetics_rating TINYINT
                             CHECK (aesthetics_rating BETWEEN 1 AND 5),

                         comment TEXT,

                         is_edited BOOLEAN DEFAULT FALSE,

                         moderation_status ENUM(
                             'Visible',
                             'Hidden',
                             'Flagged'
                             ) DEFAULT 'Visible',

                         created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                         updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                             ON UPDATE CURRENT_TIMESTAMP,

                         UNIQUE KEY uk_user_recipe_rating
                             (user_id, recipe_id),

                         FOREIGN KEY (user_id)
                             REFERENCES users(id)
                             ON DELETE CASCADE,

                         FOREIGN KEY (recipe_id)
                             REFERENCES recipes(id)
                             ON DELETE CASCADE,

                         INDEX idx_recipe_rating
                             (recipe_id, overall_rating DESC),

                         INDEX idx_rating_created
                             (created_at)
) ENGINE=InnoDB;

-- =========================================================
-- COMMENTS
-- =========================================================

CREATE TABLE comments (
                          id BIGINT AUTO_INCREMENT PRIMARY KEY,

                          recipe_id INT NOT NULL,

                          user_id INT NOT NULL,

                          parent_comment_id BIGINT NULL,

                          comment_text TEXT NOT NULL,

                          moderation_status ENUM(
                              'Visible',
                              'Hidden',
                              'Flagged'
                              ) DEFAULT 'Visible',

                          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                          updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                              ON UPDATE CURRENT_TIMESTAMP,

                          FOREIGN KEY (recipe_id)
                              REFERENCES recipes(id)
                              ON DELETE CASCADE,

                          FOREIGN KEY (user_id)
                              REFERENCES users(id)
                              ON DELETE CASCADE,

                          FOREIGN KEY (parent_comment_id)
                              REFERENCES comments(id)
                              ON DELETE CASCADE,

                          INDEX idx_recipe_comments (recipe_id),

                          INDEX idx_comment_user (user_id)
) ENGINE=InnoDB;

-- =========================================================
-- TAGS
-- =========================================================

CREATE TABLE tags (
                      id INT AUTO_INCREMENT PRIMARY KEY,

                      name VARCHAR(100) UNIQUE NOT NULL,

                      slug VARCHAR(120) UNIQUE NOT NULL,

                      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================================
-- RECIPE TAGS
-- =========================================================

CREATE TABLE recipe_tags (
                             recipe_id INT NOT NULL,

                             tag_id INT NOT NULL,

                             PRIMARY KEY (recipe_id, tag_id),

                             FOREIGN KEY (recipe_id)
                                 REFERENCES recipes(id)
                                 ON DELETE CASCADE,

                             FOREIGN KEY (tag_id)
                                 REFERENCES tags(id)
                                 ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- API TOKENS
-- =========================================================

CREATE TABLE api_tokens (
                            id BIGINT AUTO_INCREMENT PRIMARY KEY,

                            user_id INT NOT NULL,

                            token_hash VARCHAR(255) NOT NULL,

                            expires_at TIMESTAMP NULL,

                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                            FOREIGN KEY (user_id)
                                REFERENCES users(id)
                                ON DELETE CASCADE,

                            INDEX idx_token_user (user_id)
) ENGINE=InnoDB;

-- =========================================================
-- AUDIT LOGS
-- =========================================================

CREATE TABLE audit_logs (
                            id BIGINT AUTO_INCREMENT PRIMARY KEY,

                            user_id INT,

                            entity_name VARCHAR(100),

                            entity_id BIGINT,

                            action_type VARCHAR(50),

                            old_value JSON,

                            new_value JSON,

                            ip_address VARCHAR(45),

                            user_agent TEXT,

                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                            FOREIGN KEY (user_id)
                                REFERENCES users(id)
                                ON DELETE SET NULL,

                            INDEX idx_entity_audit
                                (entity_name, entity_id),

                            INDEX idx_user_audit
                                (user_id),

                            INDEX idx_audit_created
                                (created_at)
) ENGINE=InnoDB;

-- =========================================================
-- LOGIN HISTORY
-- =========================================================

CREATE TABLE login_history (
                               id BIGINT AUTO_INCREMENT PRIMARY KEY,

                               user_id INT NOT NULL,

                               ip_address VARCHAR(45),

                               user_agent TEXT,

                               login_status ENUM(
                                   'Success',
                                   'Failed'
                                   ) NOT NULL,

                               created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                               FOREIGN KEY (user_id)
                                   REFERENCES users(id)
                                   ON DELETE CASCADE,

                               INDEX idx_login_user (user_id),

                               INDEX idx_login_created (created_at)
) ENGINE=InnoDB;

-- =========================================================
-- NOTIFICATIONS
-- =========================================================

CREATE TABLE notifications (
                               id BIGINT AUTO_INCREMENT PRIMARY KEY,

                               user_id INT NOT NULL,

                               notification_type VARCHAR(100),

                               title VARCHAR(255),

                               message TEXT,

                               is_read BOOLEAN DEFAULT FALSE,

                               created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                               FOREIGN KEY (user_id)
                                   REFERENCES users(id)
                                   ON DELETE CASCADE,

                               INDEX idx_notification_user
                                   (user_id, is_read)
) ENGINE=InnoDB;

-- =========================================================
-- BOOKMARK COLLECTIONS
-- =========================================================

CREATE TABLE bookmark_collections (
                                      id BIGINT AUTO_INCREMENT PRIMARY KEY,

                                      user_id INT NOT NULL,

                                      collection_name VARCHAR(100) NOT NULL,

                                      description TEXT,

                                      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                                      FOREIGN KEY (user_id)
                                          REFERENCES users(id)
                                          ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- COLLECTION RECIPES
-- =========================================================

CREATE TABLE collection_recipes (
                                    collection_id BIGINT NOT NULL,

                                    recipe_id INT NOT NULL,

                                    PRIMARY KEY (collection_id, recipe_id),

                                    FOREIGN KEY (collection_id)
                                        REFERENCES bookmark_collections(id)
                                        ON DELETE CASCADE,

                                    FOREIGN KEY (recipe_id)
                                        REFERENCES recipes(id)
                                        ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- REPORTS / CONTENT MODERATION
-- =========================================================

CREATE TABLE reports (
                         id BIGINT AUTO_INCREMENT PRIMARY KEY,

                         reported_by_user_id INT NOT NULL,

                         entity_type ENUM(
                             'Recipe',
                             'Comment',
                             'Rating',
                             'User'
                             ) NOT NULL,

                         entity_id BIGINT NOT NULL,

                         reason TEXT NOT NULL,

                         status ENUM(
                             'Open',
                             'Investigating',
                             'Resolved',
                             'Dismissed'
                             ) DEFAULT 'Open',

                         created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                         FOREIGN KEY (reported_by_user_id)
                             REFERENCES users(id)
                             ON DELETE CASCADE,

                         INDEX idx_report_entity
                             (entity_type, entity_id)
) ENGINE=InnoDB;

-- =========================================================
-- PERFORMANCE INDEXES
-- =========================================================

CREATE INDEX idx_recipe_author_created
    ON recipes(author_id, created_at);

CREATE INDEX idx_recipe_visibility_created
    ON recipes(visibility, created_at);

CREATE INDEX idx_ratings_recipe_created
    ON ratings(recipe_id, created_at);

CREATE INDEX idx_recipe_average_rating
    ON recipes(average_rating DESC);

-- =========================================================
-- END OF SCHEMA
-- =========================================================