-- JobVisa.lk complete database schema
-- Engine: InnoDB | Charset: utf8mb4
-- Generated from database/migrations/*
-- No sample data included.

CREATE DATABASE IF NOT EXISTS `jobvisa_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `jobvisa_db`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ===== 001_create_countries_table.sql =====
-- Migration: create countries table
-- JobVisa.lk

CREATE TABLE IF NOT EXISTS `countries` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `iso2` CHAR(2) NOT NULL,
    `iso3` CHAR(3) NOT NULL,
    `phone_code` VARCHAR(8) DEFAULT NULL,
    `is_job_destination` TINYINT(1) NOT NULL DEFAULT 1,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_countries_iso2` (`iso2`),
    UNIQUE KEY `uq_countries_iso3` (`iso3`),
    KEY `idx_countries_name` (`name`),
    KEY `idx_countries_active_destination` (`is_active`, `is_job_destination`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ===== 002_create_cities_table.sql =====
-- Migration: create cities table
-- JobVisa.lk

CREATE TABLE IF NOT EXISTS `cities` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `country_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(120) NOT NULL,
    `slug` VARCHAR(150) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_cities_country_slug` (`country_id`, `slug`),
    KEY `idx_cities_country_id` (`country_id`),
    KEY `idx_cities_name` (`name`),
    KEY `idx_cities_active` (`is_active`),
    CONSTRAINT `fk_cities_country`
        FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ===== 003_create_job_categories_table.sql =====
-- Migration: create job_categories table
-- JobVisa.lk

CREATE TABLE IF NOT EXISTS `job_categories` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `parent_id` BIGINT UNSIGNED DEFAULT NULL,
    `name` VARCHAR(120) NOT NULL,
    `slug` VARCHAR(150) NOT NULL,
    `description` VARCHAR(500) DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_job_categories_slug` (`slug`),
    KEY `idx_job_categories_parent_id` (`parent_id`),
    KEY `idx_job_categories_active_sort` (`is_active`, `sort_order`),
    CONSTRAINT `fk_job_categories_parent`
        FOREIGN KEY (`parent_id`) REFERENCES `job_categories` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ===== 004_create_job_types_table.sql =====
-- Migration: create job_types table
-- JobVisa.lk

CREATE TABLE IF NOT EXISTS `job_types` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(80) NOT NULL,
    `slug` VARCHAR(80) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_job_types_slug` (`slug`),
    KEY `idx_job_types_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ===== 005_create_skills_table.sql =====
-- Migration: create skills table
-- JobVisa.lk

CREATE TABLE IF NOT EXISTS `skills` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(120) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_skills_slug` (`slug`),
    KEY `idx_skills_name` (`name`),
    KEY `idx_skills_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ===== 006_create_languages_table.sql =====
-- Migration: create languages table
-- JobVisa.lk

CREATE TABLE IF NOT EXISTS `languages` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(80) NOT NULL,
    `code` VARCHAR(10) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_languages_code` (`code`),
    KEY `idx_languages_name` (`name`),
    KEY `idx_languages_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ===== 007_create_subscription_plans_table.sql =====
-- Migration: create subscription_plans table
-- JobVisa.lk

CREATE TABLE IF NOT EXISTS `subscription_plans` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(50) NOT NULL,
    `name` VARCHAR(120) NOT NULL,
    `audience` VARCHAR(32) NOT NULL COMMENT 'employer|seeker',
    `description` TEXT DEFAULT NULL,
    `price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `currency` CHAR(3) NOT NULL DEFAULT 'LKR',
    `duration_days` INT UNSIGNED NOT NULL DEFAULT 30,
    `job_post_limit` INT UNSIGNED DEFAULT NULL,
    `features_json` JSON DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_subscription_plans_code` (`code`),
    KEY `idx_subscription_plans_audience_active` (`audience`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ===== 008_create_users_table.sql =====
-- Migration: create users table
-- JobVisa.lk

CREATE TABLE IF NOT EXISTS `users` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(191) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(32) DEFAULT NULL,
    `role` VARCHAR(32) NOT NULL DEFAULT 'seeker' COMMENT 'seeker|employer|admin|staff',
    `status` VARCHAR(32) NOT NULL DEFAULT 'active' COMMENT 'active|pending|suspended|deleted',
    `email_verified_at` DATETIME(3) DEFAULT NULL,
    `phone_verified_at` DATETIME(3) DEFAULT NULL,
    `last_login_at` DATETIME(3) DEFAULT NULL,
    `last_login_ip` VARCHAR(45) DEFAULT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`),
    KEY `idx_users_role_status` (`role`, `status`),
    KEY `idx_users_phone` (`phone`),
    KEY `idx_users_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ===== 009_create_user_profiles_table.sql =====
-- Migration: create user_profiles table
-- JobVisa.lk

CREATE TABLE IF NOT EXISTS `user_profiles` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `headline` VARCHAR(255) DEFAULT NULL,
    `summary` TEXT DEFAULT NULL,
    `date_of_birth` DATE DEFAULT NULL,
    `gender` VARCHAR(20) DEFAULT NULL,
    `nationality_country_id` BIGINT UNSIGNED DEFAULT NULL,
    `current_city_id` BIGINT UNSIGNED DEFAULT NULL,
    `preferred_country_id` BIGINT UNSIGNED DEFAULT NULL,
    `linkedin_url` VARCHAR(512) DEFAULT NULL,
    `avatar_path` VARCHAR(512) DEFAULT NULL,
    `visibility` VARCHAR(32) NOT NULL DEFAULT 'employers' COMMENT 'public|employers|private',
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_user_profiles_user_id` (`user_id`),
    KEY `idx_user_profiles_nationality` (`nationality_country_id`),
    KEY `idx_user_profiles_current_city` (`current_city_id`),
    KEY `idx_user_profiles_preferred_country` (`preferred_country_id`),
    CONSTRAINT `fk_user_profiles_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_user_profiles_nationality_country`
        FOREIGN KEY (`nationality_country_id`) REFERENCES `countries` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_user_profiles_current_city`
        FOREIGN KEY (`current_city_id`) REFERENCES `cities` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_user_profiles_preferred_country`
        FOREIGN KEY (`preferred_country_id`) REFERENCES `countries` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ===== 010_create_companies_table.sql =====
-- Migration: create companies table
-- JobVisa.lk

CREATE TABLE IF NOT EXISTS `companies` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(200) NOT NULL,
    `slug` VARCHAR(191) NOT NULL,
    `registration_no` VARCHAR(100) DEFAULT NULL,
    `website` VARCHAR(512) DEFAULT NULL,
    `logo_path` VARCHAR(512) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `industry` VARCHAR(100) DEFAULT NULL,
    `company_size` VARCHAR(50) DEFAULT NULL,
    `hq_country_id` BIGINT UNSIGNED DEFAULT NULL,
    `hq_city_id` BIGINT UNSIGNED DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_companies_slug` (`slug`),
    KEY `idx_companies_name` (`name`),
    KEY `idx_companies_industry` (`industry`),
    KEY `idx_companies_hq_country` (`hq_country_id`),
    KEY `idx_companies_active` (`is_active`),
    CONSTRAINT `fk_companies_hq_country`
        FOREIGN KEY (`hq_country_id`) REFERENCES `countries` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_companies_hq_city`
        FOREIGN KEY (`hq_city_id`) REFERENCES `cities` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ===== 011_create_employers_table.sql =====
-- Migration: create employers table
-- JobVisa.lk

CREATE TABLE IF NOT EXISTS `employers` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `company_id` BIGINT UNSIGNED NOT NULL,
    `job_title` VARCHAR(150) DEFAULT NULL,
    `verified_status` VARCHAR(32) NOT NULL DEFAULT 'pending' COMMENT 'pending|verified|rejected',
    `verified_at` DATETIME(3) DEFAULT NULL,
    `verified_by` BIGINT UNSIGNED DEFAULT NULL,
    `billing_email` VARCHAR(191) DEFAULT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_employers_user_id` (`user_id`),
    KEY `idx_employers_company_id` (`company_id`),
    KEY `idx_employers_verified_status` (`verified_status`),
    KEY `idx_employers_verified_by` (`verified_by`),
    CONSTRAINT `fk_employers_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_employers_company`
        FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_employers_verified_by`
        FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ===== 012_create_jobs_table.sql =====
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


-- ===== 013_create_resumes_table.sql =====
-- Migration: create resumes table
-- JobVisa.lk

CREATE TABLE IF NOT EXISTS `resumes` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `file_path` VARCHAR(512) DEFAULT NULL,
    `file_mime` VARCHAR(100) DEFAULT NULL,
    `file_size_bytes` INT UNSIGNED DEFAULT NULL,
    `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
    `completeness_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    KEY `idx_resumes_user_id` (`user_id`),
    KEY `idx_resumes_user_primary` (`user_id`, `is_primary`),
    CONSTRAINT `fk_resumes_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ===== 014_create_education_table.sql =====
-- Migration: create education table
-- JobVisa.lk

CREATE TABLE IF NOT EXISTS `education` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resume_id` BIGINT UNSIGNED NOT NULL,
    `institution` VARCHAR(200) NOT NULL,
    `degree` VARCHAR(150) NOT NULL,
    `field_of_study` VARCHAR(150) DEFAULT NULL,
    `start_date` DATE DEFAULT NULL,
    `end_date` DATE DEFAULT NULL,
    `is_current` TINYINT(1) NOT NULL DEFAULT 0,
    `description` TEXT DEFAULT NULL,
    `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    KEY `idx_education_resume_id` (`resume_id`),
    KEY `idx_education_resume_sort` (`resume_id`, `sort_order`),
    CONSTRAINT `fk_education_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ===== 015_create_work_experience_table.sql =====
-- Migration: create work_experience table
-- JobVisa.lk

CREATE TABLE IF NOT EXISTS `work_experience` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resume_id` BIGINT UNSIGNED NOT NULL,
    `company_name` VARCHAR(200) NOT NULL,
    `job_title` VARCHAR(150) NOT NULL,
    `country_id` BIGINT UNSIGNED DEFAULT NULL,
    `start_date` DATE DEFAULT NULL,
    `end_date` DATE DEFAULT NULL,
    `is_current` TINYINT(1) NOT NULL DEFAULT 0,
    `description` TEXT DEFAULT NULL,
    `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    KEY `idx_work_experience_resume_id` (`resume_id`),
    KEY `idx_work_experience_country_id` (`country_id`),
    KEY `idx_work_experience_resume_sort` (`resume_id`, `sort_order`),
    CONSTRAINT `fk_work_experience_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_work_experience_country`
        FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ===== 016_create_user_skills_table.sql =====
-- Migration: create user_skills table
-- JobVisa.lk

CREATE TABLE IF NOT EXISTS `user_skills` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `skill_id` BIGINT UNSIGNED NOT NULL,
    `proficiency` VARCHAR(32) DEFAULT NULL COMMENT 'beginner|intermediate|expert',
    `years_experience` TINYINT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_user_skills_user_skill` (`user_id`, `skill_id`),
    KEY `idx_user_skills_skill_id` (`skill_id`),
    CONSTRAINT `fk_user_skills_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_user_skills_skill`
        FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ===== 017_create_user_languages_table.sql =====
-- Migration: create user_languages table
-- JobVisa.lk

CREATE TABLE IF NOT EXISTS `user_languages` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `language_id` BIGINT UNSIGNED NOT NULL,
    `proficiency` VARCHAR(32) NOT NULL DEFAULT 'conversational' COMMENT 'basic|conversational|fluent|native',
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_user_languages_user_language` (`user_id`, `language_id`),
    KEY `idx_user_languages_language_id` (`language_id`),
    CONSTRAINT `fk_user_languages_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_user_languages_language`
        FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ===== 018_create_applications_table.sql =====
-- Migration: create applications table
-- JobVisa.lk

CREATE TABLE IF NOT EXISTS `applications` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `job_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `resume_id` BIGINT UNSIGNED DEFAULT NULL,
    `cover_letter` TEXT DEFAULT NULL,
    `status` VARCHAR(32) NOT NULL DEFAULT 'submitted' COMMENT 'submitted|reviewing|shortlisted|rejected|hired|withdrawn',
    `employer_notes` TEXT DEFAULT NULL,
    `applied_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `status_updated_at` DATETIME(3) DEFAULT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_applications_job_user` (`job_id`, `user_id`),
    KEY `idx_applications_user_applied` (`user_id`, `applied_at`),
    KEY `idx_applications_job_status` (`job_id`, `status`),
    KEY `idx_applications_resume_id` (`resume_id`),
    KEY `idx_applications_status_applied` (`status`, `applied_at`),
    CONSTRAINT `fk_applications_job`
        FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_applications_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_applications_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ===== 019_create_saved_jobs_table.sql =====
-- Migration: create saved_jobs table
-- JobVisa.lk

CREATE TABLE IF NOT EXISTS `saved_jobs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `job_id` BIGINT UNSIGNED NOT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_saved_jobs_user_job` (`user_id`, `job_id`),
    KEY `idx_saved_jobs_job_id` (`job_id`),
    KEY `idx_saved_jobs_user_created` (`user_id`, `created_at`),
    CONSTRAINT `fk_saved_jobs_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_saved_jobs_job`
        FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ===== 020_create_notifications_table.sql =====
-- Migration: create notifications table
-- JobVisa.lk

CREATE TABLE IF NOT EXISTS `notifications` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `type` VARCHAR(80) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `body` TEXT DEFAULT NULL,
    `data_json` JSON DEFAULT NULL,
    `read_at` DATETIME(3) DEFAULT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    KEY `idx_notifications_user_unread` (`user_id`, `read_at`, `id`),
    KEY `idx_notifications_user_created` (`user_id`, `created_at`),
    KEY `idx_notifications_type` (`type`),
    CONSTRAINT `fk_notifications_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ===== 021_create_payments_table.sql =====
-- Migration: create payments table
-- JobVisa.lk

CREATE TABLE IF NOT EXISTS `payments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED DEFAULT NULL,
    `employer_id` BIGINT UNSIGNED DEFAULT NULL,
    `subscription_plan_id` BIGINT UNSIGNED DEFAULT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `currency` CHAR(3) NOT NULL DEFAULT 'LKR',
    `provider` VARCHAR(50) NOT NULL DEFAULT 'manual' COMMENT 'payhere|stripe|bank|manual',
    `provider_ref` VARCHAR(191) DEFAULT NULL,
    `status` VARCHAR(32) NOT NULL DEFAULT 'pending' COMMENT 'pending|paid|failed|refunded',
    `paid_at` DATETIME(3) DEFAULT NULL,
    `meta_json` JSON DEFAULT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    KEY `idx_payments_user_id` (`user_id`),
    KEY `idx_payments_employer_id` (`employer_id`),
    KEY `idx_payments_plan_id` (`subscription_plan_id`),
    KEY `idx_payments_status_created` (`status`, `created_at`),
    KEY `idx_payments_provider_ref` (`provider_ref`),
    KEY `idx_payments_created_at` (`created_at`),
    CONSTRAINT `fk_payments_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_payments_employer`
        FOREIGN KEY (`employer_id`) REFERENCES `employers` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_payments_subscription_plan`
        FOREIGN KEY (`subscription_plan_id`) REFERENCES `subscription_plans` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ===== 022_create_audit_logs_table.sql =====
-- Migration: create audit_logs table
-- JobVisa.lk

CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `actor_user_id` BIGINT UNSIGNED DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `entity_type` VARCHAR(80) DEFAULT NULL,
    `entity_id` BIGINT UNSIGNED DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(512) DEFAULT NULL,
    `before_json` JSON DEFAULT NULL,
    `after_json` JSON DEFAULT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    KEY `idx_audit_logs_actor` (`actor_user_id`),
    KEY `idx_audit_logs_action` (`action`),
    KEY `idx_audit_logs_entity` (`entity_type`, `entity_id`),
    KEY `idx_audit_logs_created_at` (`created_at`),
    CONSTRAINT `fk_audit_logs_actor_user`
        FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
