CREATE TABLE typecho_comments ( "coid" INTEGER NOT NULL PRIMARY KEY,
"cid" int(10) default '0' ,
"created" int(10) default '0' ,
"author" varchar(150) default NULL ,
"authorId" int(10) default '0' ,
"ownerId" int(10) default '0' ,
"mail" varchar(150) default NULL ,
"url" varchar(255) default NULL ,
"ip" varchar(64) default NULL , 
"agent" varchar(511) default NULL ,
"text" text , 
"type" varchar(16) default 'comment' , 
"status" varchar(16) default 'approved' , 
"parent" int(10) default '0' );

CREATE INDEX typecho_comments_cid ON typecho_comments ("cid");
CREATE INDEX typecho_comments_created ON typecho_comments ("created");
CREATE INDEX typecho_comments_status_created ON typecho_comments ("status", "created");
CREATE INDEX typecho_comments_cid_status_created ON typecho_comments ("cid", "status", "created");
CREATE INDEX typecho_comments_owner_status_created ON typecho_comments ("ownerId", "status", "created");

CREATE TABLE typecho_contents ( "cid" INTEGER NOT NULL PRIMARY KEY, 
"title" varchar(150) default NULL ,
"slug" varchar(150) default NULL ,
"created" int(10) default '0' , 
"modified" int(10) default '0' , 
"text" text , 
"order" int(10) default '0' , 
"authorId" int(10) default '0' , 
"template" varchar(32) default NULL , 
"type" varchar(16) default 'post' , 
"status" varchar(16) default 'publish' , 
"password" varchar(32) default NULL , 
"commentsNum" int(10) default '0' , 
"allowComment" char(1) default '0' , 
"allowPing" char(1) default '0' , 
"allowFeed" char(1) default '0' ,
"parent" int(10) default '0' );

CREATE UNIQUE INDEX typecho_contents_slug ON typecho_contents ("slug");
CREATE INDEX typecho_contents_created ON typecho_contents ("created");
CREATE INDEX typecho_contents_type_status_created ON typecho_contents ("type", "status", "created");
CREATE INDEX typecho_contents_author_status_created ON typecho_contents ("authorId", "status", "created");
CREATE INDEX typecho_contents_parent_type ON typecho_contents ("parent", "type");
CREATE INDEX typecho_contents_parent_type_modified ON typecho_contents ("parent", "type", "modified");
CREATE INDEX typecho_contents_type_status_modified ON typecho_contents ("type", "status", "modified");

CREATE TABLE "typecho_fields" ("cid" INTEGER NOT NULL,
  "name" varchar(150) NOT NULL,
  "type" varchar(8) default 'str',
  "str_value" text,
  "int_value" int(10) default '0',
  "float_value" real default '0'
);

CREATE UNIQUE INDEX typecho_fields_cid_name ON typecho_fields ("cid", "name");
CREATE INDEX typecho_fields_int_value ON typecho_fields ("int_value");
CREATE INDEX typecho_fields_float_value ON typecho_fields ("float_value");
CREATE INDEX typecho_fields_name ON typecho_fields ("name");
CREATE INDEX typecho_fields_name_int_value ON typecho_fields ("name", "int_value");

CREATE TABLE typecho_metas ( "mid" INTEGER NOT NULL PRIMARY KEY, 
"name" varchar(150) default NULL ,
"slug" varchar(150) default NULL ,
"type" varchar(32) NOT NULL , 
"description" varchar(150) default NULL ,
"aliases" text default NULL ,
"count" int(10) default '0' , 
"order" int(10) default '0' ,
"parent" int(10) default '0');

CREATE INDEX typecho_metas_slug ON typecho_metas ("slug");
CREATE INDEX typecho_metas_type_slug ON typecho_metas ("type", "slug");
CREATE INDEX typecho_metas_type_name ON typecho_metas ("type", "name");
CREATE INDEX typecho_metas_type_order ON typecho_metas ("type", "order");
CREATE INDEX typecho_metas_parent ON typecho_metas ("parent");

CREATE TABLE typecho_options ( "name" varchar(32) NOT NULL , 
"user" int(10) NOT NULL default '0' , 
"value" text );

CREATE UNIQUE INDEX typecho_options_name_user ON typecho_options ("name", "user");
CREATE INDEX typecho_options_user ON typecho_options ("user");

CREATE TABLE typecho_relationships ( "cid" int(10) NOT NULL , 
"mid" int(10) NOT NULL );

CREATE UNIQUE INDEX typecho_relationships_cid_mid ON typecho_relationships ("cid", "mid");
CREATE INDEX typecho_relationships_mid ON typecho_relationships ("mid");

CREATE TABLE typecho_migrations ( "name" varchar(128) NOT NULL PRIMARY KEY,
"executed" int(10) NOT NULL default '0' );

CREATE TABLE typecho_logs ( "lid" INTEGER NOT NULL PRIMARY KEY,
"created" int(10) default '0',
"userId" int(10) default '0',
"action" varchar(32) default NULL,
"targetType" varchar(32) default NULL,
"targetId" int(10) default '0',
"targetTitle" varchar(150) default NULL,
"message" text,
"ip" varchar(64) default NULL );

CREATE INDEX typecho_logs_created ON typecho_logs ("created");
CREATE INDEX typecho_logs_user_created ON typecho_logs ("userId", "created");
CREATE INDEX typecho_logs_target ON typecho_logs ("targetType", "targetId");

CREATE TABLE typecho_users ( "uid" INTEGER NOT NULL PRIMARY KEY, 
"name" varchar(32) default NULL ,
"password" varchar(64) default NULL , 
"mail" varchar(150) default NULL ,
"url" varchar(150) default NULL ,
"screenName" varchar(32) default NULL , 
"created" int(10) default '0' , 
"activated" int(10) default '0' , 
"logged" int(10) default '0' , 
"group" varchar(16) default 'visitor' , 
"authCode" varchar(64) default NULL);

CREATE UNIQUE INDEX typecho_users_name ON typecho_users ("name");
CREATE UNIQUE INDEX typecho_users_mail ON typecho_users ("mail");
