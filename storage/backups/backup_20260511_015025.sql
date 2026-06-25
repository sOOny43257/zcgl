-- MariaDB dump 10.19  Distrib 10.4.28-MariaDB, for osx10.10 (x86_64)
--
-- Host: localhost    Database: zcgl
-- ------------------------------------------------------
-- Server version	10.4.28-MariaDB

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
-- Table structure for table `access_logs`
--

DROP TABLE IF EXISTS `access_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `access_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `user_name` varchar(100) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `method` varchar(10) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `browser` varchar(100) DEFAULT NULL,
  `platform` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `access_logs_user_id_foreign` (`user_id`),
  KEY `access_logs_ip_index` (`ip`),
  KEY `access_logs_created_at_index` (`created_at`),
  CONSTRAINT `access_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `access_logs`
--

LOCK TABLES `access_logs` WRITE;
/*!40000 ALTER TABLE `access_logs` DISABLE KEYS */;
INSERT INTO `access_logs` VALUES (1,NULL,NULL,'::1','http://localhost:8080/zcgl/public/login','GET','curl/7.53.1','Unknown','Unknown','2026-05-10 17:47:20'),(2,NULL,NULL,'::1','http://localhost:8080/zcgl/public/login','POST','curl/7.53.1','Unknown','Unknown','2026-05-10 17:47:20'),(3,1,'系统管理员','::1','http://localhost:8080/zcgl/public/system','GET','curl/7.53.1','Unknown','Unknown','2026-05-10 17:47:20'),(4,1,'系统管理员','::1','http://localhost:8080/zcgl/public/system/logs','GET','curl/7.53.1','Unknown','Unknown','2026-05-10 17:47:20'),(5,1,'系统管理员','::1','http://localhost:8080/zcgl/public/login','GET','curl/7.53.1','Unknown','Unknown','2026-05-10 17:47:20'),(6,1,'系统管理员','::1','http://localhost:8080/zcgl/public/system','GET','curl/7.53.1','Unknown','Unknown','2026-05-10 17:47:20'),(7,1,'系统管理员','::1','http://localhost:8080/zcgl/public/system/backup','POST','curl/7.53.1','Unknown','Unknown','2026-05-10 17:47:21'),(8,NULL,NULL,'::1','http://localhost:8080/zcgl/public/login','GET','curl/7.53.1','Unknown','Unknown','2026-05-10 17:47:52'),(9,1,'系统管理员','::1','http://localhost:8080/zcgl/public/system','GET','curl/7.53.1','Unknown','Unknown','2026-05-10 17:50:25'),(10,1,'系统管理员','::1','http://localhost:8080/zcgl/public/system/backup','POST','curl/7.53.1','Unknown','Unknown','2026-05-10 17:50:25');
/*!40000 ALTER TABLE `access_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asset_borrows`
--

DROP TABLE IF EXISTS `asset_borrows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asset_borrows` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `asset_id` bigint(20) unsigned NOT NULL,
  `order_no` varchar(50) NOT NULL COMMENT '借用单据号',
  `borrower` varchar(100) NOT NULL COMMENT '借用人',
  `department` varchar(100) DEFAULT NULL COMMENT '借用部门',
  `borrow_date` date NOT NULL COMMENT '借用日期',
  `expected_return_date` date DEFAULT NULL COMMENT '预计归还日期',
  `return_date` date DEFAULT NULL COMMENT '实际归还日期',
  `previous_status` varchar(20) NOT NULL COMMENT '借用前状态',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `asset_borrows_order_no_unique` (`order_no`),
  KEY `asset_borrows_asset_id_foreign` (`asset_id`),
  CONSTRAINT `asset_borrows_asset_id_foreign` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_borrows`
--

