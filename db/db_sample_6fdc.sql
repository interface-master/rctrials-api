-- MySQL dump 10.13  Distrib 5.7.25, for Linux (x86_64)
--
-- Host: localhost    Database: rctrials
-- ------------------------------------------------------
-- Server version	5.7.25-google-log

--
-- Current Database: `rctrials`
--

USE `rctrials`;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
INSERT INTO `users` VALUES ('5a56233d-6c77-11ea-85b6-42010a800005','abc','$2y$10$AbCdEfGhIjKlMnOpQrStUvWxYz0123045607890aBcDeFgHiJkLmN','interface.master@gmail.com','Reece Urcher','admin',1);
UNLOCK TABLES;

--
-- Dumping data for table `trials`
--

LOCK TABLES `trials` WRITE;
INSERT INTO `trials` VALUES ('6fdc','5a56233d-6c77-11ea-85b6-42010a800005','MEHAILO Trial Dev','2020-01-01 05:00:00','2120-01-01 05:00:00','2020-01-01 05:00:00','2120-01-01 05:00:00','simple','America/Toronto','1970-01-01 00:00:01','2020-07-02 10:13:55');
UNLOCK TABLES;

--
-- Dumping data for table `groups`
--

LOCK TABLES `groups` WRITE;
/*!40000 ALTER TABLE `groups` DISABLE KEYS */;
INSERT INTO `groups` VALUES ('6fdc',0,'Control','auto',0),('6fdc',1,'Experiment','auto',0);
/*!40000 ALTER TABLE `groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `surveys`
--

LOCK TABLES `surveys` WRITE;
INSERT INTO `surveys` VALUES ('6fdc',0,'Demographics','Your answers to the following questions is only used in aggregate to better understand our users. It is never shared or used to link to you personally.','[0,1]',1,0,0,1,'days'),('6fdc',1,'PHQ-4','Over the last 2 weeks, how often have you been bothered by any of the following problems?','[0,1]',0,1,0,7,'days');
UNLOCK TABLES;

--
-- Dumping data for table `questions`
--

LOCK TABLES `questions` WRITE;
INSERT INTO `questions` VALUES
('6fdc',0,1,'Which age group best describes you?','radio','Under 18 | 18-19 | 20-24 | 25-29 | 30-34 | 35-39 | 40-49 | 50-59 | 60-64 | 65-69 | 70-74 | 75-79 | 80+ | Prefer not to answer'),
('6fdc',0,2,'With which gender do you most identify?','radio','Woman | Man | Gender-fluid, non-binary, and/or Two-Spirit | Prefer not to answer'),
('6fdc',0,3,'Do you identify as Indigenous; that is First Nations (North American Indian), Métis, or Inuit?','radio','Yes - First Nations | Yes - Inuit | Yes - Métis | No | Prefer not to answer'),
('6fdc',0,4,'Do you identify as a member of a visible minority?','radio','Yes - Arab | Yes - Black | Yes - Chinese | Yes - Filipino | Yes - Japanese | Yes - Korean | Yes - Latin American | Yes - South Asian (e.g. East Indian, Pakistani, Sri Lankan, etc.) | Yes - Southeast Asian (including Vietnamese, Cambodian, Laotian, Thai, etc.) | Yes - West Asian (e.g. Iranian, Afghan, etc.) | Yes - Another visible minority group | No | Prefer not to answer'),
('6fdc',0,5,'What is your highest earned level of education?','radio','Less than a high school diploma | High school degree or equivalent | Higher diploma | Bachelor\'s degree | Master\'s degree | Doctorate | Other | Prefer not to answer'),
('6fdc',0,6,'What is your current academic status?','radio','Studying Full Time | Studying Part Time | Not Studying | Prefer not to answer'),
('6fdc',0,7,'What is your employment status?','radio','Employed full-time (40+ hr/wk) | Employed part-time | Unemployed (looking) | Unemployed (not looking) | Student | Retired | Self-employed | Unable to work | Other | Prefer not to answer'),
('6fdc',0,8,'Which of the following best describes your marital status?','radio','Single | Married | Domestic partnership | Divorced | Widowed | Separated | Other | Prefer not to say'),

('6fdc',1,20,'Feeling nervous, anxious, or on edge','likert','Not at all [0] | Several days [1] | More than half the days [2] | Nearly every day [3]'),
('6fdc',1,21,'Not being able to stop or control worrying','likert','Not at all [0] | Several days [1] | More than half the days [2] | Nearly every day [3]'),
('6fdc',1,22,'Little interest or pleasure in doing things','likert','Not at all [0] | Several days [1] | More than half the days [2] | Nearly every day [3]'),
('6fdc',1,23,'Feeling down, depressed, or hopeless','likert','Not at all [0] | Several days [1] | More than half the days [2] | Nearly every day [3]');
UNLOCK TABLES;

-- Dump completed on 2020-07-29 18:44:37
