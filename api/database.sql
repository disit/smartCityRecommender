-- MySQL dump 10.13  Distrib 5.6.26, for Linux (x86_64)
--
-- Host: localhost    Database: recommender
-- ------------------------------------------------------
-- Server version	5.6.26

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `AccessLog`
--

DROP TABLE IF EXISTS `AccessLog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `AccessLog` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `mode` varchar(45) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `userAgent` varchar(255) DEFAULT NULL,
  `uid` varchar(255) DEFAULT NULL,
  `serviceUri` varchar(255) DEFAULT NULL,
  `selection` varchar(255) DEFAULT NULL,
  `categories` text,
  `maxResults` varchar(255) DEFAULT NULL,
  `maxDistance` varchar(255) DEFAULT NULL,
  `text` varchar(255) DEFAULT NULL,
  `queryId` varchar(45) DEFAULT NULL,
  `format` varchar(45) DEFAULT NULL,
  `email` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `timestamp` (`timestamp`),
  KEY `mode` (`mode`),
  KEY `ip` (`ip`),
  KEY `uid` (`uid`)
) ENGINE=FEDERATED DEFAULT CHARSET=latin1 CONNECTION='mysql://root:ubuntu@192.168.0.72:3306/ServiceMap/AccessLog';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `assessment`
--

DROP TABLE IF EXISTS `assessment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assessment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` varchar(255) DEFAULT NULL,
  `serviceURI` varchar(255) DEFAULT NULL,
  `genericID` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `vote` int(1) DEFAULT NULL,
  `date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=147 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `assessment_items`
--

DROP TABLE IF EXISTS `assessment_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assessment_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_assessment_items_item` (`item`)
) ENGINE=InnoDB AUTO_INCREMENT=134282 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `assessment_new`
--

DROP TABLE IF EXISTS `assessment_new`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assessment_new` (
  `user_id` bigint(20) NOT NULL,
  `item_id` bigint(20) NOT NULL,
  `serviceURI` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `preference` int(11) NOT NULL DEFAULT '0',
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  PRIMARY KEY (`user_id`,`item_id`),
  KEY `user_id` (`user_id`),
  KEY `item_id` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `folder` varchar(255) DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `macroclass` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_UNIQUE` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=1563 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `categories_groups`
--

DROP TABLE IF EXISTS `categories_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(255) DEFAULT NULL,
  `group` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1027 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `categories_hours`
--

DROP TABLE IF EXISTS `categories_hours`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories_hours` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `macroclass` varchar(255) DEFAULT NULL,
  `opening1` time DEFAULT NULL,
  `closing1` time DEFAULT NULL,
  `opening2` time DEFAULT NULL,
  `closing2` time DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clustered_trajectories`
--

DROP TABLE IF EXISTS `clustered_trajectories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clustered_trajectories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cluster_id` varchar(45) DEFAULT NULL,
  `cluster_size` int(11) DEFAULT NULL,
  `trajectory` longtext,
  `profile` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_clustered_trajectories_profile` (`profile`)
) ENGINE=InnoDB AUTO_INCREMENT=689816 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dislike`
--

DROP TABLE IF EXISTS `dislike`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dislike` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` varchar(255) DEFAULT NULL,
  `dislikedSubclass` varchar(255) DEFAULT NULL,
  `dislikedGroup` varchar(255) DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_dislike_user_dislikedGroup` (`user`,`dislikedGroup`),
  UNIQUE KEY `idx_dislike_user_dislikedSubclass` (`user`,`dislikedSubclass`)
) ENGINE=InnoDB AUTO_INCREMENT=514 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `general_stats`
--

