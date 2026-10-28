-- MySQL dump 10.13  Distrib 26.10.0, for macos26.6 (arm64)
--
-- Host: 127.0.0.1    Database: gudangku
-- ------------------------------------------------------
-- Server version	26.10.0

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
SET @MYSQLDUMP_TEMP_LOG_BIN = @@SESSION.SQL_LOG_BIN;
SET @@SESSION.SQL_LOG_BIN= 0;

--
-- GTID state at the beginning of the backup 
--

SET @@GLOBAL.GTID_PURGED=/*!80000 '+'*/ 'c014ecb0-d29a-11f1-8990-ed9626fb7b76:1-514';

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Gas LPG',NULL,'2026-10-28 06:42:32','2026-10-28 06:42:32'),(2,'Deterjen',NULL,'2026-10-28 06:42:32','2026-10-28 06:42:32'),(3,'Plastik packing kecil',NULL,'2026-10-28 06:42:32','2026-10-28 06:42:32'),(4,'Plastik packing besar',NULL,'2026-10-28 06:42:32','2026-10-28 06:42:32'),(5,'Botol parfum',NULL,'2026-10-28 06:42:32','2026-10-28 06:42:32'),(6,'Softener',NULL,'2026-10-28 06:42:32','2026-10-28 06:42:32'),(7,'Botol parfum ruangan',NULL,'2026-10-28 06:42:32','2026-10-28 06:42:32'),(8,'Pembersih lantai',NULL,'2026-10-28 06:42:32','2026-10-28 06:42:32'),(9,'Selotip',NULL,'2026-10-28 06:42:32','2026-10-28 06:42:32');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expenses`
--

DROP TABLE IF EXISTS `expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `expenses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `outlet_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `category` enum('listrik','air','ipal','gaji','sewa','lain') COLLATE utf8mb4_unicode_ci NOT NULL,
  `expense_date` date NOT NULL,
  `period_start` date DEFAULT NULL,
  `period_end` date DEFAULT NULL,
  `amount` decimal(14,2) NOT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expenses_user_id_foreign` (`user_id`),
  KEY `expenses_outlet_id_expense_date_index` (`outlet_id`,`expense_date`),
  KEY `expenses_category_expense_date_index` (`category`,`expense_date`),
  CONSTRAINT `expenses_outlet_id_foreign` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `expenses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expenses`
--

