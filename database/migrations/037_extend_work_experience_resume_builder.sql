-- =============================================================================
-- Migration : 037_extend_work_experience_resume_builder
-- Project   : JobVisa.lk Enterprise
-- Purpose   : Sprint 2D.5 — additive columns + skills pivot (reuse work_experience)
-- Depends   : 015_create_work_experience_table, 005_create_skills_table
-- Preserves : all existing work_experience and user_skills rows
-- =============================================================================

ALTER TABLE `work_experience`
    ADD COLUMN IF NOT EXISTS `employment_type` VARCHAR(64) NULL AFTER `job_title`,
    ADD COLUMN IF NOT EXISTS `industry` VARCHAR(150) NULL AFTER `employment_type`,
    ADD COLUMN IF NOT EXISTS `city` VARCHAR(120) NULL AFTER `country_id`,
    ADD COLUMN IF NOT EXISTS `responsibilities` TEXT NULL AFTER `description`,
    ADD COLUMN IF NOT EXISTS `achievements` TEXT NULL AFTER `responsibilities`,
    ADD COLUMN IF NOT EXISTS `reason_for_leaving` TEXT NULL AFTER `achievements`,
    ADD COLUMN IF NOT EXISTS `supervisor_name` VARCHAR(150) NULL AFTER `reason_for_leaving`,
    ADD COLUMN IF NOT EXISTS `supervisor_contact` VARCHAR(150) NULL AFTER `supervisor_name`,
    ADD COLUMN IF NOT EXISTS `status` VARCHAR(32) NOT NULL DEFAULT 'active' AFTER `sort_order`,
    ADD COLUMN IF NOT EXISTS `deleted_at` DATETIME(3) NULL DEFAULT NULL AFTER `updated_at`;

-- Backfill responsibilities from legacy description where empty
UPDATE `work_experience`
SET `responsibilities` = `description`
WHERE (`responsibilities` IS NULL OR `responsibilities` = '')
  AND `description` IS NOT NULL
  AND `description` <> '';

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'work_experience'
              AND INDEX_NAME = 'idx_work_experience_deleted_at'
        ),
        'SELECT 1',
        'ALTER TABLE `work_experience` ADD INDEX `idx_work_experience_deleted_at` (`resume_id`, `deleted_at`)'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS `work_experience_skills` (
    `work_experience_id` BIGINT UNSIGNED NOT NULL,
    `skill_id` BIGINT UNSIGNED NOT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`work_experience_id`, `skill_id`),
    KEY `idx_wes_skill_id` (`skill_id`),
    CONSTRAINT `fk_wes_experience`
        FOREIGN KEY (`work_experience_id`) REFERENCES `work_experience` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_wes_skill`
        FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
