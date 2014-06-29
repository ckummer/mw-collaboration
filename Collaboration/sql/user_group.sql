-- (c) 2013 by Christian Kummer
-- Replace /*_*/ with the proper prefix
-- Replace /*$wgDBTableOptions*/ with the correct options

-- Add field to table user to store group membership
ALTER TABLE /*_*/user ADD user_wiki_group int(10);