-- (c) 2013 by Christian Kummer
-- Replace /*_*/ with the proper prefix
-- Replace /*$wgDBTableOptions*/ with the correct options

-- Add field to table user to indicate user's activity
ALTER TABLE /*_*/user ADD user_inactivity int(10);