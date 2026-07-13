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
