-- =============================================================================
-- Migration : 044_create_resume_publications
-- Project   : JobVisa.lk Enterprise
-- Purpose   : Sprint 2E.2 — resume publications & research
-- Depends   : 013_create_resumes_table, 001_countries, 002_cities, 041_projects
-- Preserves : all existing tables (isolated create)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `resume_publications` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resume_id` BIGINT UNSIGNED NOT NULL,
    `project_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `country_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `city_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `title` VARCHAR(300) NOT NULL,
    `publication_type` VARCHAR(64) NOT NULL,
    `publisher` VARCHAR(255) NULL DEFAULT NULL,
    `authors` TEXT NULL DEFAULT NULL,
    `user_contribution` VARCHAR(200) NULL DEFAULT NULL,
    `publication_date` DATE NULL DEFAULT NULL,
    `publication_year` SMALLINT UNSIGNED NULL DEFAULT NULL,
    `volume` VARCHAR(64) NULL DEFAULT NULL,
    `issue` VARCHAR(64) NULL DEFAULT NULL,
    `page_range` VARCHAR(64) NULL DEFAULT NULL,
    `doi` VARCHAR(200) NULL DEFAULT NULL,
    `isbn` VARCHAR(32) NULL DEFAULT NULL,
    `issn` VARCHAR(32) NULL DEFAULT NULL,
    `patent_number` VARCHAR(120) NULL DEFAULT NULL,
    `conference_name` VARCHAR(255) NULL DEFAULT NULL,
    `abstract_summary` TEXT NULL DEFAULT NULL,
    `keywords` VARCHAR(1000) NULL DEFAULT NULL,
    `publication_url` VARCHAR(500) NULL DEFAULT NULL,
    `document_path` VARCHAR(500) NULL DEFAULT NULL,
    `document_original_name` VARCHAR(255) NULL DEFAULT NULL,
    `document_mime` VARCHAR(120) NULL DEFAULT NULL,
    `document_size` INT UNSIGNED NULL DEFAULT NULL,
    `is_peer_reviewed` TINYINT(1) NOT NULL DEFAULT 0,
    `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
    `visibility` VARCHAR(32) NOT NULL DEFAULT 'public',
    `status` VARCHAR(32) NOT NULL DEFAULT 'active',
    `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    `deleted_at` DATETIME(3) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_resume_publications_resume` (`resume_id`),
    KEY `idx_resume_publications_type` (`resume_id`, `publication_type`),
    KEY `idx_resume_publications_year` (`resume_id`, `publication_year`),
    KEY `idx_resume_publications_visibility` (`resume_id`, `visibility`, `deleted_at`),
    KEY `idx_resume_publications_status` (`resume_id`, `status`, `deleted_at`),
    KEY `idx_resume_publications_featured` (`resume_id`, `is_featured`, `deleted_at`),
    KEY `idx_resume_publications_sort` (`resume_id`, `sort_order`),
    KEY `idx_resume_publications_deleted` (`resume_id`, `deleted_at`),
    KEY `idx_resume_publications_project` (`project_id`),
    KEY `idx_resume_publications_country` (`country_id`),
    KEY `idx_resume_publications_city` (`city_id`),
    KEY `idx_resume_publications_dup` (`resume_id`, `publication_year`, `title`(100)),
    CONSTRAINT `fk_resume_publications_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_resume_publications_project`
        FOREIGN KEY (`project_id`) REFERENCES `resume_projects` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_resume_publications_country`
        FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_resume_publications_city`
        FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
