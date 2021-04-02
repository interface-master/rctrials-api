CREATE DATABASE IF NOT EXISTS `rctrials`;
USE `rctrials`;

-- DEFINE USERS TABLE
-- TO STORE ADMIN ACCOUNTS
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` VARCHAR(36) NOT NULL,
  `salt` VARCHAR(32) NOT NULL,
  `hash` VARCHAR(60) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `role` ENUM('root','admin','user') NOT NULL,
  `valid` BOOLEAN NOT NULL DEFAULT FALSE,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- DEFINE SUBJECTS TABLE
-- TO STORE TRIAL SUBJECT IDS
DROP TABLE IF EXISTS `subjects`;
CREATE TABLE `subjects` (
  `id` VARCHAR(36) NOT NULL,
  `tid` VARCHAR(4) NOT NULL,
  `group` SMALLINT DEFAULT NULL,
  `research_opt` TINYINT(1) NOT NULL DEFAULT 0,
  `f6e_opt` TINYINT(1) DEFAULT TRUE,
  `f6e_token` TEXT DEFAULT NULL,
  `created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- CREATE A TRIGGER TO BUCKET SUBJECTS INTO GROUPS
-- orders ascending by a random value multiplied by the number of subjects in that group already
-- helps skew randomness towards groups with smaller counts
DROP TRIGGER IF EXISTS `subjects_before_insert`;
DELIMITER //
CREATE TRIGGER `subjects_before_insert` BEFORE INSERT ON `subjects`
FOR EACH ROW BEGIN
  DECLARE rnd_grp INT;
  SET rnd_grp = (
    SELECT `gid`
    FROM `groups`
    WHERE `tid` = new.tid
    ORDER BY (RAND()*(SELECT COUNT(*) FROM `subjects` WHERE `tid` = new.tid AND `group`=`gid`)) ASC
    LIMIT 1);
  SET new.group = CASE
    WHEN new.research_opt = 0 THEN null
    ELSE rnd_grp
  END;
END;//
DELIMITER ;


-- DEFINE TOKENS TABLE
-- STORES OAUTH TOKENS TO VALIDATE LOGINS
-- ASSOCIATES TOKEN WITH USER ID
DROP TABLE IF EXISTS `tokens`;
CREATE TABLE `tokens` (
  `uid` VARCHAR(36) NOT NULL,
  `tid` TEXT,
  `token` TEXT,
  `expires` DATETIME NOT NULL,
  PRIMARY KEY (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- DEFINE TRIALS TABLE
-- STORES DETAILS ABOUT TRIAL DATES, USER, TIMEZONE
DROP TABLE IF EXISTS `trials`;
CREATE TABLE `trials` (
  `tid` VARCHAR(4) NOT NULL DEFAULT '',
  `uid` VARCHAR(36) NOT NULL,
  `title` VARCHAR(32) NOT NULL,
  `regopen` DATETIME NOT NULL DEFAULT '1970-01-01 00:00:01',
  `regclose` DATETIME NOT NULL DEFAULT '3000-01-01 00:00:01',
  `trialstart` DATETIME NOT NULL DEFAULT '1970-01-01 00:00:01',
  `trialend` DATETIME NOT NULL DEFAULT '3000-01-01 00:00:01',
  `trialtype` ENUM('simple') NOT NULL DEFAULT 'simple',
  `timezone` VARCHAR(32),
  `created` TIMESTAMP DEFAULT '1970-01-01 00:00:01',
  `updated` TIMESTAMP DEFAULT '1970-01-01 00:00:01' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`tid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- CREATE A TRIGGER TO GENERATE UNIQUE 4-CHAR IDS FOR TRIALS
DROP TRIGGER IF EXISTS `trials_before_insert`;
DELIMITER //
CREATE TRIGGER `trials_before_insert` BEFORE INSERT ON `trials`
FOR EACH ROW BEGIN
  DECLARE ready INT DEFAULT 0;
  DECLARE rnd_str TEXT;
  IF (new.tid IS NULL OR new.tid = '') THEN
    WHILE NOT READY DO
      SET rnd_str := LEFT( UUID(), 4 );
      IF NOT EXISTS (SELECT * FROM `trials` WHERE `tid` = rnd_str) THEN
        SET new.tid = rnd_str;
        SET ready := 1;
      END IF;
    END WHILE;
  END IF;
END;//
DELIMITER ;

-- DEFINE GROUPS TABLE
-- STORES TRIAL GROUP SIZE AND NAME
DROP TABLE IF EXISTS `groups`;
CREATE TABLE `groups` (
  `tid` VARCHAR(4) NOT NULL,
  `gid` SMALLINT NOT NULL,
  `name` VARCHAR(20) NOT NULL,
  `size` ENUM('auto','manual') NOT NULL DEFAULT 'auto',
  `size_n` SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`tid`,`gid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- DEFINE SURVEYS TABLE
-- STORES NAME AND GROUPS FOR SURVEYS
DROP TABLE IF EXISTS `surveys`;
CREATE TABLE `surveys` (
  `tid` VARCHAR(4) NOT NULL,
  `sid` SMALLINT NOT NULL,
  `name` VARCHAR(20) NOT NULL,
  `time` SMALLINT DEFAULT NULL,
  `intro` TEXT DEFAULT NULL,
  `groups` VARCHAR(20) NOT NULL,
  `pre` BOOLEAN DEFAULT FALSE,
  `during` BOOLEAN DEFAULT FALSE,
  `post` BOOLEAN DEFAULT FALSE,
  `interval` SMALLINT DEFAULT 1,
  `frequency` ENUM('days','weeks','months') DEFAULT 'days',
  PRIMARY KEY (`tid`,`sid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- CLEAR TABLES IN RIGHT ORDER TO AVOID CONSTRAINT VIOLATIONS
DROP TABLE IF EXISTS `answers`;
DROP TABLE IF EXISTS `questions`;

-- DEFINE QUESTIONS TABLE
-- STORES TRIAL, SURVEY, TEXT, AND OPTIONS
CREATE TABLE `questions` (
  `tid` VARCHAR(4) NOT NULL,
  `sid` SMALLINT NOT NULL,
  `qid` SMALLINT NOT NULL,
  `text` VARCHAR(100) NOT NULL,
  `type` ENUM('text','likert','slider','radio','check') NOT NULL DEFAULT 'text',
  `options` VARCHAR(500),
  PRIMARY KEY (`tid`,`sid`,`qid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- DEFINE ANSWERS TABLE
-- STORES TRIAL, SURVEY, QUESTION, TEXT, AND UUID
CREATE TABLE `answers` (
  `tid` VARCHAR(4) NOT NULL,
  `sid` SMALLINT NOT NULL,
  `qid` SMALLINT NOT NULL,
  `uid` VARCHAR(36) NOT NULL,
  `text` VARCHAR(100) NOT NULL,
  `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`tid`,`sid`,`qid`)
  REFERENCES questions(`tid`,`sid`,`qid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- DEFINE FIREBASE LOGS TABLE
-- STORES TIMESTAMP, UUID, GROUP (at that time), TRIAL, SURVEY,
-- last time there is a recorded answer from that user for that survey
-- and the firebase response after attempting to send the message
CREATE TABLE `f6e_logs` (
  `timestamp` TIMESTAMP NOT NULL,
  `uid` VARCHAR(36) NOT NULL,
  `group` SMALLINT NOT NULL,
  `tid` VARCHAR(4) NOT NULL,
  `sid` SMALLINT NOT NULL,
  `last_answer` DATE NOT NULL,
  `f6e_response` TEXT NOT NULL,
  PRIMARY KEY (`timestamp`,`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
