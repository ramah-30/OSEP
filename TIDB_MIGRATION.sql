-- TiDB Migration: Drop Recommendation and Automation Tables
-- Date: 2026-08-23
-- Purpose: Remove automation and prompt features from the system

-- Step 1: Drop dependent tables first (child tables before parent tables)
DROP TABLE IF EXISTS `ai_prompt_versions`;
DROP TABLE IF EXISTS `ai_prompt_templates`;
DROP TABLE IF EXISTS `ai_automation_runs`;
DROP TABLE IF EXISTS `ai_automation_rules`;
DROP TABLE IF EXISTS `ai_recommendations`;

-- Verify tables are dropped
SHOW TABLES LIKE 'ai_%';

-- Migration complete. These tables are now removed:
-- - ai_recommendations
-- - ai_automation_rules
-- - ai_automation_runs
-- - ai_prompt_templates
-- - ai_prompt_versions
