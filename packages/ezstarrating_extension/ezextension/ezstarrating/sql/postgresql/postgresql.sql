CREATE SEQUENCE ezstarrating_data_s
    START 1
    INCREMENT 1
    MAXVALUE 9223372036854775807
    MINVALUE 1
    CACHE 1;

CREATE TABLE ezstarrating_data (
  id integer DEFAULT nextval('ezstarrating_data_s'::text) NOT NULL,
  created_at integer DEFAULT 0 NOT NULL,
  user_id integer DEFAULT 0 NOT NULL,
  session_key character varying(32) NOT NULL,
  rating real DEFAULT 0::real NOT NULL,
  contentobject_id integer DEFAULT 0 NOT NULL,
  contentobject_attribute_id integer DEFAULT 0 NOT NULL
);

ALTER TABLE ONLY ezstarrating_data
    ADD CONSTRAINT ezstarrating_data_pkey PRIMARY KEY (id);

CREATE INDEX user_id_session_key ON ezstarrating_data USING btree (user_id, session_key);
CREATE INDEX contentobject_id_contentobject_attribute_id ON ezstarrating_data USING btree (contentobject_id, contentobject_attribute_id);

CREATE TABLE ezstarrating (
  contentobject_id integer DEFAULT 0 NOT NULL,
  contentobject_attribute_id integer DEFAULT 0 NOT NULL,
  rating_average real DEFAULT 0::real NOT NULL,
  rating_count integer DEFAULT 0 NOT NULL
);

ALTER TABLE ONLY ezstarrating
    ADD CONSTRAINT ezstarrating_pkey PRIMARY KEY (contentobject_id, contentobject_attribute_id);
