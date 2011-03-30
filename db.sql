-- MySQL dump 10.11
--

USE yourdatabase;		-- Edit this!

--
-- Table structure for table `assignments`
--

DROP TABLE IF EXISTS `assignments`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `assignments` (
  `id` int(11) NOT NULL auto_increment,
  `username` varchar(255) NOT NULL,
  `item_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `deadline` datetime NOT NULL,
  `date_assigned` datetime NOT NULL,
  `date_completed` datetime default NULL,
  `date_reviewed` datetime default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1655 DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `items`
--

DROP TABLE IF EXISTS `items`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `items` (
  `id` int(11) NOT NULL auto_increment,
  `project_id` int(11) NOT NULL,
  `title` varchar(255) default NULL,
  `itemtext` text,
  `status` varchar(255) default NULL,
  `type` varchar(255) NOT NULL,
  `href` varchar(1000) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3393 DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `membership`
--

DROP TABLE IF EXISTS `membership`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `membership` (
  `id` int(11) NOT NULL auto_increment,
  `project_id` int(11) NOT NULL,
  `username` varchar(255) default NULL,
  `role` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=103 DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `projects` (
  `id` int(11) NOT NULL auto_increment,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) default NULL,
  `description` varchar(4000) default NULL,
  `owner` varchar(255) default NULL,
  `status` varchar(255) default NULL,
  `guidelines` text,
  `deadline_days` int(11) default NULL,
  `num_proofs` int(11) default NULL,
  `author` varchar(255) default NULL,
  `language` varchar(255) default NULL,
  `thumbnails` varchar(400) default NULL,
  `date_started` date default NULL,
  `date_completed` date default NULL,
  `date_posted` date default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=42 DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `texts`
--

DROP TABLE IF EXISTS `texts`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `texts` (
  `id` int(11) NOT NULL auto_increment,
  `project_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `user` varchar(255) default NULL,
  `date` datetime default NULL,
  `itemtext` text,
  `status` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1651 DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `users` (
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(255) default NULL,
  `email` varchar(255) default NULL,
  `score` int(11) default NULL,
  `status` varchar(50) default NULL,
  `hash` varchar(32) default NULL,
  `signup_date` date default NULL,
  `last_login` datetime default NULL,
  `admin` int(4) default NULL,
  `theme` varchar(255) default NULL,
  PRIMARY KEY  (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;
