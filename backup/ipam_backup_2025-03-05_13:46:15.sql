/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.5.27-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: ipam
-- ------------------------------------------------------
-- Server version	10.5.27-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `companies` (
  `company_id` int(11) NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) NOT NULL,
  `enrollment_options` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `description` text DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `companies`
--

LOCK TABLES `companies` WRITE;
/*!40000 ALTER TABLE `companies` DISABLE KEYS */;
INSERT INTO `companies` VALUES (1,'Tagresorts','','2025-02-24 09:06:06','Tagresorts Inc.','Active','2025-02-24 14:40:14'),(2,'Backoffice Solutions Inc','','2025-02-24 14:07:30','Backoffice Solutions Inc','Active','2025-02-24 14:07:30'),(3,'Logisprint','','2025-02-24 14:40:46','Logisprint Inc aka Viahero','Active','2025-02-24 15:03:34');
/*!40000 ALTER TABLE `companies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `custom_fields`
--

DROP TABLE IF EXISTS `custom_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `custom_fields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_id` int(11) DEFAULT NULL,
  `field_name` varchar(50) DEFAULT NULL,
  `field_value` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ip_id` (`ip_id`),
  CONSTRAINT `custom_fields_ibfk_1` FOREIGN KEY (`ip_id`) REFERENCES `ips` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `custom_fields`
--

LOCK TABLES `custom_fields` WRITE;
/*!40000 ALTER TABLE `custom_fields` DISABLE KEYS */;
INSERT INTO `custom_fields` VALUES (3,5,'DNS','DNS');
/*!40000 ALTER TABLE `custom_fields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `history_log`
--

DROP TABLE IF EXISTS `history_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `history_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ip_id` (`ip_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `history_log_ibfk_1` FOREIGN KEY (`ip_id`) REFERENCES `ips` (`id`),
  CONSTRAINT `history_log_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `history_log`
--

LOCK TABLES `history_log` WRITE;
/*!40000 ALTER TABLE `history_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `history_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ip_addresses`
--

DROP TABLE IF EXISTS `ip_addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ip_addresses` (
  `ip_id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `subnet_id` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `assigned_to` varchar(100) DEFAULT NULL,
  `owner` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` varchar(50) DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `company_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`ip_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ip_addresses`
--

LOCK TABLES `ip_addresses` WRITE;
/*!40000 ALTER TABLE `ip_addresses` DISABLE KEYS */;
/*!40000 ALTER TABLE `ip_addresses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ips`
--

DROP TABLE IF EXISTS `ips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ips` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `subnet_id` int(11) DEFAULT NULL,
  `status` enum('Available','Reserved','Assigned','Expired') DEFAULT 'Available',
  `description` text DEFAULT NULL,
  `assigned_to` varchar(255) DEFAULT NULL,
  `owner` varchar(100) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `expires_at` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `type` varchar(100) NOT NULL DEFAULT 'Unknown',
  `location` varchar(255) NOT NULL DEFAULT 'Not Specified',
  `company_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip_address` (`ip_address`),
  KEY `subnet_id` (`subnet_id`),
  KEY `fk_ips_company` (`company_id`),
  CONSTRAINT `fk_ips_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`) ON DELETE CASCADE,
  CONSTRAINT `ips_ibfk_1` FOREIGN KEY (`subnet_id`) REFERENCES `subnets` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ips`
--

LOCK TABLES `ips` WRITE;
/*!40000 ALTER TABLE `ips` DISABLE KEYS */;
INSERT INTO `ips` VALUES (5,'172.16.0.1',3,'Assigned','Sonicwall Firewall','Coron IT','Tagresorts',6,NULL,'2025-02-24 13:24:07','2025-02-27 09:06:44','Firewall','Tag - Data Center',1),(6,'192.168.88.10',4,'Assigned','Sonicwall Firewall','BSI IT','BSI',6,NULL,'2025-02-24 14:14:30','2025-03-05 05:19:38','Firewall','BSI - 1103',2),(7,'172.16.0.5',3,'Assigned','IDF1-Switch','Coron IT','Tagresorts Coron',6,NULL,'2025-02-27 08:18:33','2025-02-27 08:18:33','Switch','Not Specified',1),(8,'172.16.0.10',3,'Assigned','IDF1-Switch2','Coron IT','Tagresorts Coron',6,NULL,'2025-02-27 08:18:33','2025-02-27 08:18:33','Switch','Not Specified',1),(9,'172.16.0.6',3,'Assigned','IDF2-Distri-Switch','Coron IT','Tagresorts Coron',6,NULL,'2025-02-27 08:18:33','2025-02-27 08:18:33','Switch','Not Specified',1),(10,'172.16.0.7',3,'Assigned','IDF3-Distri-Switch','Coron IT','Tagresorts Coron',6,NULL,'2025-02-27 08:18:33','2025-02-27 08:18:33','Switch','Not Specified',1),(11,'172.16.0.8',3,'Assigned','IDF4-Switch','Coron IT','Tagresorts Coron',6,NULL,'2025-02-27 08:18:33','2025-02-27 08:18:33','Switch','Not Specified',1),(12,'172.16.0.13',3,'Assigned','IDF4-Switch2','Coron IT','Tagresorts Coron',6,NULL,'2025-02-27 08:18:33','2025-02-27 08:18:33','Switch','Not Specified',1),(13,'172.16.0.9',3,'Assigned','IDF5-Switch','Coron IT','Tagresorts Coron',6,NULL,'2025-02-27 08:18:33','2025-02-27 08:18:33','Switch','Not Specified',1),(14,'172.16.0.3',3,'Assigned','MDF-Switch1','Coron IT','Tagresorts Coron',6,NULL,'2025-02-27 08:18:33','2025-02-27 08:18:33','Switch','Not Specified',1),(15,'172.16.0.4',3,'Assigned','MDF-Switch2','Coron IT','Tagresorts Coron',6,NULL,'2025-02-27 08:18:33','2025-02-27 08:18:33','Switch','Not Specified',1),(16,'172.16.0.81',3,'Assigned','Synology','Coron IT','Tagresorts Coron',6,NULL,'2025-02-27 08:18:33','2025-02-27 08:18:33','NAS','Not Specified',1),(17,'172.16.0.46',3,'Assigned','OpenVPN Coron','Coron IT','Tagresorts Coron',6,NULL,'2025-02-27 08:18:33','2025-02-27 08:18:33','VM Instance','Data Center',1),(18,'172.16.0.57',3,'Assigned','OSTicket Server','Coron IT','Tagresorts Coron',6,NULL,'2025-02-27 08:18:33','2025-02-27 08:18:33','VM Instance','Data Center',1),(19,'172.16.0.49',3,'Assigned','SnipeIT Server','Coron IT','Tagresorts Coron',6,NULL,'2025-02-27 08:18:33','2025-02-27 08:18:33','VM Instance','Data Center',1),(20,'172.16.0.48',3,'Assigned','Wordpress-Staging','Coron IT','Tagresorts Coron',6,NULL,'2025-02-27 08:18:33','2025-02-27 08:18:33','VM Instance','Data Center',1),(21,'172.16.0.23',3,'Assigned','CAPS','Coron IT','Tagresorts Coron',6,NULL,'2025-02-27 08:18:33','2025-02-27 08:18:33','VM Instance','Data Center',1),(22,'172.16.0.25',3,'Assigned','Harle','Coron IT','Tagresorts Coron',6,NULL,'2025-02-27 08:18:33','2025-02-27 08:18:33','VM Instance','Data Center',1),(23,'172.16.0.22',3,'Assigned','IFC','Coron IT','Tagresorts Coron',6,NULL,'2025-02-27 08:18:33','2025-02-27 09:10:28','Server','Tag - Data Center',1),(24,'172.16.0.20',3,'Assigned','Operaserver','Coron IT','Tagresorts Coron',6,NULL,'2025-02-27 08:18:33','2025-02-27 09:09:47','Server','Tag - Data Center',1),(25,'172.16.0.24',3,'Assigned','OXI Server','Coron IT','Tagresorts Coron',6,NULL,'2025-02-27 08:18:33','2025-02-27 08:18:33','Server','Data Center',1),(26,'172.16.0.21',3,'Assigned','Simphony','Coron IT','Tagresorts Coron',6,NULL,'2025-02-27 08:18:33','2025-02-27 09:06:26','Server','Tag - Data Center',1);
/*!40000 ALTER TABLE `ips` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subnets`
--

DROP TABLE IF EXISTS `subnets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subnets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subnet` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `vlan_id` varchar(20) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `company_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `fk_subnets_company` (`company_id`),
  CONSTRAINT `fk_subnets_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`) ON DELETE CASCADE,
  CONSTRAINT `subnets_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subnets`
--

LOCK TABLES `subnets` WRITE;
/*!40000 ALTER TABLE `subnets` DISABLE KEYS */;
INSERT INTO `subnets` VALUES (3,'172.16.0.0/24','Management VLAN','1',6,1),(4,'192.168.88.0/23','BSI Native VLAN','1',6,1),(6,'172.16.1.0/24','AP VLAN','201',6,1);
/*!40000 ALTER TABLE `subnets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_companies`
--

DROP TABLE IF EXISTS `user_companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_companies` (
  `user_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `role` enum('admin','editor','viewer') NOT NULL DEFAULT 'viewer',
  PRIMARY KEY (`user_id`,`company_id`),
  KEY `fk_user_companies_company` (`company_id`),
  CONSTRAINT `fk_user_companies_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_companies_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_companies`
--

LOCK TABLES `user_companies` WRITE;
/*!40000 ALTER TABLE `user_companies` DISABLE KEYS */;
INSERT INTO `user_companies` VALUES (4,1,'viewer'),(4,2,'viewer'),(4,3,'viewer'),(6,1,'viewer'),(6,2,'viewer'),(6,3,'viewer'),(7,1,'viewer'),(7,2,'viewer'),(8,1,'viewer'),(8,2,'viewer'),(8,3,'viewer');
/*!40000 ALTER TABLE `user_companies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `role` enum('admin','user','guest') DEFAULT 'guest',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (4,'admin','support@tagresorts.com.ph','Admin','','User','$2y$10$HY7nX.lgDBPGjxM8pL9Kp.FbFOC2US4HEIG2n.Fa4zmz3tdGH3aFS','admin','2025-02-24 07:42:44',1),(6,'rlopez','ryan.lopez@backofficesolutions.ph','Ryan','Pasay','Lopez','$2y$10$EecHVTpmrAobufFgaLzvFee4Xr9n9cJ1PScMMAfr7V4uuteO78kZC','admin','2025-02-24 10:16:50',1),(7,'rmagalona','richard@backofficesolutions.ph','Richard','','Magalona','$2y$10$HIsDx19A6tnHGH23Y8K7EOoUvUQfDohmSTM9fHcHzxGU8eYXCzsPG','user','2025-02-25 08:30:14',1),(8,'ryan','noname@noname.com','guest','','guest','$2y$10$/AUG.8stLxLhCYbspnzwm.NvZhvoqo38JpTtqwiyjtJp9ZbdSAfNq','guest','2025-02-27 01:30:37',1);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-03-05 13:46:15