DROP TABLE IF EXISTS `general_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `general_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recs_24_h` int(11) DEFAULT NULL,
  `recs_7_days` int(11) DEFAULT NULL,
  `recs_per_hour` double DEFAULT NULL,
  `active_users_24_h` int(11) DEFAULT NULL,
  `active_users_7_days` int(11) DEFAULT NULL,
  `recs_per_user_24_h` double DEFAULT NULL,
  `recs_per_user_7_days` double DEFAULT NULL,
  `views_after_recs_over_recs_24_h` double DEFAULT NULL,
  `views_after_recs_over_recs_7_days` double DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=198 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `groups`
--

DROP TABLE IF EXISTS `groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group` varchar(45) DEFAULT NULL,
  `all` int(11) DEFAULT NULL,
  `citizen` int(11) DEFAULT NULL,
  `commuter` int(11) DEFAULT NULL,
  `student` int(11) DEFAULT NULL,
  `tourist` int(11) DEFAULT NULL,
  `disabled` int(11) DEFAULT NULL,
  `operator` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `groups_lang`
--

DROP TABLE IF EXISTS `groups_lang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups_lang` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `en` varchar(45) DEFAULT NULL,
  `it` varchar(45) DEFAULT NULL,
  `es` varchar(45) DEFAULT NULL,
  `de` varchar(45) DEFAULT NULL,
  `fr` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `groups_settings`
--

DROP TABLE IF EXISTS `groups_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `all` text,
  `citizen` text,
  `commuter` text,
  `student` text,
  `tourist` text,
  `operator` text,
  `disabled` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `preferences`
--

DROP TABLE IF EXISTS `preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `preferences` (
  `user_id` bigint(20) NOT NULL,
  `item_id` bigint(20) NOT NULL,
  `preference` float NOT NULL DEFAULT '0',
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  PRIMARY KEY (`user_id`,`item_id`),
  KEY `user_id` (`user_id`),
  KEY `item_id` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `profile_settings`
--

DROP TABLE IF EXISTS `profile_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `profile_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) DEFAULT NULL,
  `max_recommendations_groups` int(11) DEFAULT NULL,
  `max_recommendations_per_day` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `recommendations`
--

DROP TABLE IF EXISTS `recommendations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recommendations` (
  `user_id` int(11) DEFAULT NULL,
  `serviceURI` varchar(255) DEFAULT NULL,
  `name` longtext,
  `address` longtext,
  `civic` varchar(45) DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `idx_recommendations_user_id_item_id` (`user_id`,`serviceURI`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `recommendations_log`
--

DROP TABLE IF EXISTS `recommendations_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recommendations_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` varchar(255) DEFAULT NULL,
  `profile` varchar(45) DEFAULT NULL,
  `recommendations` longtext,
  `nrecommendations` int(11) DEFAULT NULL,
  `nrecommendations_weather` int(11) DEFAULT NULL,
  `nrecommendations_total` int(11) DEFAULT NULL,
  `distance` double DEFAULT NULL,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  `sparql` longtext,
  `dislikedSubclasses` text,
  `dislikedGroups` text,
  `requestedGroup` varchar(255) DEFAULT NULL,
  `mode` varchar(45) DEFAULT NULL,
  `appID` varchar(255) DEFAULT NULL,
  `version` varchar(255) DEFAULT NULL,
  `language` varchar(255) DEFAULT NULL,
  `uid2` varchar(255) DEFAULT NULL,
  `aroundme` int(1) DEFAULT NULL,
  `svdEnabled` int(1) DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `init_time` int(11) DEFAULT NULL,
  `loadSettings_time` int(11) DEFAULT NULL,
  `loadGroups_time` int(11) DEFAULT NULL,
  `loadGroupsLangs_time` int(11) DEFAULT NULL,
  `setUserPreferences_time` int(11) DEFAULT NULL,
  `refreshRecommender_time` int(11) DEFAULT NULL,
  `getRecommendations_time` int(11) DEFAULT NULL,
  `updateUserProfile_time` int(11) DEFAULT NULL,
  `getRecommendationsJSON_time` int(11) DEFAULT NULL,
  `total_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_recommendations_log_user_timestamp` (`user`,`timestamp`),
  KEY `idx_recommendations_log_timestamp_user` (`timestamp`,`user`),
  KEY `idx_recommendations_log_timestamp` (`timestamp`),
  KEY `idx_recommendations_log_timestamp_user_nrecommendations_total` (`timestamp`,`user`,`nrecommendations_total`),
  KEY `idx_recommendations_log_mode_user` (`mode`,`user`)
) ENGINE=InnoDB AUTO_INCREMENT=593323 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `recommendations_stats`
--

DROP TABLE IF EXISTS `recommendations_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recommendations_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` varchar(255) DEFAULT NULL,
  `serviceUri` varchar(255) DEFAULT NULL,
  `macroclass` varchar(255) DEFAULT NULL,
  `subclass` varchar(255) DEFAULT NULL,
  `recommendedAt` timestamp NULL DEFAULT NULL,
  `viewedAt` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_recommendations_stats_user_serviceUri_recommendedAt_viewedAt` (`user`,`serviceUri`,`recommendedAt`,`viewedAt`)
) ENGINE=InnoDB AUTO_INCREMENT=517206 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `recommendations_tweets`
--

