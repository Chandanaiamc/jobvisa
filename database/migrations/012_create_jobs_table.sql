-- Migration: create jobs table
-- JobVisa.lk

CREATE TABLE IF NOT EXISTS `jobs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `employer_id` BIGINT UNSIGNED NOT NULL,
    `company_id` BIGINT UNSIGNED NOT NULL,
    `category_id` BIGINT UNSIGNED NOT NULL,
    `job_type_id` BIGINT UNSIGNED NOT NULL,
    `posted_by_user_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(191) NOT NULL,
    `description` MEDIUMTEXT NOT NULL,
    `requirements` MEDIUMTEXT DEFAULT NULL,
    `benefits` TEXT DEFAULT NULL,
    `country_id` BIGINT UNSIGNED NOT NULL,
    `city_id` BIGINT UNSIGNED DEFAULT NULL,
    `vacancies` INT UNSIGNED NOT NULL DEFAULT 1,
    `salary_min` DECIMAL(12,2) DEFAULT NULL,
    `salary_max` DECIMAL(12,2) DEFAULT NULL,
    `salary_currency` CHAR(3) DEFAULT NULL,
    `salary_period` VARCHAR(20) DEFAULT NULL COMMENT 'month|year|hour',
    `experience_min_years` TINYINT UNSIGNED DEFAULT NULL,
    `education_level` VARCHAR(50) DEFAULT NULL,
    `visa_sponsorship` TINYINT(1) NOT NULL DEFAULT 0,
    `application_deadline` DATE DEFAULT NULL,
    `status` VARCHAR(32) NOT NULL DEFAULT 'draft' COMMENT 'draft|pending|published|closed|rejected',
    `published_at` DATETIME(3) DEFAULT NULL,
    `closes_at` DATETIME(3) DEFAULT NULL,
    `views_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `applications_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_jobs_slug` (`slug`),
    KEY `idx_jobs_employer_status` (`employer_id`, `status`),
    KEY `idx_jobs_company_id` (`company_id`),
    KEY `idx_jobs_category_id` (`category_id`),
    KEY `idx_jobs_job_type_id` (`job_type_id`),
    KEY `idx_jobs_posted_by` (`posted_by_user_id`),
    KEY `idx_jobs_country_category_status` (`country_id`, `category_id`, `status`),
    KEY `idx_jobs_city_id` (`city_id`),
    KEY `idx_jobs_status_published` (`status`, `published_at`),
    KEY `idx_jobs_deadline` (`application_deadline`),
    KEY `idx_jobs_visa_sponsorship` (`visa_sponsorship`),
    FULLTEXT KEY `ft_jobs_title_description` (`title`, `description`),
    CONSTRAINT `fk_jobs_employer`
        FOREIGN KEY (`employer_id`) REFERENCES `employers` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_jobs_company`
        FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_jobs_category`
        FOREIGN KEY (`category_id`) REFERENCES `job_categories` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_jobs_job_type`
        FOREIGN KEY (`job_type_id`) REFERENCES `job_types` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_jobs_posted_by_user`
        FOREIGN KEY (`posted_by_user_id`) REFERENCES `users` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_jobs_country`
        FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_jobs_city`
        FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
