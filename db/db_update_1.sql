alter table `subjects` add column `f6e_token` text default null;

alter table `groups` modify column `name` varchar(255) not null;
