-- =============================================================================
-- Migration : 045_create_resume_portfolios
-- Project   : JobVisa.lk Enterprise
-- Purpose   : Sprint 2E.3 — professional portfolio items + gallery images
-- Depends   : 013_create_resumes_table, 001_countries, 002_cities, 041_projects
-- Preserves : all existing tables (isolated create)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `resume_portfolios` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resume_id` BIGINT UNSIGNED NOT NULL,
    `project_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `country_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `city_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `title` VARCHAR(200) NOT NULL,
    `category` VARCHAR(64) NOT NULL DEFAULT 'other',
    `description` TEXT NULL DEFAULT NULL,
    `portfolio_url` VARCHAR(500) NULL DEFAULT NULL,
    `github_url` VARCHAR(500) NULL DEFAULT NULL,
    `behance_url` VARCHAR(500) NULL DEFAULT NULL,
    `dribbble_url` VARCHAR(500) NULL DEFAULT NULL,
    `figma_url` VARCHAR(500) NULL DEFAULT NULL,
    `youtube_url` VARCHAR(500) NULL DEFAULT NULL,
    `google_drive_url` VARCHAR(500) NULL DEFAULT NULL,
    `featured_image_path` VARCHAR(500) NULL DEFAULT NULL,
    `featured_image_original_name` VARCHAR(255) NULL DEFAULT NULL,
    `featured_image_mime` VARCHAR(120) NULL DEFAULT NULL,
    `featured_image_size` INT UNSIGNED NULL DEFAULT NULL,
    `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
    `visibility` VARCHAR(32) NOT NULL DEFAULT 'public',
    `status` VARCHAR(32) NOT NULL DEFAULT 'active',
    `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    `deleted_at` DATETIME(3) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_resume_portfolios_resume` (`resume_id`),
    KEY `idx_resume_portfolios_category` (`resume_id`, `category`),
    KEY `idx_resume_portfolios_visibility` (`resume_id`, `visibility`, `deleted_at`),
    KEY `idx_resume_portfolios_status` (`resume_id`, `status`, `deleted_at`),
    KEY `idx_resume_portfolios_featured` (`resume_id`, `is_featured`, `deleted_at`),
    KEY `idx_resume_portfolios_sort` (`resume_id`, `sort_order`),
    KEY `idx_resume_portfolios_deleted` (`resume_id`, `deleted_at`),
    KEY `idx_resume_portfolios_project` (`project_id`),
    KEY `idx_resume_portfolios_country` (`country_id`),
    KEY `idx_resume_portfolios_city` (`city_id`),
    CONSTRAINT `fk_resume_portfolios_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_resume_portfolios_project`
        FOREIGN KEY (`project_id`) REFERENCES `resume_projects` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_resume_portfolios_country`
        FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_resume_portfolios_city`
        FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `resume_portfolio_gallery` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `portfolio_id` BIGINT UNSIGNED NOT NULL,
    `image_path` VARCHAR(500) NOT NULL,
    `original_name` VARCHAR(255) NULL DEFAULT NULL,
    `mime` VARCHAR(120) NULL DEFAULT NULL,
    `file_size` INT UNSIGNED NULL DEFAULT NULL,
    `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    `deleted_at` DATETIME(3) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_portfolio_gallery_portfolio` (`portfolio_id`, `deleted_at`),
    KEY `idx_portfolio_gallery_sort` (`portfolio_id`, `sort_order`),
    CONSTRAINT `fk_portfolio_gallery_portfolio`
        FOREIGN KEY (`portfolio_id`) REFERENCES `resume_portfolios` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
