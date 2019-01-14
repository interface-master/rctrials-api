DROP TABLE IF EXISTS `users`;

-- DEFINE USERS TABLE
-- TO STORE ADMIN ACCOUNTS
CREATE TABLE `users` (
	`id` VARCHAR(36) NOT NULL,
	`salt` VARCHAR(32) NOT NULL,
	`hash` VARCHAR(64) NOT NULL,
	`email` VARCHAR(100) NOT NULL,
	`pass` VARCHAR(100) NOT NULL,
	`name` VARCHAR(100) NOT NULL,
	`role` ENUM('root','admin','user') NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- DEFINE SUBJECTS TABLE
-- TO STORE TRIAL SUBJECT IDS
CREATE TABLE `subjects` (
	`id` VARCHAR(36) NOT NULL,
	`tid` VARCHAR(4) NOT NULL,
	`group` SMALLINT DEFAULT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- CREATE A STORED PROCEDURE THAT WILL BUCKET SUBJECTS INTO GROUPS
DELIMITER //
CREATE PROCEDURE bucket_subjects_into_groups(IN in_tid VARCHAR(4))
	BEGIN
	DECLARE done INT DEFAULT 0;
	DECLARE current_subject VARCHAR(36);
	-- counter to loop over available groups
	DECLARE counter INT DEFAULT 0;
	DECLARE group_count INT DEFAULT 0;
	DECLARE current_group INT DEFAULT NULL;
	-- declare cursor for subjects in this trial
	DEClARE subject_cursor CURSOR FOR SELECT `id` FROM `subjects` WHERE `tid` = in_tid ORDER BY RAND();
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

	SET group_count = (SELECT COUNT(*) AS `count` FROM `groups` WHERE `tid` = in_tid);

	-- open the cursor to iterate over all subjects
	OPEN subject_cursor;
	assign_group: LOOP

	FETCH subject_cursor INTO current_subject;

	IF done THEN
	LEAVE assign_group;
	END IF;

	-- assign group
	SET current_group = (SELECT `gid` FROM `groups` WHERE `tid` = in_tid ORDER BY `gid` LIMIT 1 OFFSET counter);
	UPDATE `subjects` SET `group` = current_group WHERE `id` = current_subject;

	-- incremet loop counter
	SET counter = counter + 1;
	IF counter >= group_count THEN
	SET counter = 0;
	END IF;

	END LOOP assign_group;
	CLOSE subject_cursor;
END;//
DELIMITER ;


-- DEFINE TOKENS TABLE
-- STORES OAUTH TOKENS TO VALIDATE LOGINS
-- ASSOCIATES TOKEN WITH USER ID
CREATE TABLE `tokens` (
	`uid` VARCHAR(36) NOT NULL,
	`tid` TEXT,
	`token` TEXT,
	`expires` DATETIME NOT NULL,
	PRIMARY KEY (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- DEFINE TRIALS TABLE
-- STORES DETAILS ABOUT TRIAL DATES, USER, TIMEZONE
CREATE TABLE `trials` (
	`tid` VARCHAR(4) NOT NULL,
	`uid` VARCHAR(36) NOT NULL,
	`title` VARCHAR(32) NOT NULL,
	`regopen` DATETIME NOT NULL,
	`regclose` DATETIME NOT NULL,
	`trialstart` DATETIME NOT NULL,
	`trialend` DATETIME NOT NULL,
	`trialtype` ENUM('simple') NOT NULL,
	`timezone` VARCHAR(32),
	`created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	`updated` TIMESTAMP DEFAULT 0 ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`tid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- CREATE A TRIGGER TO GENERATE UNIQUE 4-CHAR IDS FOR TRIALS
DELIMITER //
CREATE TRIGGER `trials_before_insert` BEFORE INSERT ON `trials`
FOR EACH ROW BEGIN
  DECLARE ready INT DEFAULT 0;
  DECLARE rnd_str TEXT;
  WHILE NOT READY DO
    SET rnd_str := LEFT( UUID(), 4 );
    IF NOT EXISTS (SELECT * FROM `trials` WHERE `tid` = rnd_str) THEN
      SET new.tid = rnd_str;
      SET ready := 1;
    END IF;
  END WHILE;
END;//
DELIMITER ;

-- DEFINE GROUPS TABLE
-- STORES TRIAL GROUP SIZE AND NAME
CREATE TABLE `groups` (
	`tid` VARCHAR(4) NOT NULL,
	`gid` SMALLINT NOT NULL,
	`name` VARCHAR(20) NOT NULL,
	`size` ENUM('auto','manual') NOT NULL DEFAULT 'auto',
	`size_n` SMALLINT,
	PRIMARY KEY (`tid`,`gid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- DEFINE SURVEYS TABLE
-- STORES NAME AND GROUPS FOR SURVEYS
CREATE TABLE `surveys` (
	`tid` VARCHAR(4) NOT NULL,
	`sid` SMALLINT NOT NULL,
	`name` VARCHAR(20) NOT NULL,
	`groups` VARCHAR(20) NOT NULL,
	PRIMARY KEY (`tid`,`sid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- DEFINE QUESTIONS TABLE
-- STORES TRIAL, SURVEY, TEXT, AND OPTIONS
CREATE TABLE `questions` (
	`tid` VARCHAR(4) NOT NULL,
	`sid` SMALLINT NOT NULL,
	`qid` SMALLINT NOT NULL,
	`text` VARCHAR(100) NOT NULL,
	`type` ENUM('text','mc') NOT NULL DEFAULT 'text',
	`options` VARCHAR(200),
	PRIMARY KEY (`tid`,`sid`,`qid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
