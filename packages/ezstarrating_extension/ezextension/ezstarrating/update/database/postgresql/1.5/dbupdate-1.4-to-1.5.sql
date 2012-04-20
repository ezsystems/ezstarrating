ALTER TABLE ezstarrating RENAME COLUMN rating_average TO rating_average_tmp;
ALTER TABLE ezstarrating ADD COLUMN rating_average real;
ALTER TABLE ezstarrating ALTER rating_average SET DEFAULT 0::real ;
ALTER TABLE ezstarrating ALTER rating_average SET NOT NULL ;
UPDATE ezstarrating SET rating_average=rating_average_tmp;
ALTER TABLE ezstarrating DROP COLUMN rating_average_tmp;

ALTER TABLE ezstarrating_data RENAME COLUMN rating TO rating_tmp;
ALTER TABLE ezstarrating_data ADD COLUMN rating real;
ALTER TABLE ezstarrating_data ALTER rating SET DEFAULT 0::real ;
ALTER TABLE ezstarrating_data ALTER rating SET NOT NULL ;
UPDATE ezstarrating_data SET rating=rating_tmp;
ALTER TABLE ezstarrating_data DROP COLUMN rating_tmp;
