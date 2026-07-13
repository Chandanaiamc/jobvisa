-- Rollback: 038_create_resume_skills
-- Drops resume_skills only. Catalogue `skills` and `user_skills` remain intact.

DROP TABLE IF EXISTS `resume_skills`;
