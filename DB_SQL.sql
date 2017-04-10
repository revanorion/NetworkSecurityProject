/*==============================================================*/
/* DBMS name:      MySQL 5.0                                    */
/* Created on:     4/10/2017 10:07:16 AM                        */
/*==============================================================*/


drop table if exists FILE;

drop table if exists FILE_HISTORY;

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
   USER_SID             varchar(200)
);

alter table WALL_COMMENT add constraint FK_REFERENCE_6 foreign key (WALL_SEQ)
      references WALL (WALL_SEQ) on delete restrict on update restrict;

alter table WALL_FILE add constraint FK_REFERENCE_1 foreign key (FILE_SEQ)
      references FILE (FILE_SEQ) on delete restrict on update restrict;

alter table WALL_FILE add constraint FK_REFERENCE_2 foreign key (WALL_SEQ)
      references WALL (WALL_SEQ) on delete restrict on update restrict;

alter table WALL_LIKE add constraint FK_REFERENCE_4 foreign key (WALL_SEQ)
      references WALL (WALL_SEQ) on delete restrict on update restrict;

