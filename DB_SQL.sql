/*==============================================================*/
/* DBMS name:      MySQL 5.0                                    */
/* Created on:     4/7/2017 11:51:18 AM                         */
/*==============================================================*/


drop table if exists FILE;

drop table if exists FILE_HISTORY;

drop index INDEX_1 on USER;

drop table if exists USER;

drop table if exists USER_PERMISSIONS;

drop table if exists WALL;

drop table if exists WALL_COMMENT;

drop table if exists WALL_FILE;

drop table if exists WALL_LIKE;

/*==============================================================*/
/* Table: FILE                                                  */
/*==============================================================*/
create table FILE
(
   FILE_SEQ             int not null auto_increment,
   FILE_NAME            varchar(255) not null,
   CREATED_ON           datetime,
   CREATED_BY           varchar(200),
   CHANGED_ON           datetime,
   CHANGED_BY           varchar(200),
   primary key (FILE_SEQ)
);

/*==============================================================*/
/* Table: FILE_HISTORY                                          */
/*==============================================================*/
create table FILE_HISTORY
(
   FILE_HISTORY_SEQ     int not null auto_increment,
   FILE_SEQ             int,
   ACTION               varchar(60),
   USER_SID             varchar(200),
   CREATED_ON           datetime,
   primary key (FILE_HISTORY_SEQ)
);

/*==============================================================*/
/* Table: USER                                                  */
/*==============================================================*/
create table USER
(
   USER_SEQ             int not null auto_increment,
   USER_PERMISSIONS_SEQ int,
   USERNAME             varchar(255) not null,
   PASSWORD             varchar(255) not null,
   CREATED_ON           datetime,
   CREATED_BY           varchar(200),
   CHANGED_ON           datetime,
   CHANGED_BY           varchar(200),
   primary key (USER_SEQ)
);

/*==============================================================*/
/* Index: INDEX_1                                               */
/*==============================================================*/
create unique index INDEX_1 on USER
(
   USERNAME
);

/*==============================================================*/
/* Table: USER_PERMISSIONS                                      */
/*==============================================================*/
create table USER_PERMISSIONS
(
   USER_PERMISSIONS_SEQ int not null auto_increment,
   PERMISSIONS_CODE     varchar(20) not null,
   CREATED_ON           datetime,
   CREATED_BY           varchar(200),
   CHANGED_ON           datetime,
   CHANGED_BY           varchar(200),
   primary key (USER_PERMISSIONS_SEQ)
);

/*==============================================================*/
/* Table: WALL                                                  */
/*==============================================================*/
create table WALL
(
   WALL_SEQ             int not null auto_increment,
   STATUS_TEXT          longtext,
   STATUS_TITLE         varchar(255),
   CREATED_ON           datetime,
   CREATED_BY           varchar(200),
   CHANGED_ON           datetime,
   CHANGED_BY           varchar(200),
   USER_SID             varchar(200),
   USER_SEQ             int,
   primary key (WALL_SEQ)
);

/*==============================================================*/
/* Table: WALL_COMMENT                                          */
/*==============================================================*/
create table WALL_COMMENT
(
   WALL_COMMENT_SEQ     int not null auto_increment,
   WALL_SEQ             int,
   WALL_COMMENT_TEXT    longtext not null,
   DATE                 datetime not null,
   CREATED_ON           datetime,
   CREATED_BY           varchar(200),
   CHANGED_ON           datetime,
   CHANGED_BY           varchar(200),
   USER_SID             varchar(200),
   USER_SEQ             int,
   primary key (WALL_COMMENT_SEQ)
);

/*==============================================================*/
/* Table: WALL_FILE                                             */
/*==============================================================*/
create table WALL_FILE
(
   FILE_SEQ             int,
   WALL_SEQ             int,
   CREATED_ON           datetime,
   CREATED_BY           varchar(200),
   CHANGED_ON           datetime,
   CHANGED_BY           varchar(200)
);

/*==============================================================*/
/* Table: WALL_LIKE                                             */
/*==============================================================*/
create table WALL_LIKE
(
   WALL_SEQ             int,
   CREATED_ON           datetime,
   CREATED_BY           varchar(200),
   CHANGED_ON           datetime,
   CHANGED_BY           varchar(200),
   USER_SID             varchar(200),
   USER_SEQ             int
);

alter table USER add constraint FK_REFERENCE_8 foreign key (USER_PERMISSIONS_SEQ)
      references USER_PERMISSIONS (USER_PERMISSIONS_SEQ) on delete restrict on update restrict;

alter table WALL_COMMENT add constraint FK_REFERENCE_6 foreign key (WALL_SEQ)
      references WALL (WALL_SEQ) on delete restrict on update restrict;

alter table WALL_FILE add constraint FK_REFERENCE_1 foreign key (FILE_SEQ)
      references FILE (FILE_SEQ) on delete restrict on update restrict;

alter table WALL_FILE add constraint FK_REFERENCE_2 foreign key (WALL_SEQ)
      references WALL (WALL_SEQ) on delete restrict on update restrict;

alter table WALL_LIKE add constraint FK_REFERENCE_4 foreign key (WALL_SEQ)
      references WALL (WALL_SEQ) on delete restrict on update restrict;

