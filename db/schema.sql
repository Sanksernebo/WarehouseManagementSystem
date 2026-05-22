-- MySQL dump 10.13  Distrib 8.3.0, for macos12.6 (x86_64)
--
-- Host: d129780.mysql.zonevs.eu    Database: d129780_cartehnikdev
-- ------------------------------------------------------
-- Server version	11.4.10-MariaDB-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `Kalender`
--

DROP TABLE IF EXISTS `Kalender`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Kalender` (
  `kalendri_id` int(11) NOT NULL AUTO_INCREMENT,
  `kliendi_nimi` varchar(255) NOT NULL,
  `broneeritud_aeg` date NOT NULL,
  `algus_aeg` time NOT NULL,
  `lopp_aeg` time NOT NULL,
  `kirjeldus` text DEFAULT NULL,
  `sissekande_kuupaev` timestamp NOT NULL DEFAULT current_timestamp(),
  `reg_nr` varchar(10) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`kalendri_id`),
  KEY `fk_user` (`user_id`),
  CONSTRAINT `fk_user` FOREIGN KEY (`user_id`) REFERENCES `Login` (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=2040 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Ladu`
--

DROP TABLE IF EXISTS `Ladu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Ladu` (
  `toote_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Tootekood` varchar(30) NOT NULL,
  `Nimetus` varchar(255) NOT NULL,
  `Kogus` int(11) NOT NULL,
  `Sisseost` decimal(7,2) NOT NULL,
  `Jaehind` decimal(7,2) NOT NULL,
  `Lopphind` decimal(7,2) DEFAULT NULL,
  `Ost` enum('-','InterCars','AD Baltic','Balti Autoosad','Erimell') NOT NULL,
  `Olek` enum('Isiklik','Firma','Tagastus') CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  PRIMARY KEY (`toote_id`),
  UNIQUE KEY `Tootekood` (`Tootekood`)
) ENGINE=InnoDB AUTO_INCREMENT=118 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`d129780_cartehnik`@`217.146.73.1`*/ /*!50003 TRIGGER `Uus_toode` AFTER INSERT ON `Ladu`
 FOR EACH ROW BEGIN
  IF NEW.Kogus > 0 THEN
    INSERT INTO Ladu_lisatud (Tootekood, Nimetus, Vana_kogus, Uus_kogus, Vahe, Olek, Kuupäev)
    VALUES (NEW.Tootekood, NEW.Nimetus, 0, NEW.Kogus, NEW.Kogus, NEW.Olek, NOW());
  END IF;
  END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`d129780_cartehnik`@`217.146.73.1`*/ /*!50003 TRIGGER `Ladu_logi` BEFORE UPDATE ON `Ladu`
 FOR EACH ROW BEGIN
  IF OLD.Kogus > NEW.Kogus OR OLD.Lopphind <> NEW.Lopphind THEN
    INSERT INTO Ladu_logi (Tootekood, Nimetus, Kogus, Kuupaev,Sisseost, Hind, Summa, Olek)
    VALUES (NEW.Tootekood, NEW.Nimetus, NEW.Kogus - OLD.Kogus, NOW(), OLD.Sisseost ,NEW.Lopphind,(OLD.Kogus - NEW.Kogus) * NEW.Lopphind, NEW.Olek);
      set NEW.Lopphind = NULL;
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`d129780_cartehnik`@`217.146.73.1`*/ /*!50003 TRIGGER `Uus_kogus` AFTER UPDATE ON `Ladu`
 FOR EACH ROW BEGIN
  IF NEW.Kogus > OLD.Kogus THEN
    INSERT INTO Ladu_lisatud (Tootekood, Nimetus, Vana_kogus, Uus_kogus, Vahe, Olek, Kuupäev)
    VALUES (NEW.Tootekood, NEW.Nimetus, OLD.Kogus, NEW.Kogus, NEW.Kogus - OLD.Kogus, NEW.Olek, NOW());
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `Ladu_lisatud`
--

DROP TABLE IF EXISTS `Ladu_lisatud`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Ladu_lisatud` (
  `sissetuleku_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Tootekood` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `Nimetus` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `Vana_kogus` int(11) NOT NULL,
  `Uus_kogus` int(11) NOT NULL,
  `Vahe` int(11) NOT NULL,
  `Olek` enum('Isiklik','Firma','Tagastus') NOT NULL,
  `Kuupäev` datetime NOT NULL,
  PRIMARY KEY (`sissetuleku_id`)
) ENGINE=InnoDB AUTO_INCREMENT=115 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Ladu_logi`
--

DROP TABLE IF EXISTS `Ladu_logi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Ladu_logi` (
  `logi_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Tootekood` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `Nimetus` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `Kogus` int(11) NOT NULL,
  `Kuupaev` datetime NOT NULL,
  `Sisseost` decimal(7,2) NOT NULL,
  `Hind` decimal(7,2) NOT NULL,
  `Summa` decimal(7,2) NOT NULL,
  `Olek` enum('Isiklik','Firma','Tagastus','') NOT NULL,
  PRIMARY KEY (`logi_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Login`
--

DROP TABLE IF EXISTS `Login`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Login` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `kasutajanimi` varchar(50) NOT NULL,
  `parool` varchar(255) NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `kasutajanimi` (`kasutajanimi`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Rehvi_Ladu`
--

DROP TABLE IF EXISTS `Rehvi_Ladu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Rehvi_Ladu` (
  `ladustamise_id` int(11) NOT NULL AUTO_INCREMENT,
  `RegNr` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Kuupaev` date NOT NULL,
  `Omanik` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `Kogus` int(11) NOT NULL,
  `Hooaeg` enum('Suverehv','Naastrehv','Lamellrehv') NOT NULL,
  PRIMARY KEY (`ladustamise_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Rehvi_myyk`
--

DROP TABLE IF EXISTS `Rehvi_myyk`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Rehvi_myyk` (
  `rehvimyyk_id` int(11) NOT NULL AUTO_INCREMENT,
  `RegNr` varchar(10) NOT NULL,
  `Kuupaev` date NOT NULL,
  `Kogus` int(11) NOT NULL,
  `Moot` varchar(28) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `Tootja` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `Hooaeg` enum('Suverehv','Naastrehv','Lamellrehv') NOT NULL,
  `Tarnija` enum('INTERCARS','ERIMELL','LATTAKO','FIXUS','MUU') NOT NULL,
  PRIMARY KEY (`rehvimyyk_id`)
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Tehtud_tood`
--

DROP TABLE IF EXISTS `Tehtud_tood`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Tehtud_tood` (
  `too_id` int(11) NOT NULL AUTO_INCREMENT,
  `RegNr` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `Kuupaev` datetime NOT NULL,
  `Odomeeter` int(11) NOT NULL,
  `Tehtud_tood` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_estonian_ci NOT NULL,
  PRIMARY KEY (`too_id`)
) ENGINE=InnoDB AUTO_INCREMENT=945 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping routines for database 'd129780_cartehnikdev'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-22 22:38:01
