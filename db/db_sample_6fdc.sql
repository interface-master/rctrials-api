-- MySQL dump 10.13  Distrib 5.7.25, for Linux (x86_64)
--
-- Host: localhost    Database: rctrials
-- ------------------------------------------------------
-- Server version	5.7.25-google-log

--
-- Current Database: `rctrials`
--

USE `rctrials`;
SET names 'utf8';

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
INSERT INTO `trials` VALUES ('6fdc','5a56233d-6c77-11ea-85b6-42010a800005','Mehailo','2021-01-01 05:00:00','2121-01-01 05:00:00','2021-01-01 05:00:00','2121-02-13 05:00:00','simple','America/Toronto','1970-01-01 06:00:01','2021-02-15 02:09:53');
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
INSERT INTO `surveys` VALUES ('6fdc',0,'Demographics',2,'Your answers to the following questions are only used in aggregate to better understand our users. It is never shared or used to link to you personally.','[0,1]',1,0,0,1,'days'),
('6fdc',1,'Sports Motivation',2,'Why do you play sports or engage in physical activities?\n\nUsing the scale below, please indicate to what extent each of the following items corresponds to one of the reasons for which you are presently engaged in physical activities.','[0,1]',1,0,0,1,'days'),
('6fdc',2,'Wellbeing',1,'Over the last 2 weeks, how often have you been bothered by any of the following problems?','[0,1]',0,1,0,7,'days'),
('6fdc',3,'Sports Motivation',1,'Why do you play sports or engage in physical activities?\n\nUsing the scale below, please indicate to what extent each of the following items corresponds to one of the reasons for which you are presently engaged in physical activities.','[0,1]',0,0,1,1,'days'),
('6fdc',4,'App Usability',1,'Using the scale below, please indicate how was your experience using the Mehailo App?','[0,1]',0,0,1,1,'days');
UNLOCK TABLES;

--
-- Dumping data for table `questions`
--

LOCK TABLES `questions` WRITE;
INSERT INTO `questions` VALUES
('6fdc',0,0,'Which age group best describes you?','radio','< 18|18 - 19|20 - 24|25 - 29|30 - 34|35 - 39|40 - 49|50 - 59|60 - 64|65 - 69|70 - 74|75 - 79|80+|Prefer not to answer'),
('6fdc',0,1,'With which gender do you most identify?','radio','Woman|Man|Gender-fluid, non-binary, and/or Two-Spirit|Prefer not to answer'),
('6fdc',0,2,'Do you identify as Indigenous; that is First Nations (North American Indian), Métis, or Inuit?','radio','Yes – First Nations|Yes – Inuit|Yes – Métis|No|Prefer not to answer'),
('6fdc',0,3,'Do you identify as a member of a visible minority in Canada?','radio','Yes – Arab|Yes – Black|Yes – Chinese|Yes – Filipino|Yes – Japanese|Yes – Korean|Yes – Latin American|Yes – South Asian (e.g., East Indian, Pakistani, Sri Lankan, etc.)|Yes – Southeast Asian (including Vietnamese, Cambodian, Laotian, Thai; etc.)|Yes – West Asian (e.g., Iranian, Afghan, etc.)|Yes – Another visible minority group|No|Prefer not to answer'),
('6fdc',0,4,'What is your highest earned level of education?','radio','Less than a high school diploma|High school degree or equivalent|Higher diploma|Bachelor\'s degree|Master\'s degree|Doctorate|Other|Prefer not to answer'),
('6fdc',0,5,'What is your academic status?','radio','Studying Full Time|Studying Part Time|Not Studying|Prefer not to answer'),
('6fdc',0,6,'What is your employment status?','radio','Employed full-time (40+ hr/wk)|Employed part-time|Unemployed (looking)|Unemployed (not looking)|Student|Retired|Self-employed|Unable to work|Other|Prefer not to answer'),
('6fdc',0,7,'If employed, are you employed by an essential service provider?','radio','No|Yes|Prefer not to answer|N/A'),
('6fdc',0,8,'If employed, are you working from home?','radio','No|Yes|Prefer not to answer|N/A'),
('6fdc',0,9,'Which of the following best describes your marital status?','radio','Single|Married|Domestic partnership|Divorced|Widowed|Separated|Other|Prefer not to answer'),
('6fdc',0,10,'Are you Canadian?','radio','Canadian|Not Canadian'),

