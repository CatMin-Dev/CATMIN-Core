-- Backup module tables into archive_* copies (structure simplified)
-- Review and adapt per DB engine before execution.
CREATE TABLE IF NOT EXISTS archive_cat_page_pages AS SELECT * FROM cat_page_pages;
CREATE TABLE IF NOT EXISTS archive_cat_page_pages_meta AS SELECT * FROM cat_page_pages_meta;
CREATE TABLE IF NOT EXISTS archive_cat_page_revisions AS SELECT * FROM cat_page_revisions;
CREATE TABLE IF NOT EXISTS archive_cat_page_audit_trail AS SELECT * FROM cat_page_audit_trail;
CREATE TABLE IF NOT EXISTS archive_cat_page_workflow AS SELECT * FROM cat_page_workflow;
CREATE TABLE IF NOT EXISTS archive_cat_page_preview_tokens AS SELECT * FROM cat_page_preview_tokens;
CREATE TABLE IF NOT EXISTS archive_cat_page_search_index AS SELECT * FROM cat_page_search_index;
CREATE TABLE IF NOT EXISTS archive_mod_cat_blog_posts AS SELECT * FROM mod_cat_blog_posts;
CREATE TABLE IF NOT EXISTS archive_mod_cat_blog_meta AS SELECT * FROM mod_cat_blog_meta;
CREATE TABLE IF NOT EXISTS archive_mod_cat_blog_revisions AS SELECT * FROM mod_cat_blog_revisions;
CREATE TABLE IF NOT EXISTS archive_mod_cat_logger_logs AS SELECT * FROM mod_cat_logger_logs;
CREATE TABLE IF NOT EXISTS archive_cat_cache_entries AS SELECT * FROM cat_cache_entries;
CREATE TABLE IF NOT EXISTS archive_cat_media_assets AS SELECT * FROM cat_media_assets;
CREATE TABLE IF NOT EXISTS archive_cat_menu_items AS SELECT * FROM cat_menu_items;
CREATE TABLE IF NOT EXISTS archive_cat_relations_links AS SELECT * FROM cat_relations_links;
CREATE TABLE IF NOT EXISTS archive_cat_search_index AS SELECT * FROM cat_search_index;
CREATE TABLE IF NOT EXISTS archive_cat_seo_rules AS SELECT * FROM cat_seo_rules;
CREATE TABLE IF NOT EXISTS archive_cat_settings_extended AS SELECT * FROM cat_settings_extended;
CREATE TABLE IF NOT EXISTS archive_cat_tags_tags AS SELECT * FROM cat_tags_tags;
CREATE TABLE IF NOT EXISTS archive_user_profiles AS SELECT * FROM user_profiles;
