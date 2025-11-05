-- MySQL dump 10.13  Distrib 5.6.49, for Linux (x86_64)
--
-- Host: localhost    Database: KAMS
-- ------------------------------------------------------
-- Server version	5.6.49

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
-- Table structure for table `Alarms`
--

DROP TABLE IF EXISTS `Alarms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Alarms` (
  `alarm_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `alarm_name` varchar(100) DEFAULT NULL,
  `silence_alarms` varchar(100) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `OID` varchar(50) DEFAULT NULL,
  `system_type` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`alarm_id`),
  UNIQUE KEY `alarm_name` (`alarm_name`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Alarms`
--

LOCK TABLES `Alarms` WRITE;
/*!40000 ALTER TABLE `Alarms` DISABLE KEYS */;
INSERT INTO `Alarms` VALUES (1,'alDiskAlmostFull',NULL,'Hard disk is nearing capacity.','.1.3.6.1.4.1.5166.1.3.1.3.0.0.1','Audiolog'),(2,'alNoSQLServerLicense',NULL,'SQL Server license not found','.1.3.6.1.4.1.5166.1.3.1.3.0.0.2','Audiolog'),(3,'alHKFailed',NULL,'Housekeeping has failed.','.1.3.6.1.4.1.5166.1.3.1.3.0.0.3','Audiolog'),(4,'alDongleFailed',NULL,'Dongle not found','.1.3.6.1.4.1.5166.1.3.1.3.0.0.4','Audiolog'),(5,'alEncryptionStatus',NULL,'Unable to connect to AKMS server.','.1.3.6.1.4.1.5166.1.3.1.3.0.0.5','Audiolog'),(6,'alVAMStatus',NULL,'VAM Critical Error.','.1.3.6.1.4.1.5166.1.3.1.3.0.0.6','Audiolog'),(7,'alTeleco32Status',NULL,'No Audio on this channel.','.1.3.6.1.4.1.5166.1.3.1.3.0.0.7','Audiolog'),(8,'alSQLConnectionFailed',NULL,'SQL Connection Failed in Call Manager.','.1.3.6.1.4.1.5166.1.3.1.3.0.0.8','Audiolog'),(9,'alMgrCallsDeleted',NULL,'SQL Connection Failed in Call Manager.','.1.3.6.1.4.1.5166.1.3.1.3.0.0.9','Audiolog'),(10,'alNonuploadedFileDeleted',NULL,'Deleted non-uploaded file.','.1.3.6.1.4.1.5166.1.3.1.3.0.0.10','Audiolog'),(11,'alNonarchivedFileDeleted',NULL,'Deleted non-archived file.','.1.3.6.1.4.1.5166.1.3.1.3.0.0.11','Audiolog'),(12,'alNoRecorderControlDB',NULL,'No Recorder.mdb file found on the server.','.1.3.6.1.4.1.5166.1.3.1.3.0.1.0','Audiolog'),(13,'alNoChannels',NULL,'No recording channels were detected.','.1.3.6.1.4.1.5166.1.3.1.3.0.1.1','Audiolog'),(14,'alNoLicenseBlock',NULL,'Audiolog license dongle not found','.1.3.6.1.4.1.5166.1.3.1.3.0.1.3','Audiolog'),(15,'alRecordAreaMissing',NULL,'Prelog area not found.','.1.3.6.1.4.1.5166.1.3.1.3.0.1.4','Audiolog'),(16,'alChannelNotRecording',NULL,'Channel is not recording.','.1.3.6.1.4.1.5166.1.3.1.3.0.1.5','Audiolog'),(17,'alRecCallsDeleted',NULL,'Call was deleted.','.1.3.6.1.4.1.5166.1.3.1.3.0.1.6','Audiolog'),(18,'alRecPacketsDropped',NULL,'Packets were dropped for a call.','.1.3.6.1.4.1.5166.1.3.1.3.0.1.7','Audiolog'),(19,'alRecNoPacketsOneSide',NULL,'Packets were dropped for a call.','.1.3.6.1.4.1.5166.1.3.1.3.0.1.8','Audiolog'),(20,'alTapeIsFull',NULL,'Media in Archive Drive is full.','.1.3.6.1.4.1.5166.1.3.1.3.0.2.0','Audiolog'),(21,'alTapeAlmostFull',NULL,'Media in Archive Drive is almost full.','.1.3.6.1.4.1.5166.1.3.1.3.0.2.1','Audiolog'),(22,'alOneTapeFull',NULL,'Media in Day Drive is full. ','.1.3.6.1.4.1.5166.1.3.1.3.0.2.2','Audiolog'),(23,'alDaydriveAlmostFull',NULL,'Media in Day Drive is almost full.','.1.3.6.1.4.1.5166.1.3.1.3.0.2.1','Audiolog'),(24,'alTapeIOError',NULL,'Archiving drive is has input/output errors.','.1.3.6.1.4.1.5166.1.3.1.3.0.2.3','Audiolog'),(25,'alCTILinkFailed',NULL,'CTIling has failed','.1.3.6.1.4.1.5166.1.3.1.3.0.3.0','Audiolog'),(26,'alCTILinkCommunicationWithALSrvFailed',NULL,'CTILink communication with ALSrv is failed.','.1.3.6.1.4.1.5166.1.3.1.3.0.3.1','Audiolog'),(27,'alCTILinkFailedToSendDatatoMSMQ',NULL,'CTILink failed to queue message in SMDR queue.','.1.3.6.1.4.1.5166.1.3.1.3.0.3.2','Audiolog'),(28,'alCTILinkSMDRFailedToQueueItem',NULL,'SMDR service failed to add item to queue.','.1.3.6.1.4.1.5166.1.3.1.3.0.3.3','Audiolog'),(29,'alCTILinkDeviceInactivity',NULL,'CTILink device inactivity detected.','.1.3.6.1.4.1.5166.1.3.1.3.0.3.4','Audiolog'),(30,'alAudiologNotRunning',NULL,'Audiolog Server is down.','.1.3.6.1.4.1.5166.1.3.1.3.0.5.0','Audiolog'),(31,'alRecorderNotRunning',NULL,'Recorder module is not running. ','.1.3.6.1.4.1.5166.1.3.1.3.0.5.1','Audiolog'),(32,'alManagerNotRunning',NULL,'Manager module is not running. ','.1.3.6.1.4.1.5166.1.3.1.3.0.5.2','Audiolog'),(33,'alArchiverNotRunning',NULL,'Archiver module is not running. ','.1.3.6.1.4.1.5166.1.3.1.3.0.5.4','Audiolog'),(34,'alCTILinkNotRunning',NULL,'CTILink module is not running. ','.1.3.6.1.4.1.5166.1.3.1.3.0.5.5','Audiolog'),(35,'alIRISNotRunning',NULL,'IRIS Engine module is not running.','.1.3.6.1.4.1.5166.1.3.1.3.0.5.7','Audiolog'),(36,'alSOCKETSrvNotRunning',NULL,'Socket Server module is not running.','.1.3.6.1.4.1.5166.1.3.1.3.0.5.8','Audiolog'),(37,'alUpdRateBelowRecRate',NULL,'Uploading rate is below recording rate. ','.1.3.6.1.4.1.5166.1.3.1.3.0.5.9','Audiolog'),(38,'alCmprsRateBelowRecRate',NULL,'Compression rate is below recording rate. ','.1.3.6.1.4.1.5166.1.3.1.3.0.5.10','Audiolog'),(39,'alCmprsFailed',NULL,'Compression server failed to compress a call. ','.1.3.6.1.4.1.5166.1.3.1.3.0.5.11','Audiolog'),(40,'alWindowEventLogs',NULL,'Event log error','.1.3.6.1.4.1.5166.1.3.1.3.0.6','Audiolog'),(41,'alALMasterSMDelFailed',NULL,'Space Manager Deletion Failed.','.1.3.6.1.4.1.5166.1.3.1.3.0.7.0','Audiolog'),(42,'alALMasterHKDbConnFailed',NULL,'ALMaster spacemanager DB connection failed','.1.3.6.1.4.1.5166.1.3.1.3.0.7.1','Audiolog'),(43,'alALMasterHKDiskFull',NULL,'No disk free space available to run housekeeping','.1.3.6.1.4.1.5166.1.3.1.3.0.7.2','Audiolog'),(44,'alALMasterHKDelFailed',NULL,'HouseKeeping failed to delete calls.','.1.3.6.1.4.1.5166.1.3.1.3.0.7.3','Audiolog'),(45,'alALMasterHKConnFailed',NULL,'HouseKeeping failed to get  connection to  database.','.1.3.6.1.4.1.5166.1.3.1.3.0.7.4','Audiolog'),(46,'alALMasterHKFailed',NULL,'HouseKeeping failed to complete.','.1.3.6.1.4.1.5166.1.3.1.3.0.7.5','Audiolog'),(47,'alALMasterMUploadFailed',NULL,'MUpload Uploader failed to upload calls.','.1.3.6.1.4.1.5166.1.3.1.3.0.7.6','Audiolog'),(48,'alScreenRecordingFailEvents',NULL,'Screen Recording connection to client failed','.1.3.6.1.4.1.5166.1.3.1.3.0.8.0','Audiolog'),(49,'alSRVVSRxCommunicationFailedEvents',NULL,'Screen Recording connection to server failed.','.1.3.6.1.4.1.5166.1.3.1.3.0.8.1','Audiolog');
/*!40000 ALTER TABLE `Alarms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `History`
--

DROP TABLE IF EXISTS `History`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `History` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `server_id` varchar(16) DEFAULT NULL,
  `event_time` varchar(40) DEFAULT NULL,
  `event_alarm` varchar(100) DEFAULT NULL,
  `description` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `History`
--

LOCK TABLES `History` WRITE;
/*!40000 ALTER TABLE `History` DISABLE KEYS */;
INSERT INTO `History` VALUES (19,'192.168.2.68','1600701719','alRecCallsDeleted','Call was deleted.'),(20,'192.168.2.12','1600701719','alTeleco32Status','No Audio on this channel.'),(21,'172.29.43.55','1600701719','alDiskAlmostFull','Hard disk is nearing capacity.'),(22,'172.29.43.55','1600701719','alNoSQLServerLicense','SQL Server license not found'),(23,'172.29.43.55','1600701719','alDongleFailed','Dongle not found'),(24,'192.168.2.68','1600701721','alRecCallsDeleted','Call was deleted.'),(25,'192.168.2.12','1600701721','alTeleco32Status','No Audio on this channel.'),(26,'172.29.43.55','1600701721','alDiskAlmostFull','Hard disk is nearing capacity.'),(27,'172.29.43.55','1600701721','alNoSQLServerLicense','SQL Server license not found'),(40,'172.29.43.55','1601914981','alDiskAlmostFull','Hard disk is nearing capacity.'),(41,'172.29.43.55','1601914981','alNoSQLServerLicense','SQL Server license not found'),(42,'172.29.43.55','1601918320','newAlarm','New Alarm Test'),(43,'172.29.43.55','1601919229','alDiskAlmostFull','Hard disk is nearing capacity.'),(44,'172.29.43.55','1603376833','alNoSQLServerLicense','SQL Server license not found'),(45,'172.29.43.55','1606929865','alALMasterHKConnFailed','HouseKeeping failed to get  connection to  database.'),(46,'172.29.43.55','1606930464','alALMasterHKConnFailed','HouseKeeping failed to get  connection to  database.'),(47,'172.29.43.55','1606930562','alALMasterHKConnFailed','HouseKeeping failed to get  connection to  database.'),(48,'172.29.43.55','1606930604','alALMasterHKConnFailed','HouseKeeping failed to get  connection to  database.'),(49,'172.29.43.55','1606930704','alALMasterHKConnFailed','HouseKeeping failed to get  connection to  database.'),(50,'172.29.43.55','1606930981','alALMasterHKConnFailed','HouseKeeping failed to get  connection to  database.'),(51,'172.29.43.55','1606931053','alALMasterHKConnFailed','HouseKeeping failed to get  connection to  database.'),(52,'172.29.43.55','1606931069','alALMasterHKConnFailed','HouseKeeping failed to get  connection to  database.'),(53,'172.29.43.55','1606931724','alALMasterHKConnFailed','HouseKeeping failed to get  connection to  database.'),(54,'172.29.43.55','1606932288','alALMasterHKConnFailed','HouseKeeping failed to get  connection to  database.'),(55,'172.29.43.55','1606932360','alALMasterHKConnFailed','HouseKeeping failed to get  connection to  database.'),(56,'172.29.43.55','1606932651','alALMasterHKConnFailed','HouseKeeping failed to get  connection to  database.');
/*!40000 ALTER TABLE `History` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Servers`
--

DROP TABLE IF EXISTS `Servers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Servers` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `name` varchar(75) DEFAULT NULL,
  `last_alarm_time` varchar(255) DEFAULT NULL,
  `silenced` varchar(30) NOT NULL DEFAULT '0',
  `server_ip` varchar(16) DEFAULT NULL,
  `monitored_alarms` varchar(5000) DEFAULT NULL,
  `last_alarm_id` varchar(255) DEFAULT NULL,
  `silenced_alarms` varchar(5000) DEFAULT NULL,
  `silenced_all` tinyint(4) DEFAULT '0',
  `system_type` varchar(50) NOT NULL,
  `acknowledge` tinyint(4) DEFAULT NULL,
  `ackName` varchar(100) DEFAULT NULL,
  `ackTime` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `server_ip` (`server_ip`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Servers`
--

LOCK TABLES `Servers` WRITE;
/*!40000 ALTER TABLE `Servers` DISABLE KEYS */;
INSERT INTO `Servers` VALUES (3,'Pol-ECC-TLR01','1600701721','0','192.168.2.68','alRecCallsDeleted-,alChannelNotRecording-,alRecordAreaMissing-,alNoChannels-,alNoRecorderControlDB-,','Call was deleted.',NULL,0,'Audiolog',NULL,NULL,NULL),(4,'Pol-ECC-KOVA2','1600701721','0','192.168.2.12','alEncryptionStatus-,alDongleFailed-,alHKFailed-,alDiskAlmostFull-,','No Audio on this channel.',NULL,0,'Audiolog',NULL,NULL,NULL),(5,'POL-ECC-KOVA1','1606932651','0','172.29.43.55','alALMasterHKConnFailed-,alVAMStatus-,alEncryptionStatus-,alDiskAlmostFull-,alDongleFailed-,alHKFailed-,alNoSQLServerLicense-,','HouseKeeping failed to get  connection to  database.','',0,'Audiolog',1,'Kova Tech','1606933131'),(14,'Test',NULL,'0','172.29.43.56',NULL,NULL,NULL,0,'Eventide',NULL,NULL,NULL);
/*!40000 ALTER TABLE `Servers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `emails`
--

DROP TABLE IF EXISTS `emails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `emails` (
  `id` tinyint(4) NOT NULL AUTO_INCREMENT,
  `addresses` varchar(500) DEFAULT NULL,
  `smtpServer` varchar(75) DEFAULT NULL,
  `fromAddress` varchar(75) DEFAULT NULL,
  `emailSubject` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `emails`
--

LOCK TABLES `emails` WRITE;
/*!40000 ALTER TABLE `emails` DISABLE KEYS */;
INSERT INTO `emails` VALUES (1,'support@kovacorp.com,jflynn@kovacorp.com','192.168.5.25','alert@app.montgomerycountymd.gov','Issue at Montgomery County MD');
/*!40000 ALTER TABLE `emails` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2021-02-12 12:20:34
