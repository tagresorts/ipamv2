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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `custom_fields`
--

LOCK TABLES `custom_fields` WRITE;
/*!40000 ALTER TABLE `custom_fields` DISABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ips`
--

LOCK TABLES `ips` WRITE;
/*!40000 ALTER TABLE `ips` DISABLE KEYS */;
INSERT INTO `ips` VALUES (5,'172.16.0.1',3,'Assigned','Sonicwall Firewall','Coron IT','Tagresorts',6,NULL,'2025-02-24 13:24:07','2025-02-25 10:27:16','Firewall','Data Center',1),(6,'192.168.88.10',4,'Assigned','Sonicwall Firewall','BSI IT','BSI',6,NULL,'2025-02-24 14:14:30','2025-02-25 10:06:42','Firewall','Data Center',2);
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
INSERT INTO `user_companies` VALUES (4,1,'viewer'),(4,2,'viewer'),(4,3,'viewer'),(6,1,'viewer'),(6,2,'viewer'),(6,3,'viewer'),(7,1,'viewer'),(7,2,'viewer');
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (4,'admin','support@tagresorts.com.ph','Admin','','User','$2y$10$HY7nX.lgDBPGjxM8pL9Kp.FbFOC2US4HEIG2n.Fa4zmz3tdGH3aFS','admin','2025-02-24 07:42:44',1),(6,'rlopez','ryan.lopez@backofficesolutions.ph','Cristopher Ryan','Pasay','Lopez','$2y$10$EecHVTpmrAobufFgaLzvFee4Xr9n9cJ1PScMMAfr7V4uuteO78kZC','admin','2025-02-24 10:16:50',1),(7,'rmagalona','richard@backofficesolutions.ph','Richard','','Magalona','$2y$10$RHR6Jk2V4gGuQssNWQKJdeSyyRk5Pea/kus4esoouIJL4ec23676K','user','2025-02-25 08:30:14',1);
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

-- Dump completed on 2025-02-26 12:36:42
