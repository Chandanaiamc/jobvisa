-- Rollback: 039_create_resume_languages
-- Drops resume_languages only. Catalogue `languages` and `user_languages` remain intact.

DROP TABLE IF EXISTS `resume_languages`;