LOCK TABLES `asset_borrows` WRITE;
/*!40000 ALTER TABLE `asset_borrows` DISABLE KEYS */;
INSERT INTO `asset_borrows` VALUES (1,3,'JY-20260509-001','测试借用人','测试部','2026-05-09','2026-06-09','2026-05-09','在用','测试借用','2026-05-09 06:20:56','2026-05-09 06:21:12'),(2,4,'JY-20260509-002','端到端测试','测试部','2026-05-09',NULL,'2026-05-09','在用',NULL,'2026-05-09 07:24:02','2026-05-09 07:24:41'),(3,5,'JY-20260509-003','端到端测试','测试部','2026-05-09',NULL,'2026-05-09','闲置',NULL,'2026-05-09 07:24:02','2026-05-09 07:46:44');
/*!40000 ALTER TABLE `asset_borrows` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asset_logs`
--

DROP TABLE IF EXISTS `asset_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asset_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `asset_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `user_name` varchar(100) DEFAULT NULL,
  `field` varchar(50) NOT NULL,
  `field_label` varchar(50) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `asset_logs_asset_id_foreign` (`asset_id`),
  CONSTRAINT `asset_logs_asset_id_foreign` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_logs`
--

LOCK TABLES `asset_logs` WRITE;
/*!40000 ALTER TABLE `asset_logs` DISABLE KEYS */;
INSERT INTO `asset_logs` VALUES (1,1,1,'系统管理员','department','部门','财务部','行政部','2026-05-09 04:43:51'),(2,1,1,'系统管理员','room','房间号','301','501','2026-05-09 04:43:51'),(3,1,1,'系统管理员','status','状态','在用','闲置','2026-05-09 04:43:51'),(4,1,1,'系统管理员','user','使用人',NULL,'李四','2026-05-09 04:43:51'),(5,1,1,'系统管理员','remarks','备注',NULL,'测试变更','2026-05-09 04:43:51'),(6,2,1,'系统管理员','department','部门','财务部','信息中心','2026-05-09 05:14:00'),(7,2,1,'系统管理员','room','房间号','301','102','2026-05-09 05:14:00'),(8,2,1,'系统管理员','brand','品牌',NULL,'Dell','2026-05-09 05:14:00'),(9,2,1,'系统管理员','model','规格型号',NULL,'OptiPlex 7080','2026-05-09 05:14:00'),(10,2,1,'系统管理员','status','状态','在用','闲置','2026-05-09 05:14:00'),(11,2,1,'系统管理员','user','使用人',NULL,'王五','2026-05-09 05:14:00'),(12,3,1,'系统管理员','status','状态','在用','借用','2026-05-09 06:20:56'),(13,3,1,'系统管理员','status','状态','借用','在用','2026-05-09 06:21:12'),(14,5,1,'系统管理员','status','状态','闲置','报废','2026-05-09 06:22:34'),(15,5,1,'系统管理员','status','状态','报废','闲置','2026-05-09 06:22:34'),(16,4,1,'系统管理员','status','状态','在用','借用','2026-05-09 07:24:02'),(17,5,1,'系统管理员','status','状态','闲置','借用','2026-05-09 07:24:02'),(18,4,1,'系统管理员','status','状态','借用','在用','2026-05-09 07:24:41'),(19,6,1,'系统管理员','department','部门','信息中心','行政部','2026-05-09 07:44:20'),(20,6,1,'系统管理员','room','房间号','101','102','2026-05-09 07:44:20'),(21,6,1,'系统管理员','user','使用人',NULL,'赵六','2026-05-09 07:44:20'),(22,6,1,'系统管理员','department','部门','行政部','信息中心','2026-05-09 07:44:20'),(23,5,1,'系统管理员','status','状态','借用','闲置','2026-05-09 07:46:44');
/*!40000 ALTER TABLE `asset_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assets`
--

DROP TABLE IF EXISTS `assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `asset_code` varchar(20) DEFAULT NULL,
  `financial_code` varchar(50) DEFAULT NULL,
  `name` varchar(200) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `room` varchar(50) DEFAULT NULL,
  `ip` varchar(45) NOT NULL,
  `mac` varchar(17) NOT NULL,
  `sn` varchar(200) DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `category` varchar(50) NOT NULL DEFAULT '计算机',
  `status` varchar(20) NOT NULL DEFAULT '在用',
  `user` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `assets_ip_unique` (`ip`),
  UNIQUE KEY `assets_asset_code_unique` (`asset_code`),
  KEY `assets_department_index` (`department`),
  KEY `assets_mac_index` (`mac`),
  KEY `assets_sn_index` (`sn`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assets`
--

LOCK TABLES `assets` WRITE;
/*!40000 ALTER TABLE `assets` DISABLE KEYS */;
INSERT INTO `assets` VALUES (1,'C26001',NULL,'财务部台式机-001','行政部','501','192.168.1.101','00:1A:2B:3C:4D:01','SN20230001',NULL,NULL,'台式计算机（非国产）','闲置','李四','测试变更','2026-05-09 03:41:26','2026-05-09 04:43:51'),(2,'C26002',NULL,'财务部台式机-002','信息中心','102','192.168.1.102','00:1A:2B:3C:4D:02','SN20230002','Dell','OptiPlex 7080','台式计算机（非国产）','闲置','王五',NULL,'2026-05-09 03:41:26','2026-05-09 05:14:00'),(3,'P26001',NULL,'财务部打印机','财务部','302','192.168.1.103','00:1A:2B:3C:4D:03','SN20230003',NULL,NULL,'打印机','在用',NULL,NULL,'2026-05-09 03:41:26','2026-05-09 06:21:12'),(4,'C26003',NULL,'人事部笔记本-001','人事部','401','192.168.1.201','00:1A:2B:3C:4D:11','SN20230011',NULL,NULL,'台式计算机（非国产）','在用',NULL,NULL,'2026-05-09 03:41:26','2026-05-09 07:24:41'),(5,'C26004',NULL,'人事部笔记本-002','人事部','402','192.168.1.202','00:1A:2B:3C:4D:12','SN20230012',NULL,NULL,'台式计算机（非国产）','闲置',NULL,NULL,'2026-05-09 03:41:26','2026-05-09 07:46:44'),(6,'D26001',NULL,'信息中心服务器-001','信息中心','102','192.168.1.1','00:1A:2B:3C:4D:AA','SN20230021',NULL,NULL,'服务器','在用','赵六',NULL,'2026-05-09 03:41:26','2026-05-09 07:44:20'),(7,'D26002',NULL,'信息中心交换机-001','信息中心','101','192.168.1.254','00:1A:2B:3C:4D:BB','SN20230022',NULL,NULL,'交换机','在用',NULL,NULL,'2026-05-09 03:41:26','2026-05-09 03:41:26'),(8,'D26003',NULL,'信息中心路由器-001','信息中心','101','192.168.1.253','00:1A:2B:3C:4D:CC','SN20230023',NULL,NULL,'路由器','在用',NULL,NULL,'2026-05-09 03:41:26','2026-05-09 03:41:26'),(9,'C26005',NULL,'行政部台式机-001','行政部','201','192.168.1.131','00:1A:2B:3C:4D:31','SN20230031',NULL,NULL,'台式计算机（非国产）','维修',NULL,NULL,'2026-05-09 03:41:26','2026-05-09 03:41:26'),(10,'D26004',NULL,'行政部显示器-001','行政部','201','192.168.1.132','00:1A:2B:3C:4D:32','SN20230032',NULL,NULL,'显示器','闲置',NULL,NULL,'2026-05-09 03:41:26','2026-05-09 03:41:26'),(11,'C26006',NULL,'测试导入-新增资产','人事部','402','10.0.0.100','AA:BB:CC:DD:EE:01','IMP001','华为','MateStation','台式计算机（非国产）','在用','测试员','CSV导入测试','2026-05-09 05:41:43','2026-05-09 05:41:43'),(12,'P26002',NULL,'自动编号测试','测试部','999','10.0.0.99','FF:EE:DD:CC:BB:99',NULL,NULL,NULL,'打印机','在用',NULL,NULL,'2026-05-09 07:05:55','2026-05-09 07:05:55'),(13,'C26007',NULL,'架构测试资产','测试部',NULL,'10.10.10.10','AA:BB:CC:DD:EE:10',NULL,NULL,NULL,'台式计算机（非国产）','在用',NULL,NULL,'2026-05-09 15:19:36','2026-05-09 15:19:36');
/*!40000 ALTER TABLE `assets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `department_codes`
--

DROP TABLE IF EXISTS `department_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `department_codes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(30) NOT NULL DEFAULT 'department',
  `code` varchar(50) NOT NULL COMMENT '部门编号',
  `name` varchar(100) NOT NULL COMMENT '部门名称',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `department_codes_type_code_unique` (`type`,`code`),
  KEY `department_codes_type_index` (`type`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `department_codes`
--

LOCK TABLES `department_codes` WRITE;
/*!40000 ALTER TABLE `department_codes` DISABLE KEYS */;
INSERT INTO `department_codes` VALUES (1,'department','CW','财务部','2026-05-09 05:16:24','2026-05-09 05:16:24'),(2,'department','RS','人事部','2026-05-09 05:16:24','2026-05-09 05:16:24'),(3,'department','XZ','行政部','2026-05-09 05:16:24','2026-05-09 05:16:24'),(4,'department','XX','信息中心','2026-05-09 05:16:24','2026-05-09 05:16:24'),(19,'category','DTGN','台式计算机（国产）','2026-05-09 14:43:41','2026-05-09 14:43:41'),(20,'category','DTFN','台式计算机（非国产）','2026-05-09 14:43:41','2026-05-09 14:43:41'),(21,'category','PRT','打印机','2026-05-09 14:43:41','2026-05-09 14:43:41'),(22,'category','SWT','交换机','2026-05-09 14:43:41','2026-05-09 14:43:41'),(23,'category','MON','显示器','2026-05-09 14:43:41','2026-05-09 14:43:41'),(24,'category','SRV','服务器','2026-05-09 14:43:41','2026-05-09 14:43:41'),(25,'category','ROU','路由器','2026-05-09 14:43:41','2026-05-09 14:43:41'),(26,'category','OTH','其他','2026-05-09 14:43:41','2026-05-09 14:43:41'),(27,'status','ZY','在用','2026-05-09 14:43:41','2026-05-09 14:43:41'),(28,'status','XZ','闲置','2026-05-09 14:43:41','2026-05-09 14:43:41'),(29,'status','WX','维修','2026-05-09 14:43:41','2026-05-09 14:43:41'),(30,'status','JIE','借用','2026-05-09 14:43:41','2026-05-09 14:43:41'),(31,'status','DBF','待报废','2026-05-09 14:43:41','2026-05-09 14:43:41'),(32,'status','BF','报废','2026-05-09 14:43:41','2026-05-09 14:43:41');
/*!40000 ALTER TABLE `department_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2014_10_12_000000_create_users_table',1),(2,'2014_10_12_100000_create_password_reset_tokens_table',1),(3,'2019_08_19_000000_create_failed_jobs_table',1),(4,'2019_12_14_000001_create_personal_access_tokens_table',1),(5,'2026_05_09_113653_add_fields_to_users_table',2),(6,'2026_05_09_113653_create_assets_table',2),(7,'2026_05_09_122136_add_user_to_assets_table',3),(8,'2026_05_09_124051_create_asset_logs_table',4),(9,'2026_05_09_125616_create_department_codes_table',5),(10,'2026_05_09_125643_add_brand_model_to_assets_table',5),(11,'2026_05_09_140155_add_type_to_department_codes_table',6),(12,'2026_05_09_140215_create_asset_borrows_table',6),(13,'2026_05_09_140220_create_asset_scraps_table',6),(14,'2026_05_09_145446_add_asset_code_and_financial_code_to_assets',7),(15,'2026_05_09_231048_create_transfer_orders_table',8),(16,'2026_05_11_013907_create_access_logs_table',9);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
INSERT INTO `personal_access_tokens` VALUES (1,'App\\Models\\User',1,'api-token','933020714ed75095c8b1d7b55e207363c339c57743ed28adec475687746c93d2','[\"*\"]','2026-05-10 17:24:28',NULL,'2026-05-10 17:24:28','2026-05-10 17:24:28');
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transfer_orders`
--

DROP TABLE IF EXISTS `transfer_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transfer_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_no` varchar(50) NOT NULL,
  `asset_id` bigint(20) unsigned NOT NULL,
  `log_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`log_ids`)),
  `from_dept` varchar(100) DEFAULT NULL,
  `to_dept` varchar(100) DEFAULT NULL,
  `from_user` varchar(100) DEFAULT NULL,
  `to_user` varchar(100) DEFAULT NULL,
  `operator` varchar(100) DEFAULT NULL,
  `is_cancelled` tinyint(1) NOT NULL DEFAULT 0,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transfer_orders_order_no_unique` (`order_no`),
  KEY `transfer_orders_asset_id_foreign` (`asset_id`),
  CONSTRAINT `transfer_orders_asset_id_foreign` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transfer_orders`
--

LOCK TABLES `transfer_orders` WRITE;
/*!40000 ALTER TABLE `transfer_orders` DISABLE KEYS */;
INSERT INTO `transfer_orders` VALUES (1,'DB-20260509-001',6,'[19,21,22]','信息中心','行政部',NULL,'赵六','系统管理员',0,NULL,'2026-05-09 15:19:36','2026-05-09 15:19:36'),(2,'DB-20260509-002',2,'[6,11]','财务部','信息中心',NULL,'王五','系统管理员',0,NULL,'2026-05-09 15:19:36','2026-05-09 15:19:36'),(3,'DB-20260509-003',1,'[1,4]','财务部','行政部',NULL,'李四','系统管理员',0,NULL,'2026-05-09 15:19:36','2026-05-09 15:19:36');
/*!40000 ALTER TABLE `transfer_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'user',
  `department` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_username_unique` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'系统管理员','admin','admin@example.com',NULL,'$2y$12$uHnphLHjfwMjbc8RH8Mae.Qre4/SlzDtkwFCcedX9FjX0EE3dC9ya','admin','信息中心',1,NULL,'2026-05-09 03:41:26','2026-05-09 03:41:26'),(2,'张三','zhangsan','zhangsan@example.com',NULL,'$2y$12$ilyaAjzAZlGQ2ETUd1RgwOB81ocn1riuTeLmdQN3fHOiOqgZ7sc.q','user','财务部',1,NULL,'2026-05-09 03:41:26','2026-05-09 03:41:26');
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

-- Dump completed on 2026-05-11  9:50:25
