-- Rollback: 048_extend_resume_intelligence_engine
DROP TABLE IF EXISTS `resume_intelligence_history`;

-- Note: MariaDB/MySQL may not support DROP COLUMN IF EXISTS on all versions.
-- Safe rollback of additive columns when supported:
ALTER TABLE `resume_intelligence_snapshots`
    DROP COLUMN IF EXISTS `analysis_json`,
    DROP COLUMN IF EXISTS `keyword_match_score`;