('6fdc',1,11,'For the pleasure I feel in living exciting experiences.','radio','Strongly Disagree [1]|Disagree [2]|Neutral [3]|Agree [4]|Strongly Agree [5]'),
('6fdc',1,12,'For the excitement I feel when I am really involved in the activity.','radio','Strongly Disagree [1]|Disagree [2]|Neutral [3]|Agree [4]|Strongly Agree [5]'),
('6fdc',1,13,'For the intense emotions I feel doing the physical activities that I like.','radio','Strongly Disagree [1]|Disagree [2]|Neutral [3]|Agree [4]|Strongly Agree [5]'),
('6fdc',1,14,'Because I like the feeling of being totally immersed in the activity.','radio','Strongly Disagree [1]|Disagree [2]|Neutral [3]|Agree [4]|Strongly Agree [5]'),
('6fdc',1,15,'For the pleasure it gives me to know more about physical exercises.','radio','Strongly Disagree [1]|Disagree [2]|Neutral [3]|Agree [4]|Strongly Agree [5]'),
('6fdc',1,16,'For the pleasure of discovering new learning techniques.','radio','Strongly Disagree [1]|Disagree [2]|Neutral [3]|Agree [4]|Strongly Agree [5]'),
('6fdc',1,17,'For the pleasure that I feel while learning skills/techniques that I have never tried before.','radio','Strongly Disagree [1]|Disagree [2]|Neutral [3]|Agree [4]|Strongly Agree [5]'),
('6fdc',1,18,'For the pleasure of discovering new performance strategies.','radio','Strongly Disagree [1]|Disagree [2]|Neutral [3]|Agree [4]|Strongly Agree [5]'),
('6fdc',1,19,'Because I feel a lot of personal satisfaction while mastering certain difficult tasks.','radio','Strongly Disagree [1]|Disagree [2]|Neutral [3]|Agree [4]|Strongly Agree [5]'),
('6fdc',1,20,'For the pleasure I feel while improving some of my weak points.','radio','Strongly Disagree [1]|Disagree [2]|Neutral [3]|Agree [4]|Strongly Agree [5]'),
('6fdc',1,21,'For the satisfaction I experience while I am perfecting my abilities.','radio','Strongly Disagree [1]|Disagree [2]|Neutral [3]|Agree [4]|Strongly Agree [5]'),
('6fdc',1,22,'For the pleasure that I feel while executing certain difficult movements.','radio','Strongly Disagree [1]|Disagree [2]|Neutral [3]|Agree [4]|Strongly Agree [5]'),
('6fdc',1,23,'I\'m interested in developing my physical fitness.','radio','Strongly Disagree [1]|Disagree [2]|Neutral [3]|Agree [4]|Strongly Agree [5]'),
('6fdc',1,24,'Outside of school and work I like to play sports.','radio','Strongly Disagree [1]|Disagree [2]|Neutral [3]|Agree [4]|Strongly Agree [5]'),
('6fdc',1,25,'Going forward I would like to join a sports club.','radio','Strongly Disagree [1]|Disagree [2]|Neutral [3]|Agree [4]|Strongly Agree [5]'),
('6fdc',1,26,'Going forward I would like to be physically active.','radio','Strongly Disagree [1]|Disagree [2]|Neutral [3]|Agree [4]|Strongly Agree [5]'),
('6fdc',1,27,'I often do physical activities in my free time.','radio','Strongly Disagree [1]|Disagree [2]|Neutral [3]|Agree [4]|Strongly Agree [5]'),

('6fdc',2,28,'Feeling nervous, anxious, or on edge.','radio','Not at all [0]|Several days [1]|More than half the days [2]|Nearly every day [3]'),
('6fdc',2,29,'Not being able to stop or control worrying.','radio','Not at all [0]|Several days [1]|More than half the days [2]|Nearly every day [3]'),
('6fdc',2,30,'Little interest or pleasure in doing things.','radio','Not at all [0]|Several days [1]|More than half the days [2]|Nearly every day [3]'),
('6fdc',2,31,'Feeling down, depressed, or hopeless.','radio','Not at all [0]|Several days [1]|More than half the days [2]|Nearly every day [3]'),

('6fdc',3,32,'I\'m interested in developing my physical fitness.','radio','Strongly Disagree [1]|Disagree [2]|Neutral [3]|Agree [4]|Strongly Agree [5]'),
('6fdc',3,33,'Outside of school and work I like to play sports.','radio','Strongly Disagree [1]|Disagree [2]|Neutral [3]|Agree [4]|Strongly Agree [5]'),
('6fdc',3,34,'Going forward I would like to join a sports club.','radio','Strongly Disagree [1]|Disagree [2]|Neutral [3]|Agree [4]|Strongly Agree [5]'),
('6fdc',3,35,'Going forward I would like to be physically active.','radio','Strongly Disagree [1]|Disagree [2]|Neutral [3]|Agree [4]|Strongly Agree [5]'),
('6fdc',3,36,'I often do physical activities in my free time.','radio','Strongly Disagree [1]|Disagree [2]|Neutral [3]|Agree [4]|Strongly Agree [5]'),

('6fdc',4,37,'I think that I would like to use this app frequently.','radio','Strongly Disagree [1]|Disagree [2]|Neutral [3]|Agree [4]|Strongly Agree [5]'),
('6fdc',4,38,'I found the app unnecessarily complex.','radio','Strongly Disagree [1]|Disagree [2]|Neutral [3]|Agree [4]|Strongly Agree [5]'),
('6fdc',4,39,'I thought the app was easy to use.','radio','Strongly Disagree [1]|Disagree [2]|Neutral [3]|Agree [4]|Strongly Agree [5]'),
('6fdc',4,40,'I think that I would need the support of a technical person to be able to use this app.','radio','Strongly Disagree [1]|Disagree [2]|Neutral [3]|Agree [4]|Strongly Agree [5]'),
('6fdc',4,41,'I found the various functions in the app were well integrated.','radio','Strongly Disagree [1]|Disagree [2]|Neutral [3]|Agree [4]|Strongly Agree [5]'),
('6fdc',4,42,'I thought there was too much inconsistency in this app.','radio','Strongly Disagree [1]|Disagree [2]|Neutral [3]|Agree [4]|Strongly Agree [5]'),
('6fdc',4,43,'I would imagine that most people would learn to use this app very quickly.','radio','Strongly Disagree [1]|Disagree [2]|Neutral [3]|Agree [4]|Strongly Agree [5]'),
('6fdc',4,44,'I found the app very cumbersome to use.','radio','Strongly Disagree [1]|Disagree [2]|Neutral [3]|Agree [4]|Strongly Agree [5]'),
('6fdc',4,45,'I felt very confident using the app.','radio','Strongly Disagree [1]|Disagree [2]|Neutral [3]|Agree [4]|Strongly Agree [5]'),
('6fdc',4,46,'I needed to learn a lot of things before I could get going with this app.','radio','Strongly Disagree [1]|Disagree [2]|Neutral [3]|Agree [4]|Strongly Agree [5]');

UNLOCK TABLES;

-- Dump completed on 2020-07-29 18:44:37