LOCK TABLES `expenses` WRITE;
/*!40000 ALTER TABLE `expenses` DISABLE KEYS */;
INSERT INTO `expenses` VALUES (1,1,1,'sewa','2026-10-01',NULL,NULL,3000000.00,'Sewa tempat bulan berjalan','2026-10-28 06:42:32','2026-10-28 06:42:32'),(2,1,1,'listrik','2026-10-05',NULL,NULL,350000.00,'Token listrik outlet pusat','2026-10-28 06:42:32','2026-10-28 06:42:32'),(3,1,1,'air','2026-10-06',NULL,NULL,180000.00,'Tagihan PDAM','2026-10-28 06:42:32','2026-10-28 06:42:32'),(4,1,1,'ipal','2026-10-07',NULL,NULL,250000.00,'Iuran IPAL bulanan','2026-10-28 06:42:32','2026-10-28 06:42:32'),(5,1,1,'lain','2026-10-10',NULL,NULL,120000.00,'Perbaikan keran dan lampu','2026-10-28 06:42:32','2026-10-28 06:42:32'),(6,1,1,'gaji','2026-10-19','2026-10-19','2026-10-25',1500000.00,'Gaji karyawan minggu ketiga','2026-10-28 06:42:32','2026-10-28 06:42:32'),(7,2,1,'sewa','2026-10-01',NULL,NULL,2250000.00,'Sewa tempat cabang','2026-10-28 06:42:32','2026-10-28 06:42:32'),(8,2,1,'listrik','2026-10-05',NULL,NULL,275000.00,'Token listrik cabang','2026-10-28 06:42:32','2026-10-28 06:42:32'),(9,2,1,'air','2026-10-06',NULL,NULL,150000.00,'Tagihan PDAM cabang','2026-10-28 06:42:32','2026-10-28 06:42:32'),(10,2,1,'ipal','2026-10-07',NULL,NULL,200000.00,'Iuran IPAL cabang','2026-10-28 06:42:32','2026-10-28 06:42:32'),(11,2,1,'gaji','2026-10-19','2026-10-19','2026-10-25',1400000.00,'Gaji karyawan minggu ketiga','2026-10-28 06:42:32','2026-10-28 06:42:32');
/*!40000 ALTER TABLE `expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
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
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0000_01_01_000000_create_outlets_table',1),(2,'0001_01_01_000000_create_users_table',1),(3,'0001_01_01_000001_create_cache_table',1),(4,'0001_01_01_000002_create_jobs_table',1),(5,'2026_10_28_000100_create_categories_table',1),(6,'2026_10_28_000200_create_suppliers_table',1),(7,'2026_10_28_000300_create_products_table',1),(8,'2026_10_28_000400_create_purchases_table',1),(9,'2026_10_28_000500_create_purchase_items_table',1),(10,'2026_10_28_000600_create_stock_movements_table',1),(11,'2026_10_28_000700_create_stock_opnames_table',1),(12,'2026_10_28_000800_create_stock_opname_items_table',1),(13,'2026_10_28_000900_create_expenses_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `outlets`
--

DROP TABLE IF EXISTS `outlets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `outlets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `outlets_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `outlets`
--

LOCK TABLES `outlets` WRITE;
/*!40000 ALTER TABLE `outlets` DISABLE KEYS */;
INSERT INTO `outlets` VALUES (1,'Outlet Cakrawala Pusat','CKR-01','Jl. Merdeka No. 10, Jakarta Pusat','021-5550101',1,'2026-10-28 06:42:30','2026-10-28 06:42:30'),(2,'Outlet Cakrawala Cabang','CKR-02','Jl. Raya Bogor No. 88, Jakarta Timur','021-5550202',1,'2026-10-28 06:42:30','2026-10-28 06:42:30');
/*!40000 ALTER TABLE `outlets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
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
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint unsigned NOT NULL,
  `supplier_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sku` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pcs',
  `min_stock` int unsigned NOT NULL DEFAULT '0',
  `price_buy` decimal(14,2) NOT NULL DEFAULT '0.00',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_sku_unique` (`sku`),
  KEY `products_category_id_foreign` (`category_id`),
  KEY `products_supplier_id_foreign` (`supplier_id`),
  KEY `products_is_active_category_id_index` (`is_active`,`category_id`),
  CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `products_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,1,1,'Gas LPG 12kg kondisi full','LPG-12','pcs',6,21000.00,1,'2026-10-28 06:42:32','2026-10-28 06:42:32'),(2,2,2,'Jerigen deterjen','DTR-JRG','pcs',4,33000.00,1,'2026-10-28 06:42:32','2026-10-28 06:42:32'),(3,3,2,'Plastik packing kecil','PLK-KCL','pcs',50,500.00,1,'2026-10-28 06:42:32','2026-10-28 06:42:32'),(4,4,2,'Plastik packing besar','PLK-BSR','pcs',30,1200.00,1,'2026-10-28 06:42:32','2026-10-28 06:42:32'),(5,5,2,'Botol parfum','BTL-PRF','pcs',20,4200.00,1,'2026-10-28 06:42:32','2026-10-28 06:42:32'),(6,6,2,'Box sachet softener','BOX-SFT','pcs',10,7800.00,1,'2026-10-28 06:42:32','2026-10-28 06:42:32'),(7,7,2,'Botol parfum ruangan','BTL-PRR','pcs',12,9500.00,1,'2026-10-28 06:42:32','2026-10-28 06:42:32'),(8,8,2,'Pembersih lantai','PBR-LNT','pcs',10,18000.00,1,'2026-10-28 06:42:32','2026-10-28 06:42:32'),(9,9,2,'Selotip','SLT-01','pcs',6,5000.00,1,'2026-10-28 06:42:32','2026-10-28 06:42:32');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_items`
--

DROP TABLE IF EXISTS `purchase_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `purchase_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `quantity` int unsigned NOT NULL,
  `price` decimal(14,2) DEFAULT NULL,
  `subtotal` decimal(14,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_items_purchase_id_product_id_unique` (`purchase_id`,`product_id`),
  KEY `purchase_items_product_id_foreign` (`product_id`),
  CONSTRAINT `purchase_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `purchase_items_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_items`
--

LOCK TABLES `purchase_items` WRITE;
/*!40000 ALTER TABLE `purchase_items` DISABLE KEYS */;
INSERT INTO `purchase_items` VALUES (1,1,3,100,NULL,0.00,'2026-10-28 06:42:32','2026-10-28 06:42:32'),(2,1,4,50,NULL,0.00,'2026-10-28 06:42:32','2026-10-28 06:42:32'),(3,1,9,20,NULL,0.00,'2026-10-28 06:42:32','2026-10-28 06:42:32'),(4,2,1,10,21000.00,210000.00,'2026-10-28 06:42:32','2026-10-28 06:42:32'),(5,2,2,5,33000.00,165000.00,'2026-10-28 06:42:32','2026-10-28 06:42:32'),(6,3,5,30,4200.00,126000.00,'2026-10-28 06:42:32','2026-10-28 06:42:32'),(7,3,6,12,7800.00,93600.00,'2026-10-28 06:42:32','2026-10-28 06:42:32'),(8,4,8,8,NULL,0.00,'2026-10-28 06:42:32','2026-10-28 06:42:32'),(9,4,7,10,NULL,0.00,'2026-10-28 06:42:32','2026-10-28 06:42:32'),(10,5,8,6,19500.00,117000.00,'2026-10-28 06:42:32','2026-10-28 06:42:32');
/*!40000 ALTER TABLE `purchase_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchases`
--

DROP TABLE IF EXISTS `purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchases` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `outlet_id` bigint unsigned NOT NULL,
  `supplier_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `invoice_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `received_at` date NOT NULL,
  `priced_at` date DEFAULT NULL,
  `paid_at` date DEFAULT NULL,
  `total` decimal(14,2) NOT NULL DEFAULT '0.00',
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchases_supplier_id_foreign` (`supplier_id`),
  KEY `purchases_user_id_foreign` (`user_id`),
  KEY `purchases_outlet_id_received_at_index` (`outlet_id`,`received_at`),
  KEY `purchases_priced_at_paid_at_index` (`priced_at`,`paid_at`),
  CONSTRAINT `purchases_outlet_id_foreign` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `purchases_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `purchases_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchases`
--

LOCK TABLES `purchases` WRITE;
/*!40000 ALTER TABLE `purchases` DISABLE KEYS */;
INSERT INTO `purchases` VALUES (1,1,2,2,'INV-SEED-001','2026-10-25',NULL,NULL,0.00,'Penerimaan rutin mingguan, menunggu harga owner.','2026-10-28 06:42:32','2026-10-28 06:42:32'),(2,1,1,2,'INV-SEED-002','2026-10-21','2026-10-22',NULL,375000.00,'Gas LPG dan deterjen, masuk hitungan hutang.','2026-10-28 06:42:32','2026-10-28 06:42:32'),(3,2,2,3,'INV-SEED-003','2026-10-18','2026-10-18','2026-10-19',219600.00,'Pembelian packaging cabang, sudah dilunasi.','2026-10-28 06:42:32','2026-10-28 06:42:32'),(4,2,2,3,'INV-SEED-004','2026-10-27',NULL,NULL,0.00,'Penerimaan cabang, menunggu harga.','2026-10-28 06:42:32','2026-10-28 06:42:32'),(5,1,2,1,'INV-SEED-005','2026-10-26','2026-10-26',NULL,117000.00,'Dibuat owner lengkap dengan harga sejak awal.','2026-10-28 06:42:32','2026-10-28 06:42:32');
/*!40000 ALTER TABLE `purchases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_movements`
--

DROP TABLE IF EXISTS `stock_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_movements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `outlet_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `type` enum('in','out','adjustment') COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL,
  `reference_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `moved_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_movements_product_id_foreign` (`product_id`),
  KEY `stock_movements_user_id_foreign` (`user_id`),
  KEY `stock_movements_outlet_id_product_id_index` (`outlet_id`,`product_id`),
  KEY `stock_movements_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  KEY `stock_movements_outlet_id_moved_at_index` (`outlet_id`,`moved_at`),
  CONSTRAINT `stock_movements_outlet_id_foreign` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `stock_movements_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `stock_movements_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_movements`
--

LOCK TABLES `stock_movements` WRITE;
/*!40000 ALTER TABLE `stock_movements` DISABLE KEYS */;
INSERT INTO `stock_movements` VALUES (1,1,3,2,'in',100,'App\\Models\\Purchase',1,'Penerimaan INV-SEED-001','2026-10-25 09:00:00','2026-10-28 06:42:32','2026-10-28 06:42:32'),(2,1,4,2,'in',50,'App\\Models\\Purchase',1,'Penerimaan INV-SEED-001','2026-10-25 09:00:00','2026-10-28 06:42:32','2026-10-28 06:42:32'),(3,1,9,2,'in',20,'App\\Models\\Purchase',1,'Penerimaan INV-SEED-001','2026-10-25 09:00:00','2026-10-28 06:42:32','2026-10-28 06:42:32'),(4,1,1,2,'in',10,'App\\Models\\Purchase',2,'Penerimaan INV-SEED-002','2026-10-21 09:00:00','2026-10-28 06:42:32','2026-10-28 06:42:32'),(5,1,2,2,'in',5,'App\\Models\\Purchase',2,'Penerimaan INV-SEED-002','2026-10-21 09:00:00','2026-10-28 06:42:32','2026-10-28 06:42:32'),(6,2,5,3,'in',30,'App\\Models\\Purchase',3,'Penerimaan INV-SEED-003','2026-10-18 09:00:00','2026-10-28 06:42:32','2026-10-28 06:42:32'),(7,2,6,3,'in',12,'App\\Models\\Purchase',3,'Penerimaan INV-SEED-003','2026-10-18 09:00:00','2026-10-28 06:42:32','2026-10-28 06:42:32'),(8,2,8,3,'in',8,'App\\Models\\Purchase',4,'Penerimaan INV-SEED-004','2026-10-27 09:00:00','2026-10-28 06:42:32','2026-10-28 06:42:32'),(9,2,7,3,'in',10,'App\\Models\\Purchase',4,'Penerimaan INV-SEED-004','2026-10-27 09:00:00','2026-10-28 06:42:32','2026-10-28 06:42:32'),(10,1,8,1,'in',6,'App\\Models\\Purchase',5,'Penerimaan INV-SEED-005','2026-10-26 10:00:00','2026-10-28 06:42:32','2026-10-28 06:42:32'),(11,1,3,2,'adjustment',-4,'App\\Models\\StockOpname',1,'Penyesuaian stock opname 2026-10-26','2026-10-28 13:42:32','2026-10-28 06:42:32','2026-10-28 06:42:32'),(12,1,9,2,'adjustment',2,'App\\Models\\StockOpname',1,'Penyesuaian stock opname 2026-10-26','2026-10-28 13:42:32','2026-10-28 06:42:32','2026-10-28 06:42:32');
/*!40000 ALTER TABLE `stock_movements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_opname_items`
--

DROP TABLE IF EXISTS `stock_opname_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_opname_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `stock_opname_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `system_qty` int NOT NULL,
  `counted_qty` int NOT NULL,
  `difference` int NOT NULL,
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_opname_items_stock_opname_id_product_id_unique` (`stock_opname_id`,`product_id`),
  KEY `stock_opname_items_product_id_foreign` (`product_id`),
  CONSTRAINT `stock_opname_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `stock_opname_items_stock_opname_id_foreign` FOREIGN KEY (`stock_opname_id`) REFERENCES `stock_opnames` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_opname_items`
--

LOCK TABLES `stock_opname_items` WRITE;
/*!40000 ALTER TABLE `stock_opname_items` DISABLE KEYS */;
INSERT INTO `stock_opname_items` VALUES (1,1,5,0,0,0,NULL,'2026-10-28 06:42:32','2026-10-28 06:42:32'),(2,1,7,0,0,0,NULL,'2026-10-28 06:42:32','2026-10-28 06:42:32'),(3,1,6,0,0,0,NULL,'2026-10-28 06:42:32','2026-10-28 06:42:32'),(4,1,1,10,10,0,NULL,'2026-10-28 06:42:32','2026-10-28 06:42:32'),(5,1,2,5,5,0,NULL,'2026-10-28 06:42:32','2026-10-28 06:42:32'),(6,1,8,6,6,0,NULL,'2026-10-28 06:42:32','2026-10-28 06:42:32'),(7,1,4,50,50,0,NULL,'2026-10-28 06:42:32','2026-10-28 06:42:32'),(8,1,3,100,96,-4,'Selisih hasil hitung fisik','2026-10-28 06:42:32','2026-10-28 06:42:32'),(9,1,9,20,22,2,'Selisih hasil hitung fisik','2026-10-28 06:42:32','2026-10-28 06:42:32');
/*!40000 ALTER TABLE `stock_opname_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_opnames`
--

DROP TABLE IF EXISTS `stock_opnames`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_opnames` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `outlet_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `week_start` date NOT NULL,
  `week_end` date NOT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_opnames_outlet_id_week_start_unique` (`outlet_id`,`week_start`),
  KEY `stock_opnames_user_id_foreign` (`user_id`),
  CONSTRAINT `stock_opnames_outlet_id_foreign` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `stock_opnames_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_opnames`
--

LOCK TABLES `stock_opnames` WRITE;
/*!40000 ALTER TABLE `stock_opnames` DISABLE KEYS */;
INSERT INTO `stock_opnames` VALUES (1,1,2,'2026-10-26','2026-11-01','2026-10-28 09:30:00','Opname rutin mingguan outlet pusat.','2026-10-28 06:42:32','2026-10-28 06:42:32');
/*!40000 ALTER TABLE `stock_opnames` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `suppliers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `suppliers_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (1,'PT Sumber Gas Nusantara','Hendra','021-7001111','Jl. Industri Gas No. 4, Bekasi','2026-10-28 06:42:32','2026-10-28 06:42:32'),(2,'CV Aroma Kemas Nusantara','Lestari','021-7002222','Jl. Kemasan Raya No. 21, Tangerang','2026-10-28 06:42:32','2026-10-28 06:42:32');
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `outlet_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('owner','manajer') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manajer',
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_outlet_id_foreign` (`outlet_id`),
  KEY `users_role_is_active_index` (`role`,`is_active`),
  CONSTRAINT `users_outlet_id_foreign` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,NULL,'Chandra TW','owner@gudangku.test',NULL,'$2y$12$.avotegFIQ/I01DNrG1cRe8yEAHOnGi5YUo9O6vN3lVtEGnQcukIO','owner',1,NULL,'2026-10-28 06:42:31','2026-10-28 06:42:34'),(2,1,'Rina Manajer Pusat','manajer.pusat@gudangku.test',NULL,'$2y$12$VzbMD7jc.XULXSv7gvLSVe5M2a5Rcvk.EzwZTUekhmITqJTN0aiR.','manajer',1,NULL,'2026-10-28 06:42:31','2026-10-28 06:42:34'),(3,2,'Budi Manajer Cabang','manajer.cabang@gudangku.test',NULL,'$2y$12$R4mNaQbkfdfQUAHYPx6pEu1h5sbYH9tP00N6aYm4fA.6z7A0ip5ci','manajer',1,NULL,'2026-10-28 06:42:31','2026-10-28 06:42:34'),(4,NULL,'Sari Pendaftar Baru','manajer.baru@gudangku.test',NULL,'$2y$12$lgEimMhdit.NpRT3qNhoWugUodTuXOUvu.Bw/FDEge/L1GNzEW6EW','manajer',0,NULL,'2026-10-28 06:42:32','2026-10-28 06:42:34');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
SET @@SESSION.SQL_LOG_BIN = @MYSQLDUMP_TEMP_LOG_BIN;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-29  6:27:55
