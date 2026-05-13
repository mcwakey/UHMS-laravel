/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `account_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `account_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `account_categories_type_index` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `log_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `causer_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `causer_id` bigint(20) unsigned DEFAULT NULL,
  `properties` longtext COLLATE utf8mb4_unicode_ci,
  `batch_uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subject` (`subject_type`,`subject_id`),
  KEY `causer` (`causer_type`,`causer_id`),
  KEY `activity_log_log_name_index` (`log_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `admissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `admission_number` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `visit_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `bed_id` bigint(20) unsigned NOT NULL,
  `admitted_by` bigint(20) unsigned NOT NULL,
  `admitting_diagnosis` text COLLATE utf8mb4_unicode_ci,
  `admission_date` datetime NOT NULL,
  `expected_discharge_date` date DEFAULT NULL,
  `actual_discharge_date` datetime DEFAULT NULL,
  `discharged_by` bigint(20) unsigned DEFAULT NULL,
  `discharge_summary` text COLLATE utf8mb4_unicode_ci,
  `discharge_instructions` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admitted',
  `admission_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admission',
  `admission_fee_service_id` bigint(20) unsigned DEFAULT NULL,
  `consumable_fee_service_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admissions_admission_number_unique` (`admission_number`),
  KEY `admissions_visit_id_foreign` (`visit_id`),
  KEY `admissions_patient_id_foreign` (`patient_id`),
  KEY `admissions_bed_id_foreign` (`bed_id`),
  KEY `admissions_admitted_by_foreign` (`admitted_by`),
  KEY `admissions_discharged_by_foreign` (`discharged_by`),
  KEY `admissions_status_index` (`status`),
  KEY `admissions_admission_date_index` (`admission_date`),
  KEY `admissions_admission_fee_service_id_foreign` (`admission_fee_service_id`),
  KEY `admissions_consumable_fee_service_id_foreign` (`consumable_fee_service_id`),
  CONSTRAINT `admissions_admission_fee_service_id_foreign` FOREIGN KEY (`admission_fee_service_id`) REFERENCES `service_catalog` (`id`) ON DELETE SET NULL,
  CONSTRAINT `admissions_admitted_by_foreign` FOREIGN KEY (`admitted_by`) REFERENCES `users` (`id`),
  CONSTRAINT `admissions_bed_id_foreign` FOREIGN KEY (`bed_id`) REFERENCES `beds` (`id`),
  CONSTRAINT `admissions_consumable_fee_service_id_foreign` FOREIGN KEY (`consumable_fee_service_id`) REFERENCES `service_catalog` (`id`) ON DELETE SET NULL,
  CONSTRAINT `admissions_discharged_by_foreign` FOREIGN KEY (`discharged_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `admissions_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `admissions_visit_id_foreign` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `anaesthesia_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `anaesthesia_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `procedure_request_id` bigint(20) unsigned NOT NULL,
  `anaesthetist_id` bigint(20) unsigned NOT NULL,
  `anaesthesia_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pre_assessment` text COLLATE utf8mb4_unicode_ci,
  `drugs_used` text COLLATE utf8mb4_unicode_ci,
  `dosage_notes` text COLLATE utf8mb4_unicode_ci,
  `airway_management` text COLLATE utf8mb4_unicode_ci,
  `monitoring_notes` text COLLATE utf8mb4_unicode_ci,
  `complications` text COLLATE utf8mb4_unicode_ci,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `anaesthesia_notes_procedure_request_id_foreign` (`procedure_request_id`),
  KEY `anaesthesia_notes_anaesthetist_id_foreign` (`anaesthetist_id`),
  CONSTRAINT `anaesthesia_notes_anaesthetist_id_foreign` FOREIGN KEY (`anaesthetist_id`) REFERENCES `users` (`id`),
  CONSTRAINT `anaesthesia_notes_procedure_request_id_foreign` FOREIGN KEY (`procedure_request_id`) REFERENCES `procedure_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `analyzer_raw_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `analyzer_raw_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `analyzer_id` bigint(20) unsigned DEFAULT NULL,
  `protocol` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `direction` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'inbound',
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_hash` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `processing_status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'received',
  `processing_attempts` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `sample_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `received_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `analyzer_raw_messages_analyzer_id_processing_status_index` (`analyzer_id`,`processing_status`),
  KEY `analyzer_raw_messages_content_hash_index` (`content_hash`),
  KEY `analyzer_raw_messages_sample_id_index` (`sample_id`),
  CONSTRAINT `analyzer_raw_messages_analyzer_id_foreign` FOREIGN KEY (`analyzer_id`) REFERENCES `analyzers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `analyzer_test_mappings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `analyzer_test_mappings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `analyzer_id` bigint(20) unsigned NOT NULL,
  `analyzer_test_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lab_test_id` bigint(20) unsigned NOT NULL,
  `unit_conversion_factor` decimal(10,4) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `analyzer_test_code_unique` (`analyzer_id`,`analyzer_test_code`),
  KEY `analyzer_test_mappings_lab_test_id_foreign` (`lab_test_id`),
  CONSTRAINT `analyzer_test_mappings_analyzer_id_foreign` FOREIGN KEY (`analyzer_id`) REFERENCES `analyzers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `analyzer_test_mappings_lab_test_id_foreign` FOREIGN KEY (`lab_test_id`) REFERENCES `lab_tests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `analyzers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `analyzers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `manufacturer` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `protocol` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'hl7',
  `connection_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'tcp',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `port` int(10) unsigned DEFAULT NULL,
  `com_port` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `baud_rate` int(10) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `last_connected_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `appointment_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `appointment_services` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `appointment_id` bigint(20) unsigned NOT NULL,
  `service_catalog_id` bigint(20) unsigned NOT NULL,
  `quantity` smallint(5) unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `appointment_services_appointment_id_service_catalog_id_unique` (`appointment_id`,`service_catalog_id`),
  KEY `appointment_services_service_catalog_id_foreign` (`service_catalog_id`),
  CONSTRAINT `appointment_services_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointment_services_service_catalog_id_foreign` FOREIGN KEY (`service_catalog_id`) REFERENCES `service_catalog` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `appointments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `appointments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `appointment_number` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `doctor_id` bigint(20) unsigned DEFAULT NULL,
  `department_id` bigint(20) unsigned NOT NULL,
  `appointment_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time DEFAULT NULL,
  `visit_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'outpatient',
  `priority` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `chief_complaint` text COLLATE utf8mb4_unicode_ci,
  `consultation_mode` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'in_person',
  `visit_insurance_id` bigint(20) unsigned DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'scheduled',
  `visit_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancellation_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `appointments_appointment_number_unique` (`appointment_number`),
  KEY `appointments_department_id_foreign` (`department_id`),
  KEY `appointments_visit_id_foreign` (`visit_id`),
  KEY `appointments_created_by_foreign` (`created_by`),
  KEY `appointments_cancelled_by_foreign` (`cancelled_by`),
  KEY `appointments_appointment_date_status_index` (`appointment_date`,`status`),
  KEY `appointments_doctor_id_appointment_date_index` (`doctor_id`,`appointment_date`),
  KEY `appointments_patient_id_appointment_date_index` (`patient_id`,`appointment_date`),
  KEY `appointments_visit_insurance_id_foreign` (`visit_insurance_id`),
  KEY `idx_appt_patient` (`patient_id`),
  KEY `idx_appt_doctor_date` (`doctor_id`,`appointment_date`),
  KEY `idx_appt_status` (`status`),
  CONSTRAINT `appointments_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `appointments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `appointments_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_visit_id_foreign` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE SET NULL,
  CONSTRAINT `appointments_visit_insurance_id_foreign` FOREIGN KEY (`visit_insurance_id`) REFERENCES `patient_insurances` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `beds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `beds` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ward_id` bigint(20) unsigned NOT NULL,
  `bed_number` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bed_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'standard',
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'available',
  `daily_rate` decimal(10,2) NOT NULL DEFAULT '0.00',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `beds_ward_id_bed_number_unique` (`ward_id`,`bed_number`),
  KEY `beds_status_index` (`status`),
  KEY `beds_bed_type_index` (`bed_type`),
  CONSTRAINT `beds_ward_id_foreign` FOREIGN KEY (`ward_id`) REFERENCES `wards` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cashier_shifts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cashier_shifts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `shift_date` date NOT NULL,
  `started_at` datetime NOT NULL,
  `ended_at` datetime DEFAULT NULL,
  `opening_balance` decimal(10,2) NOT NULL DEFAULT '0.00',
  `expected_closing` decimal(10,2) DEFAULT NULL,
  `actual_closing` decimal(10,2) DEFAULT NULL,
  `variance` decimal(10,2) DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `verified_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cashier_shifts_verified_by_foreign` (`verified_by`),
  KEY `cashier_shifts_user_id_shift_date_index` (`user_id`,`shift_date`),
  KEY `cashier_shifts_status_index` (`status`),
  CONSTRAINT `cashier_shifts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `cashier_shifts_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `claim_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `claim_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `claim_id` bigint(20) unsigned NOT NULL,
  `service_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `service_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other',
  `quantity` int(11) NOT NULL DEFAULT '1',
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `approved_amount` decimal(10,2) DEFAULT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `claim_items_claim_id_foreign` (`claim_id`),
  CONSTRAINT `claim_items_claim_id_foreign` FOREIGN KEY (`claim_id`) REFERENCES `claims` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `claims`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `claims` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `claim_number` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `insurance_provider_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `visit_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `insurance_verification_id` bigint(20) unsigned DEFAULT NULL,
  `verification_reference` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `claim_date` date NOT NULL,
  `period_from` date NOT NULL,
  `period_to` date NOT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `approved_amount` decimal(12,2) DEFAULT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `submitted_at` datetime DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `reviewer_notes` text COLLATE utf8mb4_unicode_ci,
  `assigned_doctor_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `claims_claim_number_unique` (`claim_number`),
  KEY `claims_visit_id_foreign` (`visit_id`),
  KEY `claims_invoice_id_foreign` (`invoice_id`),
  KEY `claims_assigned_doctor_id_foreign` (`assigned_doctor_id`),
  KEY `claims_created_by_foreign` (`created_by`),
  KEY `claims_status_claim_date_index` (`status`,`claim_date`),
  KEY `claims_insurance_provider_id_status_index` (`insurance_provider_id`,`status`),
  KEY `claims_patient_id_claim_date_index` (`patient_id`,`claim_date`),
  KEY `idx_claims_status` (`status`),
  KEY `idx_claims_patient` (`patient_id`),
  KEY `claims_insurance_verification_id_foreign` (`insurance_verification_id`),
  CONSTRAINT `claims_assigned_doctor_id_foreign` FOREIGN KEY (`assigned_doctor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `claims_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `claims_insurance_provider_id_foreign` FOREIGN KEY (`insurance_provider_id`) REFERENCES `insurance_providers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `claims_insurance_verification_id_foreign` FOREIGN KEY (`insurance_verification_id`) REFERENCES `insurance_verifications` (`id`) ON DELETE SET NULL,
  CONSTRAINT `claims_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `claims_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `claims_visit_id_foreign` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `complaints`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `complaints` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `medical_record_id` bigint(20) unsigned NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `duration` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `severity` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `complaints_medical_record_id_foreign` (`medical_record_id`),
  CONSTRAINT `complaints_medical_record_id_foreign` FOREIGN KEY (`medical_record_id`) REFERENCES `medical_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `consultation_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `consultation_tasks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `medical_record_id` bigint(20) unsigned NOT NULL,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `priority` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `consultation_tasks_created_by_foreign` (`created_by`),
  KEY `consultation_tasks_medical_record_id_status_index` (`medical_record_id`,`status`),
  KEY `consultation_tasks_assigned_to_index` (`assigned_to`),
  CONSTRAINT `consultation_tasks_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `consultation_tasks_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `consultation_tasks_medical_record_id_foreign` FOREIGN KEY (`medical_record_id`) REFERENCES `medical_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `departments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `result_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none',
  `is_stock_managed` tinyint(1) NOT NULL DEFAULT '0',
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `departments_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `designations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `designations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `designations_department_id_foreign` (`department_id`),
  CONSTRAINT `designations_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `diagnoses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `diagnoses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `medical_record_id` bigint(20) unsigned NOT NULL,
  `icd_code` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icd_code_id` bigint(20) unsigned DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'provisional',
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `diagnoses_medical_record_id_foreign` (`medical_record_id`),
  KEY `diagnoses_icd_code_id_foreign` (`icd_code_id`),
  KEY `diagnoses_type_index` (`type`),
  KEY `diagnoses_icd_code_index` (`icd_code`),
  CONSTRAINT `diagnoses_icd_code_id_foreign` FOREIGN KEY (`icd_code_id`) REFERENCES `icd_codes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `diagnoses_medical_record_id_foreign` FOREIGN KEY (`medical_record_id`) REFERENCES `medical_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dispensing_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dispensing_records` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `prescription_id` bigint(20) unsigned NOT NULL,
  `prescription_item_id` bigint(20) unsigned NOT NULL,
  `drug_stock_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `visit_id` bigint(20) unsigned NOT NULL,
  `quantity_dispensed` int(11) NOT NULL,
  `dispensed_by` bigint(20) unsigned NOT NULL,
  `dispensed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dispensing_records_prescription_id_foreign` (`prescription_id`),
  KEY `dispensing_records_prescription_item_id_foreign` (`prescription_item_id`),
  KEY `dispensing_records_drug_stock_id_foreign` (`drug_stock_id`),
  KEY `dispensing_records_patient_id_foreign` (`patient_id`),
  KEY `dispensing_records_visit_id_foreign` (`visit_id`),
  KEY `dispensing_records_dispensed_by_foreign` (`dispensed_by`),
  KEY `dispensing_records_dispensed_at_index` (`dispensed_at`),
  CONSTRAINT `dispensing_records_dispensed_by_foreign` FOREIGN KEY (`dispensed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `dispensing_records_drug_stock_id_foreign` FOREIGN KEY (`drug_stock_id`) REFERENCES `drug_stock` (`id`) ON DELETE CASCADE,
  CONSTRAINT `dispensing_records_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `dispensing_records_prescription_id_foreign` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `dispensing_records_prescription_item_id_foreign` FOREIGN KEY (`prescription_item_id`) REFERENCES `prescription_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `dispensing_records_visit_id_foreign` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `doctor_specialty`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doctor_specialty` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `specialty_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `doctor_specialty_user_id_specialty_id_unique` (`user_id`,`specialty_id`),
  KEY `doctor_specialty_specialty_id_foreign` (`specialty_id`),
  CONSTRAINT `doctor_specialty_specialty_id_foreign` FOREIGN KEY (`specialty_id`) REFERENCES `specialties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `doctor_specialty_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `drug_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `drug_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `drug_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `drug_stock` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `drug_id` bigint(20) unsigned NOT NULL,
  `location` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pharmacy',
  `batch_number` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_cost` decimal(10,2) NOT NULL,
  `selling_price` decimal(10,2) NOT NULL,
  `expiry_date` date NOT NULL,
  `supplier` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `supplier_id` bigint(20) unsigned DEFAULT NULL,
  `received_date` date NOT NULL,
  `received_by` bigint(20) unsigned NOT NULL,
  `reorder_level` int(11) NOT NULL DEFAULT '10',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `drug_stock_drug_id_foreign` (`drug_id`),
  KEY `drug_stock_received_by_foreign` (`received_by`),
  KEY `drug_stock_supplier_id_foreign` (`supplier_id`),
  KEY `drug_stock_location_index` (`location`),
  CONSTRAINT `drug_stock_drug_id_foreign` FOREIGN KEY (`drug_id`) REFERENCES `drugs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `drug_stock_received_by_foreign` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `drug_stock_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `drugs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `drugs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint(20) unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `generic_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `brand_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dosage_form` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `strength` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unit` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `opening_stock` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `reorder_level` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `requires_prescription` tinyint(1) NOT NULL DEFAULT '1',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `drugs_category_id_foreign` (`category_id`),
  CONSTRAINT `drugs_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `drug_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `emergency_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `emergency_contacts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` bigint(20) unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_secondary` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `relationship` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `emergency_contacts_patient_id_index` (`patient_id`),
  CONSTRAINT `emergency_contacts_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `employee_attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_attendance` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `date` date NOT NULL,
  `clock_in` time DEFAULT NULL,
  `clock_out` time DEFAULT NULL,
  `hours_worked` decimal(4,2) DEFAULT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'present',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_attendance_employee_id_date_unique` (`employee_id`,`date`),
  KEY `employee_attendance_date_index` (`date`),
  CONSTRAINT `employee_attendance_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employees` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_number` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `first_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gender` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `phone` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `position` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hire_date` date NOT NULL,
  `basic_salary` decimal(10,2) NOT NULL DEFAULT '0.00',
  `bank_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_branch` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ssnit_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tin_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_phone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employees_employee_number_unique` (`employee_number`),
  KEY `employees_user_id_foreign` (`user_id`),
  KEY `employees_department_id_index` (`department_id`),
  KEY `employees_status_index` (`status`),
  CONSTRAINT `employees_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employees_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `financial_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `financial_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_number` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `entry_date` date NOT NULL,
  `recorded_by` bigint(20) unsigned NOT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `financial_entries_entry_number_unique` (`entry_number`),
  KEY `financial_entries_recorded_by_foreign` (`recorded_by`),
  KEY `financial_entries_approved_by_foreign` (`approved_by`),
  KEY `financial_entries_type_entry_date_index` (`type`,`entry_date`),
  KEY `financial_entries_category_id_type_index` (`category_id`,`type`),
  CONSTRAINT `financial_entries_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  CONSTRAINT `financial_entries_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `account_categories` (`id`),
  CONSTRAINT `financial_entries_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `icd_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `icd_codes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `chapter` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_billable` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `icd_codes_code_unique` (`code`),
  KEY `icd_codes_code_index` (`code`),
  KEY `icd_codes_description_index` (`description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `insurance_providers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `insurance_providers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `short_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'public',
  `contact_phone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `contract_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `verification_driver` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verification_method` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verification_channel` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verification_config` longtext COLLATE utf8mb4_unicode_ci,
  `verification_credentials_key` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `insurance_tiers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `insurance_tiers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `insurance_provider_id` bigint(20) unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT '0',
  `coverage_percentage` decimal(5,2) NOT NULL DEFAULT '100.00',
  `per_visit_limit` decimal(12,2) DEFAULT NULL,
  `annual_limit` decimal(12,2) DEFAULT NULL,
  `max_per_month` decimal(12,2) DEFAULT NULL,
  `max_visits_per_month` smallint(5) unsigned DEFAULT NULL,
  `min_visit_interval_days` smallint(5) unsigned DEFAULT NULL,
  `max_beneficiaries` smallint(5) unsigned DEFAULT NULL,
  `holder_per_visit_limit` decimal(12,2) DEFAULT NULL,
  `holder_annual_limit` decimal(12,2) DEFAULT NULL,
  `holder_max_per_month` decimal(12,2) DEFAULT NULL,
  `holder_max_visits_per_month` smallint(5) unsigned DEFAULT NULL,
  `beneficiary_per_visit_limit` decimal(12,2) DEFAULT NULL,
  `beneficiary_annual_limit` decimal(12,2) DEFAULT NULL,
  `beneficiary_max_per_month` decimal(12,2) DEFAULT NULL,
  `beneficiary_max_visits_per_month` smallint(5) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `insurance_tiers_insurance_provider_id_foreign` (`insurance_provider_id`),
  CONSTRAINT `insurance_tiers_insurance_provider_id_foreign` FOREIGN KEY (`insurance_provider_id`) REFERENCES `insurance_providers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `insurance_usages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `insurance_usages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `patient_insurance_id` bigint(20) unsigned NOT NULL,
  `visit_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `amount_covered` decimal(12,2) NOT NULL DEFAULT '0.00',
  `patient_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `reason` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `insurance_usages_visit_id_foreign` (`visit_id`),
  KEY `insurance_usages_patient_insurance_id_visit_id_index` (`patient_insurance_id`,`visit_id`),
  KEY `insurance_usages_invoice_id_index` (`invoice_id`),
  CONSTRAINT `insurance_usages_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `insurance_usages_patient_insurance_id_foreign` FOREIGN KEY (`patient_insurance_id`) REFERENCES `patient_insurances` (`id`) ON DELETE CASCADE,
  CONSTRAINT `insurance_usages_visit_id_foreign` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `insurance_verifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `insurance_verifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `patient_insurance_id` bigint(20) unsigned NOT NULL,
  `insurance_provider_id` bigint(20) unsigned NOT NULL,
  `visit_id` bigint(20) unsigned DEFAULT NULL,
  `driver` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_code` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `membership_number` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `member_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expires_at` date DEFAULT NULL,
  `verified_by` bigint(20) unsigned DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci,
  `message` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `insurance_verifications_verified_by_foreign` (`verified_by`),
  KEY `insurance_verifications_patient_insurance_id_status_index` (`patient_insurance_id`,`status`),
  KEY `insurance_verifications_visit_id_index` (`visit_id`),
  KEY `insurance_verifications_insurance_provider_id_verified_at_index` (`insurance_provider_id`,`verified_at`),
  CONSTRAINT `insurance_verifications_insurance_provider_id_foreign` FOREIGN KEY (`insurance_provider_id`) REFERENCES `insurance_providers` (`id`),
  CONSTRAINT `insurance_verifications_patient_insurance_id_foreign` FOREIGN KEY (`patient_insurance_id`) REFERENCES `patient_insurances` (`id`) ON DELETE CASCADE,
  CONSTRAINT `insurance_verifications_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `insurance_verifications_visit_id_foreign` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `investigation_criteria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `investigation_criteria` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `service_id` bigint(20) unsigned NOT NULL,
  `header_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_range` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `default_value` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `options` text COLLATE utf8mb4_unicode_ci,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT '0',
  `is_required` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `investigation_criteria_header_id_foreign` (`header_id`),
  KEY `investigation_criteria_service_id_header_id_sort_order_index` (`service_id`,`header_id`,`sort_order`),
  CONSTRAINT `investigation_criteria_header_id_foreign` FOREIGN KEY (`header_id`) REFERENCES `investigation_headers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `investigation_criteria_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `service_catalog` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `investigation_headers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `investigation_headers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `service_id` bigint(20) unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `investigation_headers_service_id_sort_order_index` (`service_id`,`sort_order`),
  CONSTRAINT `investigation_headers_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `service_catalog` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `investigation_item_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `investigation_item_stock` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `investigation_item_id` bigint(20) unsigned NOT NULL,
  `location` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'laboratory',
  `batch_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT '0',
  `unit_cost` decimal(10,2) NOT NULL DEFAULT '0.00',
  `expiry_date` date DEFAULT NULL,
  `supplier` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `supplier_id` bigint(20) unsigned DEFAULT NULL,
  `received_date` date DEFAULT NULL,
  `received_by` bigint(20) unsigned DEFAULT NULL,
  `reorder_level` int(11) NOT NULL DEFAULT '10',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `investigation_item_stock_investigation_item_id_foreign` (`investigation_item_id`),
  KEY `investigation_item_stock_supplier_id_foreign` (`supplier_id`),
  KEY `investigation_item_stock_received_by_foreign` (`received_by`),
  CONSTRAINT `investigation_item_stock_investigation_item_id_foreign` FOREIGN KEY (`investigation_item_id`) REFERENCES `investigation_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `investigation_item_stock_received_by_foreign` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `investigation_item_stock_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `investigation_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `investigation_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other',
  `unit` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unit',
  `reorder_level` int(11) NOT NULL DEFAULT '10',
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `investigation_items_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `investigation_result_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `investigation_result_values` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lab_result_id` bigint(20) unsigned NOT NULL,
  `criteria_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unit` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_range` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flag` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `investigation_result_values_criteria_id_foreign` (`criteria_id`),
  KEY `investigation_result_values_lab_result_id_sort_order_index` (`lab_result_id`,`sort_order`),
  CONSTRAINT `investigation_result_values_criteria_id_foreign` FOREIGN KEY (`criteria_id`) REFERENCES `investigation_criteria` (`id`) ON DELETE SET NULL,
  CONSTRAINT `investigation_result_values_lab_result_id_foreign` FOREIGN KEY (`lab_result_id`) REFERENCES `lab_results` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `investigations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `investigations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `medical_record_id` bigint(20) unsigned NOT NULL,
  `investigation_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `urgency` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'routine',
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'requested',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `investigations_medical_record_id_foreign` (`medical_record_id`),
  KEY `investigations_status_index` (`status`),
  KEY `investigations_investigation_type_index` (`investigation_type`),
  CONSTRAINT `investigations_medical_record_id_foreign` FOREIGN KEY (`medical_record_id`) REFERENCES `medical_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoice_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `visit_id` bigint(20) unsigned DEFAULT NULL,
  `patient_id` bigint(20) unsigned DEFAULT NULL,
  `service_catalog_id` bigint(20) unsigned DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `source_type` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_id` bigint(20) unsigned DEFAULT NULL,
  `description` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT '1',
  `unit_price` decimal(10,2) DEFAULT NULL,
  `insurance_price` decimal(12,2) DEFAULT NULL,
  `insurance_covered` decimal(12,2) NOT NULL DEFAULT '0.00',
  `patient_payable` decimal(12,2) NOT NULL DEFAULT '0.00',
  `paid_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `balance` decimal(12,2) NOT NULL DEFAULT '0.00',
  `payment_status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unpaid',
  `cash_price` decimal(10,2) DEFAULT NULL,
  `selected_price` decimal(10,2) DEFAULT NULL,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `payer_type` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `insurance_provider_id` bigint(20) unsigned DEFAULT NULL,
  `patient_insurance_id` bigint(20) unsigned DEFAULT NULL,
  `insurance_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `pricing_source` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `is_nhis_covered` tinyint(1) NOT NULL DEFAULT '0',
  `nhis_approved_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_items_invoice_id_foreign` (`invoice_id`),
  KEY `invoice_items_service_catalog_id_foreign` (`service_catalog_id`),
  KEY `invoice_items_source_idx` (`source_type`,`source_id`),
  KEY `invoice_items_visit_id_idx` (`visit_id`),
  KEY `invoice_items_patient_id_idx` (`patient_id`),
  KEY `invoice_items_payment_status_idx` (`payment_status`),
  CONSTRAINT `invoice_items_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoice_items_service_catalog_id_foreign` FOREIGN KEY (`service_catalog_id`) REFERENCES `service_catalog` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `visit_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `billing_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `nhis_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `amount_paid` decimal(10,2) NOT NULL DEFAULT '0.00',
  `balance` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `due_date` date DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `active_visit_id` bigint(20) AS (
                    CASE WHEN status IN ('cancelled','refunded') THEN NULL ELSE visit_id END) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoices_invoice_number_unique` (`invoice_number`),
  KEY `invoices_created_by_foreign` (`created_by`),
  KEY `invoices_status_index` (`status`),
  KEY `invoices_billing_type_index` (`billing_type`),
  KEY `invoices_due_date_index` (`due_date`),
  KEY `idx_invoices_patient` (`patient_id`),
  KEY `idx_invoices_status` (`status`),
  KEY `idx_invoices_created` (`created_at`),
  KEY `idx_invoices_visit_id` (`visit_id`),
  CONSTRAINT `invoices_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoices_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoices_visit_id_foreign` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lab_request_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lab_request_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lab_request_id` bigint(20) unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lab_test_id` bigint(20) unsigned DEFAULT NULL,
  `service_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `accepted_at` timestamp NULL DEFAULT NULL,
  `accepted_by` bigint(20) unsigned DEFAULT NULL,
  `billed_at` timestamp NULL DEFAULT NULL,
  `invoice_item_id` bigint(20) unsigned DEFAULT NULL,
  `unit_price` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lab_request_items_lab_request_id_foreign` (`lab_request_id`),
  KEY `lab_request_items_status_index` (`status`),
  KEY `lab_request_items_lab_test_id_foreign` (`lab_test_id`),
  KEY `lab_request_items_service_id_foreign` (`service_id`),
  KEY `lab_request_items_accepted_by_foreign` (`accepted_by`),
  KEY `lab_request_items_invoice_item_id_foreign` (`invoice_item_id`),
  CONSTRAINT `lab_request_items_accepted_by_foreign` FOREIGN KEY (`accepted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_request_items_invoice_item_id_foreign` FOREIGN KEY (`invoice_item_id`) REFERENCES `invoice_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_request_items_lab_request_id_foreign` FOREIGN KEY (`lab_request_id`) REFERENCES `lab_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lab_request_items_lab_test_id_foreign` FOREIGN KEY (`lab_test_id`) REFERENCES `lab_tests` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_request_items_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `service_catalog` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lab_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lab_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `request_number` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sample_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `visit_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `requested_by` bigint(20) unsigned NOT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `target_department_id` bigint(20) unsigned DEFAULT NULL,
  `clinical_info` text COLLATE utf8mb4_unicode_ci,
  `urgency` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'routine',
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lab_requests_request_number_unique` (`request_number`),
  KEY `lab_requests_patient_id_foreign` (`patient_id`),
  KEY `lab_requests_requested_by_foreign` (`requested_by`),
  KEY `lab_requests_department_id_foreign` (`department_id`),
  KEY `lab_requests_sample_id_index` (`sample_id`),
  KEY `lab_requests_status_index` (`status`),
  KEY `lab_requests_urgency_index` (`urgency`),
  KEY `lab_requests_target_department_id_index` (`target_department_id`),
  KEY `idx_labreq_visit` (`visit_id`),
  KEY `idx_labreq_status` (`status`),
  KEY `idx_labreq_target_dept` (`target_department_id`),
  CONSTRAINT `lab_requests_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_requests_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lab_requests_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`),
  CONSTRAINT `lab_requests_target_department_id_foreign` FOREIGN KEY (`target_department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_requests_visit_id_foreign` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lab_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lab_results` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lab_request_item_id` bigint(20) unsigned DEFAULT NULL,
  `lab_request_id` bigint(20) unsigned NOT NULL,
  `result_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'parameters',
  `result_value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `result_text` longtext COLLATE utf8mb4_unicode_ci,
  `result_file` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `result_file_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_abnormal` tinyint(1) NOT NULL DEFAULT '0',
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `performed_by` bigint(20) unsigned NOT NULL,
  `verified_by` bigint(20) unsigned DEFAULT NULL,
  `performed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lab_results_lab_request_id_foreign` (`lab_request_id`),
  KEY `lab_results_performed_by_foreign` (`performed_by`),
  KEY `lab_results_verified_by_foreign` (`verified_by`),
  KEY `lab_results_performed_at_index` (`performed_at`),
  KEY `lab_results_verified_at_index` (`verified_at`),
  KEY `lab_results_is_abnormal_index` (`is_abnormal`),
  KEY `lab_results_lab_request_item_id_foreign` (`lab_request_item_id`),
  CONSTRAINT `lab_results_lab_request_id_foreign` FOREIGN KEY (`lab_request_id`) REFERENCES `lab_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lab_results_lab_request_item_id_foreign` FOREIGN KEY (`lab_request_item_id`) REFERENCES `lab_request_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_results_performed_by_foreign` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`),
  CONSTRAINT `lab_results_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lab_test_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lab_test_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lab_test_categories_department_id_foreign` (`department_id`),
  CONSTRAINT `lab_test_categories_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lab_test_criteria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lab_test_criteria` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lab_test_id` bigint(20) unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `normal_range` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unit` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lab_test_criteria_lab_test_id_foreign` (`lab_test_id`),
  CONSTRAINT `lab_test_criteria_lab_test_id_foreign` FOREIGN KEY (`lab_test_id`) REFERENCES `lab_tests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lab_tests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lab_tests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint(20) unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `normal_range` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unit` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description_template` text COLLATE utf8mb4_unicode_ci,
  `price` decimal(10,2) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lab_tests_code_unique` (`code`),
  KEY `lab_tests_category_id_foreign` (`category_id`),
  CONSTRAINT `lab_tests_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `lab_test_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `leave_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leave_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `leave_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `days` int(11) NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leave_requests_approved_by_foreign` (`approved_by`),
  KEY `leave_requests_employee_id_status_index` (`employee_id`,`status`),
  KEY `leave_requests_leave_type_index` (`leave_type`),
  CONSTRAINT `leave_requests_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leave_requests_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `medical_pattern_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `medical_pattern_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `medical_pattern_id` bigint(20) unsigned NOT NULL,
  `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `medical_pattern_items_medical_pattern_id_foreign` (`medical_pattern_id`),
  CONSTRAINT `medical_pattern_items_medical_pattern_id_foreign` FOREIGN KEY (`medical_pattern_id`) REFERENCES `medical_patterns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `medical_patterns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `medical_patterns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `doctor_id` bigint(20) unsigned DEFAULT NULL,
  `usage_count` int(10) unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `medical_patterns_doctor_id_foreign` (`doctor_id`),
  CONSTRAINT `medical_patterns_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `medical_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `medical_records` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `visit_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `doctor_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `medical_records_visit_id_foreign` (`visit_id`),
  KEY `medical_records_patient_id_foreign` (`patient_id`),
  KEY `medical_records_doctor_id_foreign` (`doctor_id`),
  CONSTRAINT `medical_records_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `medical_records_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `medical_records_visit_id_foreign` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `modules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_core` tinyint(1) NOT NULL DEFAULT '0',
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `depends_on` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `modules_slug_unique` (`slug`),
  KEY `modules_is_enabled_is_core_index` (`is_enabled`,`is_core`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint(20) unsigned NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `operative_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `operative_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `procedure_request_id` bigint(20) unsigned NOT NULL,
  `surgeon_id` bigint(20) unsigned NOT NULL,
  `assistant_surgeon_id` bigint(20) unsigned DEFAULT NULL,
  `procedure_performed` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pre_op_diagnosis` text COLLATE utf8mb4_unicode_ci,
  `post_op_diagnosis` text COLLATE utf8mb4_unicode_ci,
  `findings` text COLLATE utf8mb4_unicode_ci,
  `incision` text COLLATE utf8mb4_unicode_ci,
  `technique` text COLLATE utf8mb4_unicode_ci,
  `blood_loss` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `complications` text COLLATE utf8mb4_unicode_ci,
  `specimens` text COLLATE utf8mb4_unicode_ci,
  `implants` text COLLATE utf8mb4_unicode_ci,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `outcome` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `operative_notes_procedure_request_id_foreign` (`procedure_request_id`),
  KEY `operative_notes_surgeon_id_foreign` (`surgeon_id`),
  KEY `operative_notes_assistant_surgeon_id_foreign` (`assistant_surgeon_id`),
  CONSTRAINT `operative_notes_assistant_surgeon_id_foreign` FOREIGN KEY (`assistant_surgeon_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `operative_notes_procedure_request_id_foreign` FOREIGN KEY (`procedure_request_id`) REFERENCES `procedure_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `operative_notes_surgeon_id_foreign` FOREIGN KEY (`surgeon_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `patient_insurances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patient_insurances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` bigint(20) unsigned NOT NULL,
  `insurance_provider_id` bigint(20) unsigned NOT NULL,
  `insurance_tier_id` bigint(20) unsigned DEFAULT NULL,
  `member_type` enum('holder','beneficiary') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'holder',
  `card_holder_insurance_id` bigint(20) unsigned DEFAULT NULL,
  `membership_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `policy_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ccc_code` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `patient_insurance_unique` (`patient_id`,`insurance_provider_id`),
  KEY `patient_insurances_insurance_provider_id_foreign` (`insurance_provider_id`),
  KEY `patient_insurances_is_active_index` (`is_active`),
  KEY `patient_insurances_insurance_tier_id_foreign` (`insurance_tier_id`),
  KEY `patient_insurances_card_holder_insurance_id_foreign` (`card_holder_insurance_id`),
  KEY `patient_insurances_ccc_code_index` (`ccc_code`),
  CONSTRAINT `patient_insurances_card_holder_insurance_id_foreign` FOREIGN KEY (`card_holder_insurance_id`) REFERENCES `patient_insurances` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patient_insurances_insurance_provider_id_foreign` FOREIGN KEY (`insurance_provider_id`) REFERENCES `insurance_providers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_insurances_insurance_tier_id_foreign` FOREIGN KEY (`insurance_tier_id`) REFERENCES `insurance_tiers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patient_insurances_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `patient_procedures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patient_procedures` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `visit_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `procedure_id` bigint(20) unsigned NOT NULL,
  `performed_by` bigint(20) unsigned DEFAULT NULL,
  `scheduled_date` datetime NOT NULL,
  `performed_date` datetime DEFAULT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'scheduled',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `outcome` text COLLATE utf8mb4_unicode_ci,
  `consent_signed` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_procedures_procedure_id_foreign` (`procedure_id`),
  KEY `patient_procedures_performed_by_foreign` (`performed_by`),
  KEY `patient_procedures_visit_id_status_index` (`visit_id`,`status`),
  KEY `patient_procedures_patient_id_index` (`patient_id`),
  CONSTRAINT `patient_procedures_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_procedures_performed_by_foreign` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patient_procedures_procedure_id_foreign` FOREIGN KEY (`procedure_id`) REFERENCES `procedures` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_procedures_visit_id_foreign` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `patients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `patient_number` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `other_names` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date NOT NULL,
  `height` decimal(5,1) DEFAULT NULL COMMENT 'Height in cm',
  `gender` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `blood_group` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `marital_status` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `religion` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_secondary` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ghana_card_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `occupation` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `city` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `town` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `region` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `digital_address` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_phone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_relationship` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `allergies` text COLLATE utf8mb4_unicode_ci,
  `chronic_conditions` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `registered_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `patients_patient_number_unique` (`patient_number`),
  UNIQUE KEY `patients_ghana_card_number_unique` (`ghana_card_number`),
  KEY `patients_registered_by_foreign` (`registered_by`),
  KEY `patients_phone_index` (`phone`),
  KEY `patients_status_index` (`status`),
  KEY `idx_patients_phone` (`phone`),
  KEY `idx_patients_created` (`created_at`),
  CONSTRAINT `patients_registered_by_foreign` FOREIGN KEY (`registered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payment_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_allocations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payment_id` bigint(20) unsigned NOT NULL,
  `invoice_item_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payment_alloc_payment_idx` (`payment_id`),
  KEY `payment_alloc_item_idx` (`invoice_item_id`),
  CONSTRAINT `fk_pa_invoice_item` FOREIGN KEY (`invoice_item_id`) REFERENCES `invoice_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pa_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payment_number` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `received_by` bigint(20) unsigned NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `paid_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payments_payment_number_unique` (`payment_number`),
  KEY `payments_patient_id_foreign` (`patient_id`),
  KEY `payments_received_by_foreign` (`received_by`),
  KEY `payments_payment_method_index` (`payment_method`),
  KEY `payments_paid_at_index` (`paid_at`),
  KEY `idx_payments_invoice` (`invoice_id`),
  KEY `idx_payments_created` (`created_at`),
  CONSTRAINT `payments_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_received_by_foreign` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payroll_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_records` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `pay_period` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `basic_salary` decimal(10,2) NOT NULL,
  `allowances` decimal(10,2) NOT NULL DEFAULT '0.00',
  `gross_pay` decimal(10,2) NOT NULL,
  `ssnit_employee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `ssnit_employer` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tax` decimal(10,2) NOT NULL DEFAULT '0.00',
  `other_deductions` decimal(10,2) NOT NULL DEFAULT '0.00',
  `net_pay` decimal(10,2) NOT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `processed_by` bigint(20) unsigned DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payroll_records_employee_id_pay_period_unique` (`employee_id`,`pay_period`),
  KEY `payroll_records_processed_by_foreign` (`processed_by`),
  KEY `payroll_records_pay_period_index` (`pay_period`),
  KEY `payroll_records_status_index` (`status`),
  CONSTRAINT `payroll_records_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payroll_records_processed_by_foreign` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `post_op_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `post_op_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `procedure_request_id` bigint(20) unsigned NOT NULL,
  `recorded_by` bigint(20) unsigned NOT NULL,
  `recovery_status` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pain_score` tinyint(3) unsigned DEFAULT NULL,
  `consciousness_level` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `post_op_instructions` text COLLATE utf8mb4_unicode_ci,
  `medications` text COLLATE utf8mb4_unicode_ci,
  `complications` text COLLATE utf8mb4_unicode_ci,
  `transfer_destination` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `post_op_notes_procedure_request_id_foreign` (`procedure_request_id`),
  KEY `post_op_notes_recorded_by_foreign` (`recorded_by`),
  CONSTRAINT `post_op_notes_procedure_request_id_foreign` FOREIGN KEY (`procedure_request_id`) REFERENCES `procedure_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `post_op_notes_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prescription_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `prescription_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `prescription_id` bigint(20) unsigned NOT NULL,
  `drug_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `drug_id` bigint(20) unsigned DEFAULT NULL,
  `dosage` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `frequency` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `duration` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int(11) NOT NULL,
  `route` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'oral',
  `instructions` text COLLATE utf8mb4_unicode_ci,
  `is_dispensed` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `prescription_items_prescription_id_foreign` (`prescription_id`),
  KEY `prescription_items_drug_id_index` (`drug_id`),
  CONSTRAINT `prescription_items_prescription_id_foreign` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prescriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `prescriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `medical_record_id` bigint(20) unsigned NOT NULL,
  `visit_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `doctor_id` bigint(20) unsigned NOT NULL,
  `prescription_number` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `prescriptions_prescription_number_unique` (`prescription_number`),
  KEY `prescriptions_medical_record_id_foreign` (`medical_record_id`),
  KEY `prescriptions_patient_id_foreign` (`patient_id`),
  KEY `prescriptions_doctor_id_foreign` (`doctor_id`),
  KEY `prescriptions_status_index` (`status`),
  KEY `idx_rx_visit` (`visit_id`),
  KEY `idx_rx_status` (`status`),
  CONSTRAINT `prescriptions_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `prescriptions_medical_record_id_foreign` FOREIGN KEY (`medical_record_id`) REFERENCES `medical_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `prescriptions_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `prescriptions_visit_id_foreign` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `procedure_checklists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `procedure_checklists` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `procedure_request_id` bigint(20) unsigned NOT NULL,
  `consent_signed` tinyint(1) NOT NULL DEFAULT '0',
  `fasting_confirmed` tinyint(1) NOT NULL DEFAULT '0',
  `allergies_checked` tinyint(1) NOT NULL DEFAULT '0',
  `blood_available` tinyint(1) NOT NULL DEFAULT '0',
  `site_marked` tinyint(1) NOT NULL DEFAULT '0',
  `equipment_ready` tinyint(1) NOT NULL DEFAULT '0',
  `anaesthesia_review_done` tinyint(1) NOT NULL DEFAULT '0',
  `pre_op_diagnosis` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `completed_by` bigint(20) unsigned NOT NULL,
  `completed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `procedure_checklists_procedure_request_id_foreign` (`procedure_request_id`),
  KEY `procedure_checklists_completed_by_foreign` (`completed_by`),
  CONSTRAINT `procedure_checklists_completed_by_foreign` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`),
  CONSTRAINT `procedure_checklists_procedure_request_id_foreign` FOREIGN KEY (`procedure_request_id`) REFERENCES `procedure_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `procedure_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `procedure_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `request_number` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `visit_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `requested_by` bigint(20) unsigned NOT NULL,
  `department_id` bigint(20) unsigned NOT NULL,
  `service_catalog_id` bigint(20) unsigned DEFAULT NULL,
  `procedure_id` bigint(20) unsigned DEFAULT NULL,
  `priority` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'routine',
  `indication` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `preferred_datetime` timestamp NULL DEFAULT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'requested',
  `billing_item_id` bigint(20) unsigned DEFAULT NULL,
  `billed_at` timestamp NULL DEFAULT NULL,
  `billed_by` bigint(20) unsigned DEFAULT NULL,
  `accepted_by` bigint(20) unsigned DEFAULT NULL,
  `accepted_at` timestamp NULL DEFAULT NULL,
  `acceptance_notes` text COLLATE utf8mb4_unicode_ci,
  `rejected_by` bigint(20) unsigned DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancellation_reason` text COLLATE utf8mb4_unicode_ci,
  `completed_by` bigint(20) unsigned DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `requested_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `procedure_requests_request_number_unique` (`request_number`),
  KEY `procedure_requests_requested_by_foreign` (`requested_by`),
  KEY `procedure_requests_service_catalog_id_foreign` (`service_catalog_id`),
  KEY `procedure_requests_procedure_id_foreign` (`procedure_id`),
  KEY `procedure_requests_billing_item_id_foreign` (`billing_item_id`),
  KEY `procedure_requests_billed_by_foreign` (`billed_by`),
  KEY `procedure_requests_accepted_by_foreign` (`accepted_by`),
  KEY `procedure_requests_rejected_by_foreign` (`rejected_by`),
  KEY `procedure_requests_cancelled_by_foreign` (`cancelled_by`),
  KEY `procedure_requests_completed_by_foreign` (`completed_by`),
  KEY `procedure_requests_visit_id_status_index` (`visit_id`,`status`),
  KEY `procedure_requests_patient_id_status_index` (`patient_id`,`status`),
  KEY `procedure_requests_department_id_index` (`department_id`),
  KEY `procedure_requests_status_index` (`status`),
  CONSTRAINT `procedure_requests_accepted_by_foreign` FOREIGN KEY (`accepted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `procedure_requests_billed_by_foreign` FOREIGN KEY (`billed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `procedure_requests_billing_item_id_foreign` FOREIGN KEY (`billing_item_id`) REFERENCES `invoice_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `procedure_requests_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `procedure_requests_completed_by_foreign` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `procedure_requests_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  CONSTRAINT `procedure_requests_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `procedure_requests_procedure_id_foreign` FOREIGN KEY (`procedure_id`) REFERENCES `procedures` (`id`) ON DELETE SET NULL,
  CONSTRAINT `procedure_requests_rejected_by_foreign` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `procedure_requests_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`),
  CONSTRAINT `procedure_requests_service_catalog_id_foreign` FOREIGN KEY (`service_catalog_id`) REFERENCES `service_catalog` (`id`) ON DELETE SET NULL,
  CONSTRAINT `procedure_requests_visit_id_foreign` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `procedure_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `procedure_schedules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `procedure_request_id` bigint(20) unsigned NOT NULL,
  `theatre_room_id` bigint(20) unsigned DEFAULT NULL,
  `scheduled_start` datetime NOT NULL,
  `scheduled_end` datetime DEFAULT NULL,
  `surgeon_id` bigint(20) unsigned DEFAULT NULL,
  `anaesthetist_id` bigint(20) unsigned DEFAULT NULL,
  `assistant_surgeon_id` bigint(20) unsigned DEFAULT NULL,
  `theatre_nurse_ids` text COLLATE utf8mb4_unicode_ci,
  `required_equipment` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'scheduled',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `scheduled_by` bigint(20) unsigned NOT NULL,
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `procedure_schedules_theatre_room_id_foreign` (`theatre_room_id`),
  KEY `procedure_schedules_surgeon_id_foreign` (`surgeon_id`),
  KEY `procedure_schedules_anaesthetist_id_foreign` (`anaesthetist_id`),
  KEY `procedure_schedules_assistant_surgeon_id_foreign` (`assistant_surgeon_id`),
  KEY `procedure_schedules_scheduled_by_foreign` (`scheduled_by`),
  KEY `procedure_schedules_procedure_request_id_is_current_index` (`procedure_request_id`,`is_current`),
  KEY `procedure_schedules_scheduled_start_index` (`scheduled_start`),
  CONSTRAINT `procedure_schedules_anaesthetist_id_foreign` FOREIGN KEY (`anaesthetist_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `procedure_schedules_assistant_surgeon_id_foreign` FOREIGN KEY (`assistant_surgeon_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `procedure_schedules_procedure_request_id_foreign` FOREIGN KEY (`procedure_request_id`) REFERENCES `procedure_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `procedure_schedules_scheduled_by_foreign` FOREIGN KEY (`scheduled_by`) REFERENCES `users` (`id`),
  CONSTRAINT `procedure_schedules_surgeon_id_foreign` FOREIGN KEY (`surgeon_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `procedure_schedules_theatre_room_id_foreign` FOREIGN KEY (`theatre_room_id`) REFERENCES `theatre_rooms` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `procedure_status_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `procedure_status_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `procedure_request_id` bigint(20) unsigned NOT NULL,
  `from_status` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `to_status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `changed_by` bigint(20) unsigned DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `procedure_status_logs_changed_by_foreign` (`changed_by`),
  KEY `procedure_status_logs_procedure_request_id_index` (`procedure_request_id`),
  CONSTRAINT `procedure_status_logs_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `procedure_status_logs_procedure_request_id_foreign` FOREIGN KEY (`procedure_request_id`) REFERENCES `procedure_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `procedure_vitals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `procedure_vitals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `procedure_request_id` bigint(20) unsigned NOT NULL,
  `stage` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `temperature` decimal(5,2) DEFAULT NULL,
  `blood_pressure` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pulse` smallint(5) unsigned DEFAULT NULL,
  `respiratory_rate` smallint(5) unsigned DEFAULT NULL,
  `oxygen_saturation` smallint(5) unsigned DEFAULT NULL,
  `weight` decimal(6,2) DEFAULT NULL,
  `pain_score` tinyint(3) unsigned DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `recorded_by` bigint(20) unsigned NOT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `procedure_vitals_recorded_by_foreign` (`recorded_by`),
  KEY `procedure_vitals_procedure_request_id_stage_index` (`procedure_request_id`,`stage`),
  CONSTRAINT `procedure_vitals_procedure_request_id_foreign` FOREIGN KEY (`procedure_request_id`) REFERENCES `procedure_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `procedure_vitals_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `procedures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `procedures` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `category` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other',
  `description` text COLLATE utf8mb4_unicode_ci,
  `default_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `nhis_price` decimal(10,2) DEFAULT NULL,
  `requires_consent` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `procedures_department_id_index` (`department_id`),
  KEY `procedures_category_index` (`category`),
  CONSTRAINT `procedures_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchase_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` bigint(20) unsigned NOT NULL,
  `drug_id` bigint(20) unsigned NOT NULL,
  `investigation_item_id` bigint(20) unsigned DEFAULT NULL,
  `item_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'drug',
  `quantity_ordered` int(11) NOT NULL,
  `quantity_received` int(11) NOT NULL DEFAULT '0',
  `unit_cost` decimal(10,2) NOT NULL,
  `total_cost` decimal(10,2) NOT NULL,
  `batch_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_order_items_purchase_order_id_foreign` (`purchase_order_id`),
  KEY `purchase_order_items_drug_id_foreign` (`drug_id`),
  KEY `purchase_order_items_investigation_item_id_foreign` (`investigation_item_id`),
  CONSTRAINT `purchase_order_items_drug_id_foreign` FOREIGN KEY (`drug_id`) REFERENCES `drugs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_order_items_investigation_item_id_foreign` FOREIGN KEY (`investigation_item_id`) REFERENCES `investigation_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_order_items_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchase_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `po_number` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `order_date` date NOT NULL,
  `expected_date` date DEFAULT NULL,
  `received_date` date DEFAULT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint(20) unsigned NOT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_orders_po_number_unique` (`po_number`),
  KEY `purchase_orders_created_by_foreign` (`created_by`),
  KEY `purchase_orders_approved_by_foreign` (`approved_by`),
  KEY `purchase_orders_status_order_date_index` (`status`,`order_date`),
  KEY `purchase_orders_supplier_id_status_index` (`supplier_id`,`status`),
  CONSTRAINT `purchase_orders_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_orders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_orders_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `queue_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `queue_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `visit_id` bigint(20) unsigned NOT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `queue_number` int(11) NOT NULL,
  `priority` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'waiting',
  `called_at` timestamp NULL DEFAULT NULL,
  `served_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `served_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `queue_entries_visit_id_foreign` (`visit_id`),
  KEY `queue_entries_served_by_foreign` (`served_by`),
  KEY `queue_entries_department_id_status_index` (`department_id`,`status`),
  KEY `queue_entries_queue_number_index` (`queue_number`),
  KEY `queue_entries_priority_index` (`priority`),
  KEY `queue_entries_called_at_index` (`called_at`),
  CONSTRAINT `queue_entries_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `queue_entries_served_by_foreign` FOREIGN KEY (`served_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `queue_entries_visit_id_foreign` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `service_catalog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service_catalog` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `department_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `service_catalog_code_unique` (`code`),
  KEY `service_catalog_department_id_foreign` (`department_id`),
  CONSTRAINT `service_catalog_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `service_prices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service_prices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `service_catalog_id` bigint(20) unsigned NOT NULL,
  `insurance_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `insurance_provider_id` bigint(20) unsigned DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_svc_ins_type_provider` (`service_catalog_id`,`insurance_type`,`insurance_provider_id`),
  KEY `service_prices_insurance_provider_id_foreign` (`insurance_provider_id`),
  CONSTRAINT `service_prices_insurance_provider_id_foreign` FOREIGN KEY (`insurance_provider_id`) REFERENCES `insurance_providers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `service_prices_service_catalog_id_foreign` FOREIGN KEY (`service_catalog_id`) REFERENCES `service_catalog` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `service_specialty`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service_specialty` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `service_catalog_id` bigint(20) unsigned NOT NULL,
  `specialty_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `service_specialty_service_catalog_id_specialty_id_unique` (`service_catalog_id`,`specialty_id`),
  KEY `service_specialty_specialty_id_foreign` (`specialty_id`),
  CONSTRAINT `service_specialty_service_catalog_id_foreign` FOREIGN KEY (`service_catalog_id`) REFERENCES `service_catalog` (`id`) ON DELETE CASCADE,
  CONSTRAINT `service_specialty_specialty_id_foreign` FOREIGN KEY (`specialty_id`) REFERENCES `specialties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `group` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` longtext COLLATE utf8mb4_unicode_ci,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'string',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_group_key_unique` (`group`,`key`),
  KEY `settings_group_index` (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `specialties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `specialties` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `specialties_name_unique` (`name`),
  KEY `specialties_department_id_foreign` (`department_id`),
  CONSTRAINT `specialties_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stock_balances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_balances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `drug_id` bigint(20) unsigned NOT NULL,
  `stock_location_id` bigint(20) unsigned NOT NULL,
  `quantity_on_hand` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `last_movement_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_balances_drug_location_unique` (`drug_id`,`stock_location_id`),
  KEY `stock_balances_stock_location_id_foreign` (`stock_location_id`),
  CONSTRAINT `stock_balances_drug_id_foreign` FOREIGN KEY (`drug_id`) REFERENCES `drugs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_balances_stock_location_id_foreign` FOREIGN KEY (`stock_location_id`) REFERENCES `stock_locations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stock_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_locations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'store',
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_locations_name_unique` (`name`),
  KEY `stock_locations_department_id_foreign` (`department_id`),
  CONSTRAINT `stock_locations_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stock_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_movements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `drug_id` bigint(20) unsigned NOT NULL,
  `stock_location_id` bigint(20) unsigned NOT NULL,
  `movement_type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `direction` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(14,4) NOT NULL,
  `unit_cost` decimal(12,2) DEFAULT NULL,
  `batch_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `source_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_id` bigint(20) unsigned DEFAULT NULL,
  `performed_by` bigint(20) unsigned DEFAULT NULL,
  `movement_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_movements_performed_by_foreign` (`performed_by`),
  KEY `stock_movements_drug_id_index` (`drug_id`),
  KEY `stock_movements_stock_location_id_index` (`stock_location_id`),
  KEY `stock_movements_movement_type_index` (`movement_type`),
  KEY `stock_movements_direction_index` (`direction`),
  KEY `stock_movements_source_type_source_id_index` (`source_type`,`source_id`),
  KEY `stock_movements_movement_date_index` (`movement_date`),
  CONSTRAINT `stock_movements_drug_id_foreign` FOREIGN KEY (`drug_id`) REFERENCES `drugs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_movements_performed_by_foreign` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stock_movements_stock_location_id_foreign` FOREIGN KEY (`stock_location_id`) REFERENCES `stock_locations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stock_transfer_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_transfer_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `stock_transfer_id` bigint(20) unsigned NOT NULL,
  `drug_id` bigint(20) unsigned NOT NULL,
  `investigation_item_id` bigint(20) unsigned DEFAULT NULL,
  `item_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'drug',
  `quantity` int(11) NOT NULL,
  `batch_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_transfer_items_stock_transfer_id_foreign` (`stock_transfer_id`),
  KEY `stock_transfer_items_drug_id_foreign` (`drug_id`),
  KEY `stock_transfer_items_investigation_item_id_foreign` (`investigation_item_id`),
  CONSTRAINT `stock_transfer_items_drug_id_foreign` FOREIGN KEY (`drug_id`) REFERENCES `drugs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_transfer_items_investigation_item_id_foreign` FOREIGN KEY (`investigation_item_id`) REFERENCES `investigation_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stock_transfer_items_stock_transfer_id_foreign` FOREIGN KEY (`stock_transfer_id`) REFERENCES `stock_transfers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stock_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_transfers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `transfer_number` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `from_location` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'store',
  `to_location` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pharmacy',
  `transferred_by` bigint(20) unsigned NOT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `transfer_date` datetime NOT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_transfers_transfer_number_unique` (`transfer_number`),
  KEY `stock_transfers_transferred_by_foreign` (`transferred_by`),
  KEY `stock_transfers_approved_by_foreign` (`approved_by`),
  KEY `stock_transfers_status_transfer_date_index` (`status`,`transfer_date`),
  CONSTRAINT `stock_transfers_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stock_transfers_transferred_by_foreign` FOREIGN KEY (`transferred_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suppliers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_person` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `theatre_rooms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `theatre_rooms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `theatre_rooms_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `treatments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `treatments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `medical_record_id` bigint(20) unsigned NOT NULL,
  `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `treatments_medical_record_id_foreign` (`medical_record_id`),
  CONSTRAINT `treatments_medical_record_id_foreign` FOREIGN KEY (`medical_record_id`) REFERENCES `medical_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `triages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `triages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `visit_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `blood_pressure_systolic` int(11) DEFAULT NULL,
  `blood_pressure_diastolic` int(11) DEFAULT NULL,
  `heart_rate` int(11) DEFAULT NULL,
  `temperature` decimal(4,1) DEFAULT NULL,
  `respiratory_rate` int(11) DEFAULT NULL,
  `spo2` int(11) DEFAULT NULL,
  `weight` decimal(5,1) DEFAULT NULL,
  `height` decimal(5,1) DEFAULT NULL,
  `bmi` decimal(4,1) DEFAULT NULL,
  `triage_score` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `triaged_by` bigint(20) unsigned NOT NULL,
  `triaged_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `triages_visit_id_unique` (`visit_id`),
  KEY `triages_patient_id_foreign` (`patient_id`),
  KEY `triages_department_id_foreign` (`department_id`),
  KEY `triages_triaged_by_foreign` (`triaged_by`),
  KEY `triages_triage_score_index` (`triage_score`),
  CONSTRAINT `triages_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `triages_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `triages_triaged_by_foreign` FOREIGN KEY (`triaged_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `triages_visit_id_foreign` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `avatar` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `employee_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `designation_id` bigint(20) unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_employee_id_unique` (`employee_id`),
  KEY `users_department_id_foreign` (`department_id`),
  KEY `users_designation_id_foreign` (`designation_id`),
  CONSTRAINT `users_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_designation_id_foreign` FOREIGN KEY (`designation_id`) REFERENCES `designations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `visit_department_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `visit_department_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `visit_id` bigint(20) unsigned NOT NULL,
  `department_id` bigint(20) unsigned NOT NULL,
  `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'consultation',
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'waiting',
  `assigned_by` bigint(20) unsigned NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `visit_department_history_department_id_foreign` (`department_id`),
  KEY `visit_department_history_assigned_by_foreign` (`assigned_by`),
  KEY `visit_department_history_visit_id_department_id_index` (`visit_id`,`department_id`),
  KEY `visit_department_history_type_index` (`type`),
  KEY `visit_department_history_status_index` (`status`),
  CONSTRAINT `visit_department_history_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `visit_department_history_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `visit_department_history_visit_id_foreign` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `visit_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `visit_services` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `visit_id` bigint(20) unsigned NOT NULL,
  `service_catalog_id` bigint(20) unsigned NOT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `patient_insurance_id` bigint(20) unsigned DEFAULT NULL,
  `payment_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `insurance_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pricing_source` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT '1',
  `unit_price` decimal(10,2) NOT NULL,
  `insurance_price` decimal(10,2) DEFAULT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `insurance_covered` decimal(10,2) NOT NULL DEFAULT '0.00',
  `patient_payable` decimal(10,2) NOT NULL DEFAULT '0.00',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `visit_services_department_id_foreign` (`department_id`),
  KEY `visit_services_service_catalog_id_foreign` (`service_catalog_id`),
  KEY `visit_services_visit_id_service_catalog_id_index` (`visit_id`,`service_catalog_id`),
  KEY `visit_services_patient_insurance_id_foreign` (`patient_insurance_id`),
  CONSTRAINT `visit_services_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `visit_services_patient_insurance_id_foreign` FOREIGN KEY (`patient_insurance_id`) REFERENCES `patient_insurances` (`id`) ON DELETE SET NULL,
  CONSTRAINT `visit_services_service_catalog_id_foreign` FOREIGN KEY (`service_catalog_id`) REFERENCES `service_catalog` (`id`) ON DELETE CASCADE,
  CONSTRAINT `visit_services_visit_id_foreign` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `visit_status_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `visit_status_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `visit_id` bigint(20) unsigned NOT NULL,
  `from_status` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `to_status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `changed_by` bigint(20) unsigned NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `visit_status_logs_visit_id_foreign` (`visit_id`),
  KEY `visit_status_logs_changed_by_foreign` (`changed_by`),
  KEY `visit_status_logs_to_status_index` (`to_status`),
  KEY `visit_status_logs_timestamp_index` (`timestamp`),
  CONSTRAINT `visit_status_logs_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `visit_status_logs_visit_id_foreign` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `visits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `visits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `visit_number` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `patient_age` int(11) DEFAULT NULL,
  `visit_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `visit_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'registered',
  `priority` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `assigned_doctor_id` bigint(20) unsigned DEFAULT NULL,
  `chief_complaint` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `checked_in_at` timestamp NULL DEFAULT NULL,
  `checked_out_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `visit_insurance_id` bigint(20) unsigned DEFAULT NULL,
  `insurance_verification_id` bigint(20) unsigned DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancellation_reason` text COLLATE utf8mb4_unicode_ci,
  `rescheduled_from_id` bigint(20) unsigned DEFAULT NULL,
  `rescheduled_at` timestamp NULL DEFAULT NULL,
  `rescheduled_reason` text COLLATE utf8mb4_unicode_ci,
  `consultation_mode` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'in_person',
  `meeting_link` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `current_department_id` bigint(20) unsigned DEFAULT NULL,
  `triage_score` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `visits_visit_number_unique` (`visit_number`),
  KEY `visits_assigned_doctor_id_foreign` (`assigned_doctor_id`),
  KEY `visits_created_by_foreign` (`created_by`),
  KEY `visits_status_index` (`status`),
  KEY `visits_visit_date_index` (`visit_date`),
  KEY `visits_priority_index` (`priority`),
  KEY `visits_visit_insurance_id_foreign` (`visit_insurance_id`),
  KEY `visits_cancelled_by_foreign` (`cancelled_by`),
  KEY `visits_rescheduled_from_id_foreign` (`rescheduled_from_id`),
  KEY `visits_triage_score_index` (`triage_score`),
  KEY `idx_visits_patient_id` (`patient_id`),
  KEY `idx_visits_status` (`status`),
  KEY `idx_visits_current_dept` (`current_department_id`),
  KEY `idx_visits_created_at` (`created_at`),
  KEY `idx_visits_status_created` (`status`,`created_at`),
  KEY `visits_insurance_verification_id_foreign` (`insurance_verification_id`),
  CONSTRAINT `visits_assigned_doctor_id_foreign` FOREIGN KEY (`assigned_doctor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `visits_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `visits_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `visits_current_department_id_foreign` FOREIGN KEY (`current_department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `visits_insurance_verification_id_foreign` FOREIGN KEY (`insurance_verification_id`) REFERENCES `insurance_verifications` (`id`) ON DELETE SET NULL,
  CONSTRAINT `visits_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `visits_rescheduled_from_id_foreign` FOREIGN KEY (`rescheduled_from_id`) REFERENCES `visits` (`id`) ON DELETE SET NULL,
  CONSTRAINT `visits_visit_insurance_id_foreign` FOREIGN KEY (`visit_insurance_id`) REFERENCES `patient_insurances` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `vitals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vitals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `visit_id` bigint(20) unsigned NOT NULL,
  `admission_id` bigint(20) unsigned DEFAULT NULL,
  `triage_id` bigint(20) unsigned DEFAULT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `recorded_by` bigint(20) unsigned NOT NULL,
  `blood_pressure_systolic` int(11) DEFAULT NULL,
  `blood_pressure_diastolic` int(11) DEFAULT NULL,
  `heart_rate` int(11) DEFAULT NULL,
  `temperature` decimal(4,1) DEFAULT NULL,
  `respiratory_rate` int(11) DEFAULT NULL,
  `spo2` int(11) DEFAULT NULL,
  `weight` decimal(5,1) DEFAULT NULL,
  `height` decimal(5,1) DEFAULT NULL,
  `bmi` decimal(4,1) DEFAULT NULL,
  `blood_sugar` decimal(5,1) DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `recorded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vitals_triage_id_unique` (`triage_id`),
  KEY `vitals_visit_id_foreign` (`visit_id`),
  KEY `vitals_patient_id_foreign` (`patient_id`),
  KEY `vitals_recorded_by_foreign` (`recorded_by`),
  KEY `vitals_admission_id_foreign` (`admission_id`),
  CONSTRAINT `vitals_admission_id_foreign` FOREIGN KEY (`admission_id`) REFERENCES `admissions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vitals_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vitals_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vitals_triage_id_foreign` FOREIGN KEY (`triage_id`) REFERENCES `triages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vitals_visit_id_foreign` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ward_rounds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ward_rounds` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `admission_id` bigint(20) unsigned NOT NULL,
  `recorded_by` bigint(20) unsigned NOT NULL,
  `round_date` datetime NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `instructions` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ward_rounds_admission_id_foreign` (`admission_id`),
  KEY `ward_rounds_recorded_by_foreign` (`recorded_by`),
  KEY `ward_rounds_round_date_index` (`round_date`),
  CONSTRAINT `ward_rounds_admission_id_foreign` FOREIGN KEY (`admission_id`) REFERENCES `admissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ward_rounds_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wards` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `capacity` int(11) NOT NULL DEFAULT '0',
  `floor` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wards_code_unique` (`code`),
  KEY `wards_department_id_foreign` (`department_id`),
  KEY `wards_is_active_index` (`is_active`),
  CONSTRAINT `wards_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2026_04_11_000001_create_departments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2026_04_11_000002_create_designations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2026_04_11_000003_modify_users_table_for_uhms',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2026_04_11_214943_create_permission_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2026_04_11_215013_create_activity_log_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2026_04_11_215014_add_event_column_to_activity_log_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2026_04_11_215015_add_batch_uuid_column_to_activity_log_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2026_04_11_300001_create_patients_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2026_04_11_400001_create_visits_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2026_04_11_400002_create_visit_status_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2026_04_11_400003_create_queue_entries_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2026_04_12_000001_create_medical_records_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2026_04_12_000002_create_complaints_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2026_04_12_000003_create_diagnoses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2026_04_12_000004_create_investigations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2026_04_12_000005_create_treatments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2026_04_12_000006_create_prescriptions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2026_04_12_000007_create_prescription_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2026_04_12_000008_create_vitals_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2026_04_12_000009_create_medical_patterns_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2026_04_12_000010_create_medical_pattern_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2026_04_12_100001_create_lab_test_categories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2026_04_12_100001_create_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2026_04_12_100002_create_lab_tests_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2026_04_12_100003_create_lab_requests_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2026_04_12_100004_create_lab_request_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2026_04_12_100005_create_lab_results_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2026_04_12_152150_create_notifications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2026_04_12_200001_create_drug_categories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2026_04_12_200002_create_drugs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2026_04_12_200003_create_drug_stock_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2026_04_12_200004_create_dispensing_records_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2026_04_12_300001_create_service_catalog_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2026_04_12_300002_create_invoices_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2026_04_12_300003_create_invoice_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2026_04_12_300004_create_payments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2026_04_12_400001_create_wards_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2026_04_12_400002_create_beds_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2026_04_12_400003_create_admissions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2026_04_12_400004_create_ward_rounds_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2026_04_12_500001_create_appointments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2026_04_12_600001_create_insurance_providers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2026_04_12_600002_create_claims_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2026_04_12_600003_create_claim_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2026_04_12_700001_create_suppliers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2026_04_12_700002_create_purchase_orders_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2026_04_12_700003_create_purchase_order_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2026_04_12_700004_create_stock_transfers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2026_04_12_700005_create_stock_transfer_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2026_04_12_700006_add_location_to_drug_stock',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2026_04_12_800001_create_account_categories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2026_04_12_800002_create_financial_entries_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2026_04_12_800003_create_cashier_shifts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2026_04_12_900001_create_employees_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2026_04_12_900002_create_employee_attendance_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2026_04_12_900003_create_leave_requests_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2026_04_12_900004_create_payroll_records_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2026_04_12_950001_create_icd_codes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2026_04_12_950002_create_procedures_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2026_04_12_950003_create_patient_procedures_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2026_04_12_950004_add_icd_code_id_to_diagnoses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2026_04_12_960001_create_analyzer_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2026_04_12_960002_add_sample_id_to_lab_requests_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2026_04_13_000001_add_performance_indexes',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2026_04_13_095727_remove_nhis_fields_from_patients_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2026_04_13_100001_create_patient_insurances_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2026_04_13_100002_create_emergency_contacts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2026_04_13_100003_enhance_insurance_providers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2026_04_13_100004_add_religion_to_patients_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2026_04_13_100005_add_scheduling_and_insurance_to_visits_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2026_04_13_100006_create_consultation_tasks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2026_04_13_200001_create_specialties_and_pivot_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2026_04_13_200002_add_department_to_service_catalog',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2026_04_13_200003_create_visit_services_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2026_04_13_200004_add_start_date_to_patient_insurances',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2026_04_14_100001_add_clinical_fields_to_appointments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2026_04_14_100002_create_appointment_services_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2026_04_18_000001_make_queue_entries_department_nullable',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2026_04_18_024049_add_type_to_departments_and_age_to_visits_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (83,'2026_04_18_100000_add_department_to_specialties_and_create_service_prices',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (84,'2026_04_20_000001_cleanup_service_visits_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (85,'2026_04_21_000001_add_town_to_patients_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (86,'2026_04_21_000001_create_triages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (87,'2026_04_21_000002_create_visit_department_history_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (88,'2026_04_21_000003_add_triage_fields_to_visits_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (89,'2026_04_21_143405_make_department_id_nullable_on_designations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (90,'2026_04_21_212754_add_monthly_limits_to_insurance_providers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (91,'2026_04_21_212801_create_insurance_usages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (92,'2026_04_21_212826_create_insurance_usages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (93,'2026_04_21_213653_add_columns_to_insurance_usages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (94,'2026_04_21_220000_create_insurance_tiers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (95,'2026_04_21_220100_add_tier_member_to_patient_insurances_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (96,'2026_04_21_220200_remove_limits_from_insurance_providers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (97,'2026_04_22_000001_add_is_primary_to_diagnoses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (98,'2026_04_22_144330_add_height_to_patients_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (99,'2026_04_23_000001_add_admission_type_and_services_to_admissions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (100,'2026_04_27_000001_add_admission_id_to_vitals_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (101,'2026_04_28_000001_create_investigation_items_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (102,'2026_04_28_100001_add_result_type_and_stock_managed_to_departments',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (103,'2026_04_28_100002_add_name_to_lab_request_items',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (104,'2026_04_28_200001_add_target_department_id_to_lab_requests_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (105,'2026_04_28_200002_make_lab_test_id_nullable_on_lab_request_items',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (106,'2026_04_28_200003_add_result_type_columns_to_lab_results_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (107,'2026_05_02_100001_create_modules_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (108,'2026_05_02_100002_add_performance_indexes',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (109,'2026_05_03_130000_add_triage_id_to_vitals_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (110,'2026_05_04_000001_create_lab_test_criteria_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (111,'2026_05_04_120000_consolidate_department_result_types',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (112,'2026_05_04_120100_link_lab_catalog_to_departments',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (113,'2026_05_05_090000_add_pricing_snapshot_to_invoice_items',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (114,'2026_05_05_120000_generalize_nhis_to_insurance_provider_category',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (115,'2026_05_06_122923_add_verification_columns_to_insurance_providers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (116,'2026_05_06_122924_create_insurance_verifications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (117,'2026_05_06_122925_link_insurance_verifications_to_visits_and_claims',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (118,'2026_05_08_203253_add_pricing_snapshot_fields_to_visit_services_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (119,'2026_05_09_000001_add_service_id_to_lab_request_items',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (120,'2026_05_09_000002_create_investigation_headers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (121,'2026_05_09_000003_create_investigation_criteria_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (122,'2026_05_09_000004_create_investigation_result_values_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (123,'2026_05_09_000005_add_acceptance_billing_to_lab_request_items',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (124,'2026_05_11_000001_extend_invoice_items_for_single_visit_invoice',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (125,'2026_05_11_000002_create_payment_allocations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (126,'2026_05_11_000003_enforce_unique_active_invoice_per_visit',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (127,'2026_05_12_000001_add_ccc_code_to_patient_insurances_and_invoice_items',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (128,'2026_05_12_092800_make_legacy_invoice_item_unit_price_nullable',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (129,'2026_05_13_090000_create_stock_locations_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (130,'2026_05_13_090100_create_stock_movements_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (131,'2026_05_13_090200_create_stock_balances_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (132,'2026_05_13_090300_add_opening_stock_to_drugs_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (133,'2026_05_13_000001_create_theatre_procedure_tables',6);
