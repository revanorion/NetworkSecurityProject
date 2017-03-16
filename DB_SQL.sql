/*==============================================================*/
/* DBMS name:      MySQL 5.0                                    */
/* Created on:     11/20/2016 12:20:05 PM                       */
/*==============================================================*/


drop table if exists IMAGE;

drop table if exists USER;

drop table if exists WALL;

drop table if exists WALL_COMMENT;

drop table if exists WALL_COMMENT_LIKE;

drop table if exists WALL_IMAGE;

drop table if exists WALL_LIKE;

/*==============================================================*/
/* Table: IMAGE                                                 */
/*==============================================================*/
create table IMAGE
(
   IMAGE_SEQ            int not null auto_increment,
   IMAGE_NAME           varchar(255) not null,
   primary key (IMAGE_SEQ)
);

/*==============================================================*/
/* Table: USER                                                  */
/*==============================================================*/
create table USER
(
   USER_SEQ             int not null auto_increment,
   USERNAME             varchar(255) not null,
   PASSWORD             varchar(255) not null,
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
/* Table: WALL                                                  */
/*==============================================================*/
create table WALL
(
   WALL_SEQ             int not null auto_increment,
   USER_SEQ             int,
   STATUS_TEXT          longtext,
   STATUS_TITLE         varchar(255),
   TIME_STAMP           timestamp,
   primary key (WALL_SEQ)
);

/*==============================================================*/
/* Table: WALL_COMMENT                                          */
/*==============================================================*/
create table WALL_COMMENT
(
   WALL_COMMENT_SEQ     int not null auto_increment,
   WALL_SEQ             int,
   USER_SEQ             int,
   WALL_COMMENT_TEXT    longtext not null,
   DATE                 datetime not null,
   primary key (WALL_COMMENT_SEQ)
);

/*==============================================================*/
/* Table: WALL_COMMENT_LIKE                                     */
/*==============================================================*/
create table WALL_COMMENT_LIKE
(
   USER_SEQ             int,
   WALL_COMMENT_SEQ     int
);

/*==============================================================*/
/* Table: WALL_IMAGE                                            */
/*==============================================================*/
create table WALL_IMAGE
(
   IMAGE_SEQ            int,
   WALL_SEQ             int
);

/*==============================================================*/
/* Table: WALL_LIKE                                             */
/*==============================================================*/
create table WALL_LIKE
(
   WALL_SEQ             int,
   USER_SEQ             int
);

alter table WALL add constraint FK_REFERENCE_3 foreign key (USER_SEQ)
      references USER (USER_SEQ) on delete restrict on update restrict;

alter table WALL_COMMENT add constraint FK_REFERENCE_6 foreign key (WALL_SEQ)
      references WALL (WALL_SEQ) on delete restrict on update restrict;

alter table WALL_COMMENT add constraint FK_REFERENCE_7 foreign key (USER_SEQ)
      references USER (USER_SEQ) on delete restrict on update restrict;

alter table WALL_COMMENT_LIKE add constraint FK_REFERENCE_8 foreign key (USER_SEQ)
      references USER (USER_SEQ) on delete restrict on update restrict;

alter table WALL_COMMENT_LIKE add constraint FK_REFERENCE_9 foreign key (WALL_COMMENT_SEQ)
      references WALL_COMMENT (WALL_COMMENT_SEQ) on delete restrict on update restrict;

alter table WALL_IMAGE add constraint FK_REFERENCE_1 foreign key (IMAGE_SEQ)
      references IMAGE (IMAGE_SEQ) on delete restrict on update restrict;

alter table WALL_IMAGE add constraint FK_REFERENCE_2 foreign key (WALL_SEQ)
      references WALL (WALL_SEQ) on delete restrict on update restrict;

alter table WALL_LIKE add constraint FK_REFERENCE_4 foreign key (WALL_SEQ)
      references WALL (WALL_SEQ) on delete restrict on update restrict;

alter table WALL_LIKE add constraint FK_REFERENCE_5 foreign key (USER_SEQ)
      references USER (USER_SEQ) on delete restrict on update restrict;

