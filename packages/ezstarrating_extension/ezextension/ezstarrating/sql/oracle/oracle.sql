CREATE SEQUENCE s_starrating_data;

CREATE TABLE ezstarrating_data (
  id integer NOT NULL,
  created_at integer DEFAULT 0 NOT NULL,
  user_id integer DEFAULT 0 NOT NULL,
  session_key varchar2(32) NOT NULL,
  rating double precision DEFAULT 0 NOT NULL,
  contentobject_id integer DEFAULT 0 NOT NULL,
  contentobject_attribute_id integer DEFAULT 0 NOT NULL,
  PRIMARY KEY(id)
);

CREATE INDEX user_id_session_key ON ezstarrating_data (user_id, session_key);
CREATE INDEX ezsr_data_cobj_id_cobj_att_id ON ezstarrating_data (contentobject_id, contentobject_attribute_id);

CREATE TABLE ezstarrating (
  contentobject_id integer DEFAULT 0 NOT NULL,
  contentobject_attribute_id integer DEFAULT 0 NOT NULL,
  rating_average double precision DEFAULT 0 NOT NULL,
  rating_count integer DEFAULT 0 NOT NULL,
  PRIMARY KEY (contentobject_id, contentobject_attribute_id)
);

CREATE OR REPLACE TRIGGER ezstarrating_data_id_tr
BEFORE INSERT ON ezstarrating_data FOR EACH ROW WHEN (new.id IS NULL)
BEGIN
  SELECT s_starrating_data.nextval INTO :new.id FROM dual;
END;
/
