SELECT "Recreating canon database" AS NOTE;

DROP DATABASE IF EXISTS canon;
CREATE DATABASE canon
    DEFAULT CHARACTER SET utf8
    COLLATE utf8_bin;

SELECT "Creating canon user" AS NOTE;

DROP USER 'canon'@'localhost';
CREATE USER 'canon'@'localhost'
    IDENTIFIED BY 'Passw0rd!';
GRANT USAGE ON *.* TO 'canon'@'localhost'
    REQUIRE NONE
    WITH MAX_QUERIES_PER_HOUR 0
         MAX_CONNECTIONS_PER_HOUR 0
         MAX_UPDATES_PER_HOUR 0
         MAX_USER_CONNECTIONS 0;

GRANT ALL PRIVILEGES ON canon.* TO 'canon'@'localhost';

SELECT "Setting password for canon user" AS NOTE;

SET @q = CONCAT("SET PASSWORD FOR 'canon'@'localhost' = PASSWORD('",@password,"')");
PREPARE stmt FROM @q; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @password = NULL;

FLUSH PRIVILEGES;

USE canon;

SELECT "Creating tables" AS NOTE;

CREATE TABLE branch_aliases
(
    id                  int(11)             NOT NULL    AUTO_INCREMENT,
    branch_id           int(11)             NOT NULL,
    branch_alias        varchar(255)        NOT NULL,

    PRIMARY KEY (id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE awards
(
    id                  int(11)             NOT NULL    AUTO_INCREMENT,
    acronym             varchar(16)         NOT NULL,
    name                varchar(255)        NOT NULL,
    description         text                NOT NULL,
    rank                int(11)             NOT NULL,                   -- honour, plebeian, armiger, grant, patent
    precedence          int(11)             NOT NULL,
    active              tinyint(1)          NOT NULL,
    conveys             int(11)             NULL,
    branch_id           int(11)             NULL,

    PRIMARY KEY (id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE branches
(
    id                  int(11)             NOT NULL    AUTO_INCREMENT,
    branch_name         varchar(255)        NOT NULL,
    region              INT(11)             NOT NULL,                   -- oz, nz, os, earth
    physical_location   varchar(255)        NULL,
    became_branch_id    int(11)             NULL,
    url                 varchar(255)        NULL,

    PRIMARY KEY (id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE events
(
    id                  int(11)             NOT NULL    AUTO_INCREMENT,
    branch_id           int(11)             NULL,
    from_date           date                NOT NULL,
    to_date             date                NOT NULL,
    event_name          varchar(255)        NULL,
    physical_location   varchar(255)        NULL,

    PRIMARY KEY (id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE reigns
(
    id                  int(11)             NOT NULL    AUTO_INCREMENT,
    branch_id           int(11)             NOT NULL,
    short_name          varchar(255)        NOT NULL,
    one_person_id       int(11)             NULL,
    one_title           varchar(255)        NULL,
    other_person_id     int(11)             NULL,
    other_title         varchar(255)        NULL,
    won                 date                NULL,
    stepped_up          date                NULL,
    stepped_down        date                NULL,

    PRIMARY KEY (id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE branch_types
(
    id                  int(11)             NOT NULL    AUTO_INCREMENT,
    branch_id           int(11)             NOT NULL,
    branch_type         int(11)             NULL,                       -- canton,college,shire,barony,crown-principality,principality,kingdom,region,hamlet,stronghold,port,palatine-barony
    transition          date                NULL,
    parent_branch_id    int(11)             NULL,

    PRIMARY KEY (id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE person_aliases
(
    id                  int(11)             NOT NULL    AUTO_INCREMENT,
    person_id           int(11)             NOT NULL,
    sca_name            text                NOT NULL,
    is_primary          tinyint(1)          NOT NULL,
    gender              int(11)             NULL,                       -- female, male, other

    PRIMARY KEY (id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE heraldic_registrations
(
    id                  int(11)             NOT NULL    AUTO_INCREMENT,
    person_alias_id     int(11)             NOT NULL,
    registration_date   date                NULL,
    kingdom_branch_id   int(11)             NULL,
    lroa_id             int(11)             NULL,

    PRIMARY KEY (id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE op_scores
(
    score               int(11)             NOT NULL,
    person_id           int(11)             NOT NULL,
    award_id            int(11)             NULL,
    award_count         int(11)             NOT NULL,
    highest_date        date                NULL,

    PRIMARY KEY (score)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE people
(
    id                  int(11)             NOT NULL    AUTO_INCREMENT,
    mundane_name        varchar(255)        NULL,
    branch_id           int(11)             NULL,
    tracking            int(11)             NOT NULL,                   -- normal, foreign-royalty, will-refuse, banished

    PRIMARY KEY (id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE received_awards
(
    id                  int(11)             NOT NULL    AUTO_INCREMENT,
    person_id           int(11)             NOT NULL,
    award_id            int(11)             NOT NULL,
    event_id            int(11)             NULL,
    reign_id            int(11)             NULL,
    received_date       date                NULL,

    PRIMARY KEY (id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;
