-- MySQL dump 10.11
--

USE yourdatabase;		-- Edit this!

--
-- Table structure for table `queues`
--

DROP TABLE IF EXISTS `queues`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `queues` (
  `id` int(11) NOT NULL auto_increment,
  `queue_name` varchar(255) NOT NULL,
  `item_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  `date_removed` datetime default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
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
  `transcript` text,
  `status` varchar(255) default NULL,
  `type` varchar(255) NOT NULL,
  `href` varchar(1000) default NULL,
  `workflow_index` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL auto_increment,
  `project_id` int(11) NOT NULL,
  `username` varchar(255) default NULL,
  `role` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
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
  `type` varchar(255) default NULL,
  `slug` varchar(255) default NULL,
  `description` varchar(4000) default NULL,
  `owner` varchar(255) default NULL,
  `status` varchar(255) default NULL,
  `workflow` varchar(2000) default NULL,
  `whitelist` varchar(2000) default NULL,
  `guidelines` text default NULL,
  `language` varchar(255) default NULL,
  `thumbnails` varchar(400) default NULL,
  `date_started` date default NULL,
  `date_completed` date default NULL,
  `fields` varchar(4000) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `transcripts`
--

DROP TABLE IF EXISTS `transcripts`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `transcripts` (
  `id` int(11) NOT NULL auto_increment,
  `item_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `user` varchar(255) default NULL,
  `date` datetime default NULL,
  `transcript` text,
  `status` varchar(255) default NULL,
  `type` varchar(255) default NULL,
  `fields` varchar(8000) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
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
  `role` varchar(255) default NULL,
  `theme` varchar(255) default NULL,
  `prefs` varchar(4000) default NULL,
  PRIMARY KEY  (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `metadata`
--

DROP TABLE IF EXISTS `metadata`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `metadata` (
  `id` int(11) NOT NULL auto_increment,
  `table` varchar(255) NOT NULL,
  `object_id` int(11) NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` varchar(4000),
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;
