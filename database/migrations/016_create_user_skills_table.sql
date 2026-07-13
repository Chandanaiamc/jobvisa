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
