CREATE SEQUENCE ezstarrating_s
    START 1
    INCREMENT 1
    MAXVALUE 9223372036854775807
    MINVALUE 1
    CACHE 1;

CREATE TABLE ezstarrating (
  id integer DEFAULT nextval('ezstarrating_s'::text) NOT NULL,
  created_at integer DEFAULT 0 NOT NULL,
  user_id integer DEFAULT 0 NOT NULL,
  session_key varchar(32) NOT NULL,
  rating integer DEFAULT 0 NOT NULL,
  contentobject_id integer DEFAULT 0 NOT NULL,
  contentobject_attribute_id integer DEFAULT 0 NOT NULL
);

ALTER TABLE ONLY ezstarrating
    ADD CONSTRAINT ezstarrating_pkey PRIMARY KEY (id);

CREATE INDEX ezsr_user_id_session_key ON ezstarrating USING btree (user_id, session_key);
CREATE INDEX ezsr_contentobject_id_contentobject_attribute_id ON ezstarrating USING btree (contentobject_id, contentobject_attribute_id);
