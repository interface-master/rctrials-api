DROP TABLE IF EXISTS `users`;

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

-- INSERT INTO `users` (`id`,`salt`,`hash`,`email`,`pass`,`name`,`role`) VALUES
-- 	( UUID(), '..salt..', '..hash..', 'interface.master@gmail.com', 'abc', 'Interface Master', 'root' ),
-- 	( UUID(), '..salt..', '..hash..', 'john@smith.ca', 'Passw0rd', 'John Smith', 'admin' );

CREATE TABLE `tokens` (
	`uid` VARCHAR(36) NOT NULL,
	`token` TEXT NOT NULL,
	`expires` DATETIME NOT NULL,
	PRIMARY KEY (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