DROP TABLE IF EXISTS `recommendations_tweets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recommendations_tweets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` varchar(255) DEFAULT NULL,
  `twitterId` varchar(255) DEFAULT NULL,
  `group` varchar(255) DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_recommendations_tweets_user_twitterId` (`user`,`twitterId`)
) ENGINE=InnoDB AUTO_INCREMENT=5174398 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `service_category_menus`
--

DROP TABLE IF EXISTS `service_category_menus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service_category_menus` (
  `id` int(11) NOT NULL,
  `SubClass` varchar(255) DEFAULT NULL,
  `MacroClass` varchar(255) DEFAULT NULL,
  `student` int(11) DEFAULT '0',
  `commuter` int(11) DEFAULT '0',
  `tourist` int(11) DEFAULT '0',
  `citizen` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service` varchar(255) DEFAULT NULL,
  `subclass` varchar(255) DEFAULT NULL,
  `macroclass` varchar(255) DEFAULT NULL,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `service_UNIQUE` (`service`)
) ENGINE=InnoDB AUTO_INCREMENT=76914 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `text` varchar(255) DEFAULT NULL,
  `type` varchar(45) DEFAULT NULL,
  `value` varchar(255) DEFAULT NULL,
  `comment` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `trajectories`
--

DROP TABLE IF EXISTS `trajectories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trajectories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trajectory_id` int(11) NOT NULL,
  `cluster_id` int(11) DEFAULT NULL,
  `trajectory` longtext,
  `profile` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_trajectories_profile` (`profile`)
) ENGINE=InnoDB AUTO_INCREMENT=2743176 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tweets`
--

DROP TABLE IF EXISTS `tweets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tweets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `profile` varchar(255) DEFAULT NULL,
  `tweets` text,
  `category` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_tweets_profile_category` (`profile`,`category`)
) ENGINE=InnoDB AUTO_INCREMENT=491500 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tweets_log`
--

DROP TABLE IF EXISTS `tweets_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tweets_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` varchar(255) DEFAULT NULL,
  `twitterId` varchar(255) DEFAULT NULL,
  `group` varchar(255) DEFAULT NULL,
  `viewedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=823 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` varchar(255) DEFAULT NULL,
  `profile` varchar(45) DEFAULT NULL,
  `userAgent` text,
  `label` varchar(255) DEFAULT NULL,
  `assessor` int(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_UNIQUE` (`user`)
) ENGINE=InnoDB AUTO_INCREMENT=99666434 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users_stats`
--

DROP TABLE IF EXISTS `users_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` varchar(255) DEFAULT NULL,
  `nrecommendations` int(11) DEFAULT NULL,
  `nviews` int(11) DEFAULT NULL,
  `recommendations` longtext,
  `views` longtext,
  `recommendedAt` timestamp NULL DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2017-02-24 14:37:57
