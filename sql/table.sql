BEGIN;

CREATE TABLE IF NOT EXISTS /*_*/collegeinsider (
	-- wiki page this metadata is associated with
	page_id integer unsigned NOT NULL PRIMARY KEY,
	-- thumbnail filename
	thumbnail varchar(255) binary NOT NULL,
	-- article datestamp
	datestamp integer unsigned NOT NULL,
	-- byline
	byline text NOT NULL,
	-- description, shown with thumbnail
	blurb text NOT NULL,
	-- language
	lang char(2) NOT NULL DEFAULT 'en',
	-- keys
	FOREIGN KEY(page_id) REFERENCES /*_*/page(page_id)
);

COMMIT;