-- Rollback: 032_extend_job_seeker_profile

ALTER TABLE `user_languages`
    DROP COLUMN IF EXISTS `writing`,
    DROP COLUMN IF EXISTS `reading`,
    DROP COLUMN IF EXISTS `speaking`;

ALTER TABLE `education`
    DROP COLUMN IF EXISTS `grade`,
    DROP COLUMN IF EXISTS `school`;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'user_profiles'
              AND CONSTRAINT_NAME = 'fk_user_profiles_current_country'
        ),
        'ALTER TABLE `user_profiles` DROP FOREIGN KEY `fk_user_profiles_current_country`',
        'SELECT 1'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

ALTER TABLE `user_profiles`
    DROP COLUMN IF EXISTS `whatsapp`,
    DROP COLUMN IF EXISTS `address`,
    DROP COLUMN IF EXISTS `current_country_id`,
    DROP COLUMN IF EXISTS `expected_salary`,
    DROP COLUMN IF EXISTS `marital_status`,
    DROP COLUMN IF EXISTS `nic_passport`,
    DROP COLUMN IF EXISTS `last_name`,
    DROP COLUMN IF EXISTS `first_name`;
