--
-- Table structure for table "typecho_comments"
--
CREATE SEQUENCE "typecho_comments_seq";

CREATE TABLE "typecho_comments" (  "coid" INT NOT NULL DEFAULT nextval('typecho_comments_seq'),
  "cid" INT NULL DEFAULT '0',
  "created" INT NULL DEFAULT '0',
  "author" VARCHAR(150) NULL DEFAULT NULL,
  "authorId" INT NULL DEFAULT '0',
  "ownerId" INT NULL DEFAULT '0',
  "mail" VARCHAR(150) NULL DEFAULT NULL,
  "url" VARCHAR(255) NULL DEFAULT NULL,
  "ip" VARCHAR(64) NULL DEFAULT NULL,
  "agent" VARCHAR(511) NULL DEFAULT NULL,
  "text" TEXT NULL DEFAULT NULL,
  "type" VARCHAR(16) NULL DEFAULT 'comment',
  "status" VARCHAR(16) NULL DEFAULT 'approved',
  "parent" INT NULL DEFAULT '0',
  PRIMARY KEY ("coid")
);

CREATE INDEX "typecho_comments_cid" ON "typecho_comments" ("cid");
CREATE INDEX "typecho_comments_created" ON "typecho_comments" ("created");
CREATE INDEX "typecho_comments_status_created" ON "typecho_comments" ("status", "created");
CREATE INDEX "typecho_comments_cid_status_created" ON "typecho_comments" ("cid", "status", "created");
CREATE INDEX "typecho_comments_owner_status_created" ON "typecho_comments" ("ownerId", "status", "created");


--
-- Table structure for table "typecho_contents"
--

CREATE SEQUENCE "typecho_contents_seq";

CREATE TABLE "typecho_contents" (  "cid" INT NOT NULL DEFAULT nextval('typecho_contents_seq'),
  "title" VARCHAR(150) NULL DEFAULT NULL,
  "slug" VARCHAR(150) NULL DEFAULT NULL,
  "created" INT NULL DEFAULT '0',
  "modified" INT NULL DEFAULT '0',
  "text" TEXT NULL DEFAULT NULL,
  "order" INT NULL DEFAULT '0',
  "authorId" INT NULL DEFAULT '0',
  "template" VARCHAR(32) NULL DEFAULT NULL,
  "type" VARCHAR(16) NULL DEFAULT 'post',
  "status" VARCHAR(16) NULL DEFAULT 'publish',
  "password" VARCHAR(32) NULL DEFAULT NULL,
  "commentsNum" INT NULL DEFAULT '0',
  "allowComment" CHAR(1) NULL DEFAULT '0',
  "allowPing" CHAR(1) NULL DEFAULT '0',
  "allowFeed" CHAR(1) NULL DEFAULT '0',
  "parent" INT NULL DEFAULT '0',
  PRIMARY KEY ("cid"),
  UNIQUE ("slug")
);

CREATE INDEX "typecho_contents_created" ON "typecho_contents" ("created");
CREATE INDEX "typecho_contents_type_status_created" ON "typecho_contents" ("type", "status", "created");
CREATE INDEX "typecho_contents_author_status_created" ON "typecho_contents" ("authorId", "status", "created");
CREATE INDEX "typecho_contents_parent_type" ON "typecho_contents" ("parent", "type");

--
-- Table structure for table "typecho_fields"
--

CREATE TABLE "typecho_fields" ("cid" INT NOT NULL,
  "name" VARCHAR(150) NOT NULL,
  "type" VARCHAR(8) NULL DEFAULT 'str',
  "str_value" TEXT NULL DEFAULT NULL,
  "int_value" INT NULL DEFAULT '0',
  "float_value" REAL NULL DEFAULT '0',
  PRIMARY KEY  ("cid","name")
);

CREATE INDEX "typecho_fields_int_value" ON "typecho_fields" ("int_value");
CREATE INDEX "typecho_fields_float_value" ON "typecho_fields" ("float_value");

--
-- Table structure for table "typecho_metas"
--

CREATE SEQUENCE "typecho_metas_seq";

CREATE TABLE "typecho_metas" (  "mid" INT NOT NULL DEFAULT nextval('typecho_metas_seq'),
  "name" VARCHAR(150) NULL DEFAULT NULL,
  "slug" VARCHAR(150) NULL DEFAULT NULL,
  "type" VARCHAR(16) NOT NULL DEFAULT '',
  "description" VARCHAR(150) NULL DEFAULT NULL,
  "count" INT NULL DEFAULT '0',
  "order" INT NULL DEFAULT '0',
  "parent" INT NULL DEFAULT '0',
  PRIMARY KEY ("mid")
);

CREATE INDEX "typecho_metas_slug" ON "typecho_metas" ("slug");
CREATE INDEX "typecho_metas_type_slug" ON "typecho_metas" ("type", "slug");
CREATE INDEX "typecho_metas_type_name" ON "typecho_metas" ("type", "name");
CREATE INDEX "typecho_metas_type_order" ON "typecho_metas" ("type", "order");
CREATE INDEX "typecho_metas_parent" ON "typecho_metas" ("parent");


--
-- Table structure for table "typecho_options"
--

CREATE TABLE "typecho_options" (  "name" VARCHAR(32) NOT NULL DEFAULT '',
  "user" INT NOT NULL DEFAULT '0',
  "value" TEXT NULL DEFAULT NULL,
  PRIMARY KEY ("name","user")
);

CREATE INDEX "typecho_options_user" ON "typecho_options" ("user");

--
-- Table structure for table "typecho_relationships"
--

CREATE TABLE "typecho_relationships" (  "cid" INT NOT NULL DEFAULT '0',
  "mid" INT NOT NULL DEFAULT '0',
  PRIMARY KEY ("cid","mid")
); 

CREATE INDEX "typecho_relationships_mid" ON "typecho_relationships" ("mid");

--
-- Table structure for table "typecho_migrations"
--

CREATE TABLE "typecho_migrations" (  "name" VARCHAR(128) NOT NULL,
  "executed" INT NOT NULL DEFAULT '0',
  PRIMARY KEY ("name")
);

--
-- Table structure for table "typecho_users"
--
CREATE SEQUENCE "typecho_users_seq";

CREATE TABLE "typecho_users" (  "uid" INT NOT NULL DEFAULT nextval('typecho_users_seq') ,
  "name" VARCHAR(32) NULL DEFAULT NULL,
  "password" VARCHAR(64) NULL DEFAULT NULL,
  "mail" VARCHAR(150) NULL DEFAULT NULL,
  "url" VARCHAR(150) NULL DEFAULT NULL,
  "screenName" VARCHAR(32) NULL DEFAULT NULL,
  "created" INT NULL DEFAULT '0',
  "activated" INT NULL DEFAULT '0',
  "logged" INT NULL DEFAULT '0',
  "group" VARCHAR(16) NULL DEFAULT 'visitor',
  "authCode" VARCHAR(64) NULL DEFAULT NULL,
  PRIMARY KEY ("uid"),
  UNIQUE ("name"),
  UNIQUE ("mail")
);
