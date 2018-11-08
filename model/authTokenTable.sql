-- ************* AUTHTOKEN TABLE

-- Remove old definitions first then recreate new
DROP INDEX IF EXISTS "fki_autSelector";
-- DROP TRIGGER IF EXISTS update_authtoken_modtime ON authtoken;
DROP TABLE IF EXISTS authtoken CASCADE;


CREATE TABLE authtoken
(
  "aut_id" serial NOT NULL PRIMARY KEY,
  "aut_selector" varchar(32) NOT NULL UNIQUE,
  "aut_hashed_validator" varchar(64) NULL,
  "aut_expire" int default now()::abstime::int,
  "modified" timestamp default CURRENT_TIMESTAMP,
  "aut_user_id" int NOT NULL
)
WITH (
  OIDS=FALSE
);


CREATE TRIGGER update_authtoken_modtime BEFORE UPDATE ON authtoken FOR EACH ROW EXECUTE PROCEDURE  update_modified();

ALTER TABLE authtoken OWNER TO postgres;
COMMENT ON TABLE authtoken IS 'Contains a record for each authtoken.';
COMMENT ON COLUMN authtoken."aut_id" IS 'Uniquely identifies a authtoken object.';
COMMENT ON COLUMN authtoken."aut_selector" IS 'ISO Language code : label pairs for this authtoken.';
COMMENT ON COLUMN authtoken."aut_hashed_validator" IS 'hash of validator from cookie used to verify this authtoken.';
COMMENT ON COLUMN authtoken."aut_expire" IS 'expiration of token';
COMMENT ON COLUMN authtoken."aut_user_id" IS 'Link to user usergroup.';


-- Indexes:

CREATE INDEX "fki_autSelector" ON authtoken ("aut_selector");


