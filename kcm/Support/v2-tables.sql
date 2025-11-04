/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.11-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: KAMS
-- ------------------------------------------------------
-- Server version	10.11.11-MariaDB-0ubuntu0.24.04.2

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
-- Current Database: `KAMS`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `KAMS` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `KAMS`;

--
-- Table structure for table `kamsAlarms`
--

DROP TABLE IF EXISTS `kamsAlarms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `kamsAlarms` (
  `id` int(20) NOT NULL AUTO_INCREMENT,
  `active` int(2) DEFAULT NULL,
  `timestamp` varchar(25) DEFAULT NULL,
  `module` varchar(100) DEFAULT NULL,
  `iface` varchar(10) DEFAULT NULL,
  `msgline` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kamsCrons`
--

DROP TABLE IF EXISTS `kamsCrons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `kamsCrons` (
  `module` varchar(20) DEFAULT NULL,
  `procid` varchar(20) DEFAULT NULL,
  `ifaceid` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `moto_channel_data`
--

DROP TABLE IF EXISTS `moto_channel_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `moto_channel_data` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `channel_id` int(11) DEFAULT NULL,
  `channel_name` varchar(200) DEFAULT NULL,
  `last_activity` int(11) DEFAULT 1645117261,
  `timeout_number` smallint(6) DEFAULT 0,
  `timeout_value` varchar(50) DEFAULT 'Hours',
  `alarm` varchar(50) DEFAULT NULL,
  `spare_3` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `moto_channel_settings`
--

DROP TABLE IF EXISTS `moto_channel_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `moto_channel_settings` (
  `id` tinyint(4) NOT NULL AUTO_INCREMENT,
  `time_limit` smallint(6) DEFAULT NULL,
  `emails` varchar(300) DEFAULT NULL,
  `alert_repeat` smallint(6) DEFAULT NULL,
  `spare_1` varchar(50) DEFAULT NULL,
  `spare_2` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `serial_data`
--

DROP TABLE IF EXISTS `serial_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `serial_data` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `epoch` varchar(60) DEFAULT NULL,
  `size` mediumint(9) DEFAULT NULL,
  `iface` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `serial_entries`
--

DROP TABLE IF EXISTS `serial_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `serial_entries` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(100) DEFAULT NULL,
  `Roles` varchar(250) DEFAULT NULL,
  `AgentID` varchar(250) DEFAULT NULL,
  `emailed` varchar(250) DEFAULT 'no',
  `Custom_2` varchar(250) DEFAULT NULL,
  `Custom_3` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `serial_settings`
--

DROP TABLE IF EXISTS `serial_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `serial_settings` (
  `id` tinyint(4) NOT NULL DEFAULT 1,
  `show_eth` tinyint(4) DEFAULT 1,
  `graph_time` smallint(6) DEFAULT 6,
  `emails` varchar(300) DEFAULT NULL,
  `alarmID` varchar(10) DEFAULT NULL,
  `spare_3` varchar(50) DEFAULT NULL,
  `issueCount` float(4,2) DEFAULT NULL,
  `ifaceid` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `udp_data`
--

DROP TABLE IF EXISTS `udp_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `udp_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `epoch` varchar(50) DEFAULT NULL,
  `udp_packets` mediumint(9) DEFAULT NULL,
  `tcp_packets` mediumint(9) DEFAULT NULL,
  `spare_1` varchar(20) DEFAULT NULL,
  `spare_2` varchar(20) DEFAULT NULL,
  `iface` varchar(30) DEFAULT NULL,
  `tstamp` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `udp_settings`
--

DROP TABLE IF EXISTS `udp_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `udp_settings` (
  `id` tinyint(4) NOT NULL,
  `show_eth` tinyint(4) DEFAULT 1,
  `graph_time` smallint(6) DEFAULT 30,
  `emails` varchar(300) DEFAULT NULL,
  `alarmID` varchar(50) DEFAULT NULL,
  `spare_3` varchar(50) DEFAULT NULL,
  `ifaceid` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-05-08 11:49:39
