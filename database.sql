-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: petcare_pbw
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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
-- Table structure for table `pet_measurements`
--

DROP TABLE IF EXISTS `pet_measurements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pet_measurements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pet_id` int(11) NOT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `weight` decimal(5,2) DEFAULT NULL,
  `length` decimal(5,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pet_id` (`pet_id`),
  CONSTRAINT `pet_measurements_ibfk_1` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pet_measurements`
--

LOCK TABLES `pet_measurements` WRITE;
/*!40000 ALTER TABLE `pet_measurements` DISABLE KEYS */;
INSERT INTO `pet_measurements` VALUES (19,26,'2025-07-15 07:00:00',20.00,100.00,NULL),(20,26,'2025-07-15 07:01:00',120.00,101.00,NULL),(21,26,'2025-07-15 07:01:00',888.00,101.00,NULL),(25,23,'2025-07-15 07:02:00',0.20,0.20,NULL),(27,25,'2025-07-15 07:02:00',0.50,0.50,NULL),(28,23,'2025-07-15 07:02:00',20.00,20.00,NULL),(29,22,'2025-07-15 07:04:00',50.00,50.00,NULL),(31,21,'2025-07-15 07:04:00',1.00,50.00,NULL),(32,20,'2025-07-15 07:04:00',2.00,80.00,NULL),(33,19,'2025-07-15 07:04:00',80.00,150.00,NULL),(34,18,'2025-07-15 07:05:00',80.00,150.00,NULL),(35,17,'2025-07-15 07:05:00',0.50,60.00,NULL);
/*!40000 ALTER TABLE `pet_measurements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pets`
--

DROP TABLE IF EXISTS `pets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `type` varchar(50) NOT NULL,
  `birth_date` date DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT 0.00,
  `length` decimal(5,2) DEFAULT 0.00,
  `gender` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `image_path` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `pets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pets`
--

LOCK TABLES `pets` WRITE;
/*!40000 ALTER TABLE `pets` DISABLE KEYS */;
INSERT INTO `pets` VALUES (17,3,'ikan koi hitam 1','Ikan','2020-08-08',0.10,0.00,'Jantan','','pet_6875fa63deda7_1752562275.webp','2025-07-15 06:51:15'),(18,3,'sapi coklat','sapi','2020-08-08',100.00,0.00,'Jantan','','pet_6875fa86d7dbb_1752562310.jpg','2025-07-15 06:51:50'),(19,3,'Sapi Hitam Putih 1','Sapi','2020-08-08',89.00,0.00,'Jantan','','pet_6875faaea4e15_1752562350.jpeg','2025-07-15 06:52:30'),(20,3,'Kucing Hitam Persia','Kucing','2020-01-08',1.00,0.00,'Betina','','pet_6875faeb907ab_1752562411.webp','2025-07-15 06:53:31'),(21,3,'Koi Putih Merah Gelap','Ikan','2020-08-08',0.20,0.00,'Jantan','','pet_6875fb2ec67f6_1752562478.jpg','2025-07-15 06:54:38'),(22,3,'Kambing Keren Jawa','Kambing','2020-08-08',10.00,0.00,'Jantan','','pet_6875fbae32ef6_1752562606.jpg','2025-07-15 06:56:46'),(23,3,'Domba mirip yang di Shaun the Sheep','Domba','0000-00-00',5.00,0.00,'Jantan','','pet_6875fbdbb4a7c_1752562651.jpg','2025-07-15 06:57:31'),(25,3,'Love Bird Keren','Burung','2020-08-08',0.20,0.00,'Jantan','','pet_6875fc532519d_1752562771.webp','2025-07-15 06:59:31'),(26,3,'Kerbau Keren','Kerbau','2020-08-08',25.00,0.00,'Jantan','','pet_6875fc73c73f3_1752562803.jpg','2025-07-15 07:00:03'),(27,3,'Murai Batu Pertamaku','Burung','2020-08-20',0.50,0.00,'Jantan','','pet_6875fddde656f_1752563165.webp','2025-07-15 07:06:05');
/*!40000 ALTER TABLE `pets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `remember_tokens`
--

DROP TABLE IF EXISTS `remember_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `remember_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_token` (`token`),
  CONSTRAINT `remember_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `remember_tokens`
--

LOCK TABLES `remember_tokens` WRITE;
/*!40000 ALTER TABLE `remember_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `remember_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `schedules`
--

DROP TABLE IF EXISTS `schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `care_type` varchar(50) NOT NULL,
  `schedule_time` datetime NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `pet_id` (`pet_id`),
  CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `schedules_ibfk_2` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schedules`
--

LOCK TABLES `schedules` WRITE;
/*!40000 ALTER TABLE `schedules` DISABLE KEYS */;
INSERT INTO `schedules` VALUES (14,3,23,'Makan','2025-07-15 15:00:00','test',1,NULL,'2025-07-15 07:15:18','2025-07-15 07:15:18'),(15,3,17,'Makan','2025-07-15 17:00:00','test',1,NULL,'2025-07-15 07:15:32','2025-07-15 07:15:32'),(16,3,26,'Lainnya','2025-07-15 17:30:00','test',1,NULL,'2025-07-15 07:15:49','2025-07-15 07:15:49'),(17,3,23,'Makan','2025-07-15 18:00:00','test',1,NULL,'2025-07-15 07:16:01','2025-07-15 07:16:01'),(18,3,25,'Makan','2025-07-16 19:00:00','',1,NULL,'2025-07-15 07:16:23','2025-07-15 07:16:23'),(19,3,17,'Olahraga','2025-07-16 12:00:00','',1,NULL,'2025-07-15 07:16:38','2025-07-15 07:16:38'),(20,3,25,'Lainnya','2025-07-14 19:00:00','',1,NULL,'2025-07-15 07:16:54','2025-07-15 07:16:54'),(21,3,18,'Checkup','2025-07-13 15:00:00','',1,NULL,'2025-07-15 07:17:14','2025-07-15 07:17:14'),(22,3,18,'Grooming','2025-07-14 14:30:00','akhirnya 1',1,NULL,'2025-07-15 07:17:47','2025-07-15 07:17:47'),(23,3,25,'Obat','2025-07-18 14:00:00','test',1,NULL,'2025-07-15 07:18:38','2025-07-15 07:18:38');
/*!40000 ALTER TABLE `schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_preferences`
--

DROP TABLE IF EXISTS `user_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_preferences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `timezone` varchar(100) DEFAULT 'Asia/Jakarta',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `user_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_preferences`
--

LOCK TABLES `user_preferences` WRITE;
/*!40000 ALTER TABLE `user_preferences` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_preferences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(64) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (3,'test','ecd71870d1963316a97e3ac3408c9835ad8cf0f3c1bc703527c30265534f75ae','test@example.com','akun-tester','2025-07-15 06:50:15','2025-07-15 06:50:26');
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

-- Dump completed on 2025-07-15 14:20:43
