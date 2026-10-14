-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 14, 2026 at 06:52 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `skinova_project`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(80) NOT NULL,
  `entity_type` varchar(60) DEFAULT NULL,
  `entity_id` int(10) UNSIGNED DEFAULT NULL,
  `details` varchar(1000) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ai_conversations`
--

CREATE TABLE `ai_conversations` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(150) DEFAULT NULL,
  `channel` enum('chatbot','assistant') NOT NULL DEFAULT 'chatbot',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ai_knowledge_base`
--

CREATE TABLE `ai_knowledge_base` (
  `id` int(10) UNSIGNED NOT NULL,
  `category` varchar(60) NOT NULL DEFAULT 'general' COMMENT 'general, ingredients, products, orders, delivery, payment, returns, safety',
  `question` varchar(300) NOT NULL,
  `answer` text NOT NULL,
  `keywords` varchar(500) DEFAULT NULL COMMENT 'comma separated matching keywords',
  `is_faq` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'shown on faq.php',
  `is_sensitive` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'answer should recommend professional consultation',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `hit_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ai_messages`
--

CREATE TABLE `ai_messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `conversation_id` int(10) UNSIGNED NOT NULL,
  `role` enum('user','assistant','system') NOT NULL,
  `content` text NOT NULL,
  `source` enum('user','ai','knowledge_base','rule_based','fallback') NOT NULL DEFAULT 'user' COMMENT 'where the assistant answer came from - never label KB/rule answers as ai',
  `provider` varchar(40) DEFAULT NULL,
  `meta` text DEFAULT NULL COMMENT 'JSON: suggested products, kb id, escalation flag ...',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ai_query_logs`
--

CREATE TABLE `ai_query_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `module` enum('chatbot','assistant','recommendation','routine','ingredient','skin_analysis','comparison') NOT NULL,
  `query_text` varchar(1000) DEFAULT NULL,
  `response_source` enum('ai','knowledge_base','rule_based','image_metrics','none') NOT NULL DEFAULT 'none',
  `kb_id` int(10) UNSIGNED DEFAULT NULL,
  `provider` varchar(40) DEFAULT NULL,
  `status` enum('success','fallback','error') NOT NULL DEFAULT 'success',
  `escalated_to_human` tinyint(1) NOT NULL DEFAULT 0,
  `response_ms` int(10) UNSIGNED DEFAULT NULL,
  `error_message` varchar(300) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ai_recommendations`
--

CREATE TABLE `ai_recommendations` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `batch_key` char(16) NOT NULL COMMENT 'groups rows created by one recommendation request',
  `product_id` int(10) UNSIGNED NOT NULL,
  `rec_type` enum('personalized','budget','routine','similar') NOT NULL DEFAULT 'personalized',
  `score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `reason` varchar(800) DEFAULT NULL COMMENT 'human readable explanation of why it was recommended',
  `source` enum('rule_based','ai') NOT NULL DEFAULT 'rule_based',
  `provider` varchar(40) DEFAULT NULL,
  `status` enum('active','hidden') NOT NULL DEFAULT 'active',
  `clicked` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blogs`
--

CREATE TABLE `blogs` (
  `id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `author_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(190) NOT NULL,
  `slug` varchar(210) NOT NULL,
  `excerpt` varchar(400) DEFAULT NULL,
  `content` mediumtext NOT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `tags` varchar(255) DEFAULT NULL,
  `is_tip` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'short skincare tip',
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `view_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `like_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `comment_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `published_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blog_categories`
--

CREATE TABLE `blog_categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `description` varchar(300) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blog_comments`
--

CREATE TABLE `blog_comments` (
  `id` int(10) UNSIGNED NOT NULL,
  `blog_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `comment` varchar(1500) NOT NULL,
  `status` enum('visible','hidden') NOT NULL DEFAULT 'visible',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blog_likes`
--

CREATE TABLE `blog_likes` (
  `blog_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

CREATE TABLE `brands` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`id`, `name`, `slug`, `logo`, `description`, `is_active`, `created_at`) VALUES
(1, 'PureDerm', 'purederm', NULL, 'Clean formulas for everyday skin health.', 1, '2026-10-14 22:38:34'),
(2, 'GlowBotanics', 'glowbotanics', NULL, 'Plant-powered skincare for radiance.', 1, '2026-10-14 22:38:34'),
(3, 'DermaSense', 'dermasense', NULL, 'Science-led formulas developed with dermatologists.', 1, '2026-10-14 22:38:34'),
(4, 'AquaBloom', 'aquabloom', NULL, 'Hydration-first skincare.', 1, '2026-10-14 22:38:34'),
(5, 'SolShield', 'solshield', NULL, 'Sun protection for every skin tone.', 1, '2026-10-14 22:38:34');

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `coupon_code` varchar(40) DEFAULT NULL,
  `use_loyalty_points` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `cart_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `added_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `description`, `image`, `sort_order`, `is_active`, `created_at`) VALUES
(1, NULL, 'Cleansers', 'cleansers', 'Gentle daily cleansers to start every routine.', NULL, 1, 1, '2026-10-14 22:38:34'),
(2, NULL, 'Toners', 'toners', 'Prep and balance skin after cleansing.', NULL, 2, 1, '2026-10-14 22:38:34'),
(3, NULL, 'Serums', 'serums', 'Concentrated treatments for targeted concerns.', NULL, 3, 1, '2026-10-14 22:38:34'),
(4, NULL, 'Moisturizers', 'moisturizers', 'Hydrate and protect the skin barrier.', NULL, 4, 1, '2026-10-14 22:38:34'),
(5, NULL, 'Sunscreens', 'sunscreens', 'Daily UV protection.', NULL, 5, 1, '2026-10-14 22:38:34'),
(6, NULL, 'Masks', 'masks', 'Weekly treatments for deep care.', NULL, 6, 1, '2026-10-14 22:38:34'),
(7, NULL, 'Treatments', 'treatments', 'Targeted spot and exfoliating treatments.', NULL, 7, 1, '2026-10-14 22:38:34');

-- --------------------------------------------------------

--
-- Table structure for table `consultants`
--

CREATE TABLE `consultants` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(80) DEFAULT NULL COMMENT 'e.g. Dr., Esthetician',
  `specialization` varchar(190) NOT NULL,
  `bio` text DEFAULT NULL,
  `qualifications` varchar(400) DEFAULT NULL,
  `experience_years` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `languages` varchar(190) DEFAULT 'English',
  `consultation_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `availability_note` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'accepting new consultations',
  `rating_avg` decimal(3,2) NOT NULL DEFAULT 0.00,
  `rating_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `consultations`
--

CREATE TABLE `consultations` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `consultant_id` int(10) UNSIGNED NOT NULL,
  `subject` varchar(190) NOT NULL,
  `concern_details` text DEFAULT NULL,
  `preferred_at` datetime DEFAULT NULL,
  `status` enum('requested','confirmed','in_progress','completed','cancelled','rejected') NOT NULL DEFAULT 'requested',
  `fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_status` enum('unpaid','paid','waived') NOT NULL DEFAULT 'unpaid',
  `share_skin_profile` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'user consent to show skin profile to consultant',
  `advice_summary` text DEFAULT NULL COMMENT 'expert advice / consultation record written by consultant',
  `user_rating` tinyint(3) UNSIGNED DEFAULT NULL,
  `user_feedback` varchar(500) DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `consultation_messages`
--

CREATE TABLE `consultation_messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `consultation_id` int(10) UNSIGNED NOT NULL,
  `sender_id` int(10) UNSIGNED NOT NULL,
  `message` text NOT NULL,
  `attachment_path` varchar(255) DEFAULT NULL COMMENT 'private file under assets/uploads/prescriptions, served by download.php',
  `attachment_name` varchar(190) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `subject` varchar(190) NOT NULL,
  `message` text NOT NULL,
  `is_handled` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(40) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL DEFAULT 'percentage',
  `discount_value` decimal(10,2) NOT NULL,
  `min_order_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `max_discount_amount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int(10) UNSIGNED DEFAULT NULL COMMENT 'total uses allowed, NULL = unlimited',
  `per_user_limit` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `used_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `first_order_only` tinyint(1) NOT NULL DEFAULT 0,
  `starts_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `coupon_usages`
--

CREATE TABLE `coupon_usages` (
  `id` int(10) UNSIGNED NOT NULL,
  `coupon_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `used_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ingredients`
--

CREATE TABLE `ingredients` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `slug` varchar(140) NOT NULL,
  `inci_name` varchar(190) DEFAULT NULL,
  `ingredient_type` varchar(60) DEFAULT NULL COMMENT 'humectant, exfoliant, antioxidant ...',
  `purpose` varchar(500) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `considerations` text DEFAULT NULL COMMENT 'known cautions / who should be careful',
  `risk_level` enum('low','moderate','high') NOT NULL DEFAULT 'low',
  `is_common_allergen` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ingredients`
--

INSERT INTO `ingredients` (`id`, `name`, `slug`, `inci_name`, `ingredient_type`, `purpose`, `description`, `considerations`, `risk_level`, `is_common_allergen`, `created_at`) VALUES
(1, 'Water', 'water', 'Aqua', 'Solvent', 'Dissolves and carries the other ingredients.', 'The base of most skincare formulas.', 'Generally well tolerated by all skin types.', 'low', 0, '2026-10-14 22:38:34'),
(2, 'Glycerin', 'glycerin', 'Glycerin', 'Humectant', 'Draws water into the skin to keep it hydrated.', 'A gentle, widely used moisturising ingredient.', 'Very well tolerated; rarely causes reactions.', 'low', 0, '2026-10-14 22:38:34'),
(3, 'Hyaluronic Acid', 'hyaluronic-acid', 'Sodium Hyaluronate', 'Humectant', 'Holds water in the skin for plumper, hydrated skin.', 'A molecule naturally found in skin that can bind large amounts of water.', 'Well tolerated. Apply to damp skin and seal with a moisturizer.', 'low', 0, '2026-10-14 22:38:34'),
(4, 'Niacinamide', 'niacinamide', 'Niacinamide', 'Vitamin B3', 'Helps visibly reduce oiliness, pores and uneven tone; supports the barrier.', 'A multi-tasking vitamin B3 derivative.', 'Usually well tolerated. Some people feel flushing or tingling at high strengths; patch test first.', 'low', 0, '2026-10-14 22:38:34'),
(5, 'Salicylic Acid', 'salicylic-acid', 'Salicylic Acid', 'Exfoliant (BHA)', 'Clears oil and dead cells from pores; helps with blackheads and breakouts.', 'An oil-soluble beta hydroxy acid.', 'Can dry or irritate, especially with other actives. Use sun protection. Ask a professional before use if pregnant or breastfeeding.', 'moderate', 0, '2026-10-14 22:38:34'),
(6, 'Glycolic Acid', 'glycolic-acid', 'Glycolic Acid', 'Exfoliant (AHA)', 'Dissolves dull surface cells to smooth texture and brighten.', 'The smallest alpha hydroxy acid, so it penetrates easily.', 'Increases sun sensitivity and can sting on sensitive skin. Start slowly and use sunscreen daily.', 'high', 0, '2026-10-14 22:38:34'),
(7, 'Lactic Acid', 'lactic-acid', 'Lactic Acid', 'Exfoliant (AHA)', 'Gently exfoliates while also hydrating.', 'A milder alpha hydroxy acid than glycolic acid.', 'May cause mild tingling. Use sunscreen. Patch test on sensitive skin.', 'moderate', 0, '2026-10-14 22:38:34'),
(8, 'Retinol', 'retinol', 'Retinol', 'Retinoid', 'Supports cell turnover to soften fine lines, texture and breakouts.', 'A vitamin A derivative and one of the most studied anti-ageing ingredients.', 'Can cause dryness, peeling and irritation while skin adjusts. Use at night with sunscreen by day. Avoid during pregnancy - consult a professional.', 'high', 0, '2026-10-14 22:38:34'),
(9, 'Vitamin C', 'vitamin-c', 'Ascorbic Acid', 'Antioxidant', 'Brightens dull skin and helps protect against environmental stress.', 'A potent antioxidant that can fade the look of dark spots over time.', 'Can sting at higher strengths or on compromised skin. Store away from light and air.', 'moderate', 0, '2026-10-14 22:38:34'),
(10, 'Ceramides', 'ceramides', 'Ceramide NP', 'Skin-identical lipid', 'Rebuild the skin barrier and lock in moisture.', 'Natural fats that make up a large part of the skin barrier.', 'Very well tolerated.', 'low', 0, '2026-10-14 22:38:34'),
(11, 'Squalane', 'squalane', 'Squalane', 'Emollient', 'Softens skin without a greasy feel.', 'A lightweight, skin-compatible oil.', 'Well tolerated; non-comedogenic for most people.', 'low', 0, '2026-10-14 22:38:34'),
(12, 'Zinc Oxide', 'zinc-oxide', 'Zinc Oxide', 'Mineral UV filter', 'Reflects UVA and UVB rays; also calming.', 'A broad-spectrum physical sunscreen filter.', 'Can leave a white cast on deeper skin tones. Very gentle for sensitive skin.', 'low', 0, '2026-10-14 22:38:34'),
(13, 'Titanium Dioxide', 'titanium-dioxide', 'Titanium Dioxide', 'Mineral UV filter', 'Reflects UV rays as a physical filter.', 'Works with zinc oxide for broad coverage.', 'Generally well tolerated.', 'low', 0, '2026-10-14 22:38:34'),
(14, 'Panthenol', 'panthenol', 'Panthenol', 'Humectant / soothing', 'Soothes and supports barrier repair.', 'Provitamin B5.', 'Very well tolerated.', 'low', 0, '2026-10-14 22:38:34'),
(15, 'Centella Asiatica Extract', 'centella-asiatica', 'Centella Asiatica Extract', 'Soothing botanical', 'Calms redness and supports healing.', 'A traditional plant extract also known as cica.', 'Rarely may cause contact allergy in sensitive individuals.', 'low', 0, '2026-10-14 22:38:34'),
(16, 'Aloe Vera', 'aloe-vera', 'Aloe Barbadensis Leaf Juice', 'Soothing botanical', 'Cools and soothes irritated skin.', 'A hydrating plant gel.', 'Rare allergic reactions are possible; patch test.', 'low', 0, '2026-10-14 22:38:34'),
(17, 'Tea Tree Oil', 'tea-tree-oil', 'Melaleuca Alternifolia Leaf Oil', 'Essential oil', 'Has antimicrobial properties used in blemish products.', 'A natural oil used in low amounts for spots.', 'A known contact allergen for some people and can irritate sensitive skin.', 'moderate', 1, '2026-10-14 22:38:34'),
(18, 'Fragrance', 'fragrance', 'Parfum', 'Fragrance', 'Adds scent to the product.', 'A blend of scent compounds.', 'One of the most common causes of skin sensitivity and allergic reactions. Avoid if you have sensitive or reactive skin.', 'high', 1, '2026-10-14 22:38:34'),
(19, 'Alcohol Denat.', 'alcohol-denat', 'Alcohol Denat.', 'Solvent', 'Helps products dry quickly and feel light.', 'A drying alcohol used in some toners and gels.', 'May dry or irritate dry and sensitive skin with frequent use.', 'moderate', 0, '2026-10-14 22:38:34'),
(20, 'Benzoyl Peroxide', 'benzoyl-peroxide', 'Benzoyl Peroxide', 'Acne treatment', 'Kills acne-causing bacteria.', 'A common over-the-counter acne ingredient.', 'Can be drying and may bleach fabrics. Introduce slowly.', 'high', 0, '2026-10-14 22:38:34'),
(21, 'Azelaic Acid', 'azelaic-acid', 'Azelaic Acid', 'Treatment acid', 'Helps with redness, breakouts and dark marks.', 'A gentle multi-purpose acid.', 'May cause mild tingling at first. Often better tolerated than other acids.', 'moderate', 0, '2026-10-14 22:38:34'),
(22, 'Shea Butter', 'shea-butter', 'Butyrospermum Parkii Butter', 'Emollient', 'Nourishes and softens dry skin.', 'A rich plant butter.', 'Rich for oily or acne-prone skin; may feel heavy.', 'low', 0, '2026-10-14 22:38:34'),
(23, 'Kaolin Clay', 'kaolin-clay', 'Kaolin', 'Absorbent clay', 'Absorbs excess oil and impurities.', 'A mild white clay used in masks.', 'Can be drying if left on too long.', 'low', 0, '2026-10-14 22:38:34'),
(24, 'Peptides', 'peptides', 'Palmitoyl Tripeptide-1', 'Signal peptide', 'Support the look of firmer skin.', 'Short chains of amino acids.', 'Well tolerated.', 'low', 0, '2026-10-14 22:38:34'),
(25, 'Vitamin E', 'vitamin-e', 'Tocopherol', 'Antioxidant', 'Protects the formula and skin from oxidation.', 'A fat-soluble antioxidant.', 'Rarely may cause breakouts on very acne-prone skin.', 'low', 0, '2026-10-14 22:38:34'),
(26, 'Lavender Oil', 'lavender-oil', 'Lavandula Angustifolia Oil', 'Essential oil', 'Adds a calming scent.', 'A fragrant essential oil.', 'A common allergen and can irritate sensitive skin.', 'high', 1, '2026-10-14 22:38:34');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `low_stock_threshold` int(11) NOT NULL DEFAULT 5,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `product_id`, `quantity`, `low_stock_threshold`, `updated_at`) VALUES
(1, 1, 80, 10, '2026-10-14 22:38:34'),
(2, 2, 60, 10, '2026-10-14 22:38:34'),
(3, 3, 55, 10, '2026-10-14 22:38:34'),
(4, 4, 45, 8, '2026-10-14 22:38:34'),
(5, 5, 40, 8, '2026-10-14 22:38:34'),
(6, 6, 90, 15, '2026-10-14 22:38:34'),
(7, 7, 35, 8, '2026-10-14 22:38:34'),
(8, 8, 70, 10, '2026-10-14 22:38:34'),
(9, 9, 30, 8, '2026-10-14 22:38:34'),
(10, 10, 65, 10, '2026-10-14 22:38:34'),
(11, 11, 50, 10, '2026-10-14 22:38:34'),
(12, 12, 25, 5, '2026-10-14 22:38:34'),
(13, 13, 100, 15, '2026-10-14 22:38:34'),
(14, 14, 75, 10, '2026-10-14 22:38:34'),
(15, 15, 40, 8, '2026-10-14 22:38:34'),
(16, 16, 35, 8, '2026-10-14 22:38:34'),
(17, 17, 4, 5, '2026-10-14 22:38:34'),
(18, 18, 28, 5, '2026-10-14 22:38:34'),
(19, 19, 38, 8, '2026-10-14 22:38:34'),
(20, 20, 0, 5, '2026-10-14 22:38:34');

-- --------------------------------------------------------

--
-- Table structure for table `loyalty_transactions`
--

CREATE TABLE `loyalty_transactions` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED DEFAULT NULL,
  `points` int(11) NOT NULL COMMENT 'positive = earned, negative = redeemed',
  `type` enum('earned','redeemed','adjusted','reversed') NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `balance_after` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `type` enum('order','shipping','delivery','discount','new_product','wishlist_discount','consultation','message','system') NOT NULL DEFAULT 'system',
  `title` varchar(150) NOT NULL,
  `message` varchar(500) NOT NULL,
  `link` varchar(255) DEFAULT NULL COMMENT 'path relative to app root, e.g. order-details.php?id=5',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_number` varchar(30) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `status` enum('pending','confirmed','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `payment_method` enum('cod','online') NOT NULL DEFAULT 'cod',
  `payment_status` enum('pending','paid','failed','refunded','partially_refunded') NOT NULL DEFAULT 'pending',
  `shipping_name` varchar(120) NOT NULL,
  `shipping_phone` varchar(30) NOT NULL,
  `shipping_address` varchar(400) NOT NULL,
  `shipping_city` varchar(100) NOT NULL,
  `shipping_state` varchar(100) DEFAULT NULL,
  `shipping_postal_code` varchar(20) NOT NULL,
  `shipping_country` varchar(100) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'sum of unit_price*qty (after product discount)',
  `promotion_discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `coupon_code` varchar(40) DEFAULT NULL,
  `coupon_discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `loyalty_points_used` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `loyalty_discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `shipping_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `loyalty_points_earned` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `notes` varchar(500) DEFAULT NULL,
  `cancel_reason` varchar(300) DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED DEFAULT NULL,
  `product_name` varchar(190) NOT NULL,
  `sku` varchar(60) DEFAULT NULL,
  `original_price` decimal(10,2) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL,
  `line_total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_status_history`
--

CREATE TABLE `order_status_history` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `status` varchar(30) NOT NULL,
  `note` varchar(300) DEFAULT NULL,
  `changed_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `method` enum('cod','online') NOT NULL,
  `provider` varchar(40) NOT NULL DEFAULT 'cod' COMMENT 'cod | sandbox | stripe',
  `transaction_id` varchar(120) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` char(3) NOT NULL DEFAULT 'USD',
  `status` enum('pending','paid','failed','refunded','partially_refunded') NOT NULL DEFAULT 'pending',
  `card_brand` varchar(20) DEFAULT NULL,
  `card_last4` char(4) DEFAULT NULL,
  `failure_reason` varchar(255) DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prescriptions`
--

CREATE TABLE `prescriptions` (
  `id` int(10) UNSIGNED NOT NULL,
  `consultation_id` int(10) UNSIGNED DEFAULT NULL,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT 'the patient/customer the file belongs to',
  `consultant_id` int(10) UNSIGNED DEFAULT NULL,
  `uploaded_by` int(10) UNSIGNED NOT NULL,
  `kind` enum('user_upload','consultant_prescription') NOT NULL DEFAULT 'user_upload',
  `file_path` varchar(255) NOT NULL,
  `original_name` varchar(190) NOT NULL,
  `mime_type` varchar(80) NOT NULL,
  `file_size` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `notes` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `brand_id` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(190) NOT NULL,
  `slug` varchar(210) NOT NULL,
  `sku` varchar(60) NOT NULL,
  `short_description` varchar(300) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `benefits` text DEFAULT NULL COMMENT 'one benefit per line',
  `usage_instructions` text DEFAULT NULL,
  `precautions` text DEFAULT NULL,
  `size_label` varchar(40) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `discount_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `routine_step` enum('cleanser','toner','serum','moisturizer','sunscreen','treatment','mask','exfoliant','eye_care','other') NOT NULL DEFAULT 'other',
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `view_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `sold_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `rating_avg` decimal(3,2) NOT NULL DEFAULT 0.00,
  `rating_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `brand_id`, `name`, `slug`, `sku`, `short_description`, `description`, `benefits`, `usage_instructions`, `precautions`, `size_label`, `price`, `discount_percent`, `routine_step`, `is_featured`, `is_active`, `view_count`, `sold_count`, `rating_avg`, `rating_count`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Gentle Foaming Cleanser', 'gentle-foaming-cleanser', 'SKN-CL-001', 'Soap-free foaming cleanser that removes dirt without stripping.', 'A balanced, pH-friendly foaming cleanser that lifts away makeup, sunscreen and excess oil while leaving skin soft and comfortable.', 'Removes dirt and oil\nLeaves skin soft, not tight\nSuitable for daily use', 'Massage a small amount onto damp skin morning and night, then rinse with lukewarm water.', 'Avoid contact with eyes. Discontinue if irritation occurs.', '150 ml', 14.00, 0.00, 'cleanser', 1, 1, 540, 120, 0.00, 0, '2026-05-27 22:38:34', '2026-10-14 22:38:34'),
(2, 1, 2, 'Hydrating Cream Cleanser', 'hydrating-cream-cleanser', 'SKN-CL-002', 'Creamy, non-foaming cleanser for dry and sensitive skin.', 'A cushiony cream cleanser enriched with ceramides that cleans gently and keeps the moisture barrier comfortable.', 'Cleanses without drying\nSupports the skin barrier\nCalms tightness', 'Apply to dry or damp skin, massage gently, and rinse or wipe away with a soft cloth.', 'For external use only.', '150 ml', 16.50, 0.00, 'cleanser', 0, 1, 320, 80, 0.00, 0, '2026-06-06 22:38:34', '2026-10-14 22:38:34'),
(3, 1, 3, 'Clarifying Salicylic Cleanser', 'clarifying-salicylic-cleanser', 'SKN-CL-003', 'Daily gel cleanser with salicylic acid for oily, blemish-prone skin.', 'Helps keep pores clear and reduces the look of blackheads with a low strength of salicylic acid.', 'Clears excess oil\nHelps prevent clogged pores\nRefreshing gel texture', 'Use once daily (evening) at first, then increase to twice daily if skin tolerates it. Avoid the eye area.', 'May cause dryness. Use sunscreen during the day. If pregnant or breastfeeding, ask a professional first.', '150 ml', 15.00, 10.00, 'cleanser', 0, 1, 410, 95, 0.00, 0, '2026-06-16 22:38:34', '2026-10-14 22:38:34'),
(4, 2, 4, 'Hydrating Facial Toner', 'hydrating-facial-toner', 'SKN-TN-001', 'Alcohol-free toner that adds a first layer of hydration.', 'A lightweight, fragrance-free essence toner with hyaluronic acid and panthenol.', 'Instant hydration\nPrepares skin for serums\nAlcohol-free and gentle', 'After cleansing, pat a few drops into the face with clean hands or a cotton pad.', 'For external use only.', '200 ml', 18.00, 0.00, 'toner', 0, 1, 260, 60, 0.00, 0, '2026-06-26 22:38:34', '2026-10-14 22:38:34'),
(5, 2, 3, 'Exfoliating AHA/BHA Toner', 'exfoliating-aha-bha-toner', 'SKN-TN-002', 'Chemical exfoliating toner to smooth texture and unclog pores.', 'A leave-on toner with glycolic and salicylic acids for brighter, smoother skin.', 'Smooths rough texture\nHelps unclog pores\nBrightens dull skin', 'Use 2-3 evenings per week after cleansing. Follow with moisturizer. Always use sunscreen the next day.', 'Contains fragrance. Strong exfoliant - may sting on sensitive skin. Not for use with retinol on the same night.', '150 ml', 22.00, 15.00, 'toner', 1, 1, 380, 70, 0.00, 0, '2026-07-06 22:38:34', '2026-10-14 22:38:34'),
(6, 3, 1, 'Niacinamide 10% Serum', 'niacinamide-10-serum', 'SKN-SR-001', 'Balancing serum to refine the look of pores and oiliness.', 'A lightweight water-based serum with 10% niacinamide to visibly even tone and control shine.', 'Reduces the look of pores\nBalances oil\nEvens skin tone', 'Apply 3-4 drops to clean skin morning and/or night before moisturizer.', 'Patch test first. Reduce frequency if you feel tingling.', '30 ml', 19.00, 0.00, 'serum', 1, 1, 900, 210, 0.00, 0, '2026-06-01 22:38:34', '2026-10-14 22:38:34'),
(7, 3, 2, 'Vitamin C Brightening Serum', 'vitamin-c-brightening-serum', 'SKN-SR-002', 'Antioxidant serum that helps fade dark spots and boost glow.', 'A stabilised vitamin C serum with vitamin E to brighten and protect.', 'Brightens dull skin\nHelps fade dark spots\nAntioxidant protection', 'Apply 3-4 drops to clean skin in the morning, then moisturizer and sunscreen.', 'Can sting on sensitive skin. Store in a cool, dark place.', '30 ml', 28.00, 20.00, 'serum', 1, 1, 760, 150, 0.00, 0, '2026-06-11 22:38:34', '2026-10-14 22:38:34'),
(8, 3, 4, 'Hyaluronic Acid Hydration Serum', 'hyaluronic-acid-hydration-serum', 'SKN-SR-003', 'Multi-weight hyaluronic acid serum for plump, hydrated skin.', 'A fragrance-free hydration serum that layers well under any moisturizer.', 'Deep hydration\nPlumps fine dehydration lines\nSuits all skin types', 'Apply to damp skin morning and night, then seal with moisturizer.', 'For external use only.', '30 ml', 24.00, 0.00, 'serum', 0, 1, 610, 130, 0.00, 0, '2026-06-18 22:38:34', '2026-10-14 22:38:34'),
(9, 3, 3, 'Retinol 0.3% Night Serum', 'retinol-0-3-night-serum', 'SKN-SR-004', 'Beginner-friendly retinol serum for texture, lines and breakouts.', 'A gentle retinol serum in a squalane base that supports smoother, clearer-looking skin.', 'Smooths fine lines\nRefines texture\nHelps with breakouts', 'Apply a pea-sized amount at night 2 times per week, increasing slowly. Always use sunscreen in the morning.', 'Do not use during pregnancy or while breastfeeding. May cause dryness or peeling. Avoid combining with strong acids on the same night.', '30 ml', 32.00, 0.00, 'serum', 1, 1, 820, 110, 0.00, 0, '2026-07-01 22:38:34', '2026-10-14 22:38:34'),
(10, 4, 3, 'Ceramide Barrier Moisturizer', 'ceramide-barrier-moisturizer', 'SKN-MO-001', 'Rich yet non-greasy cream that repairs the skin barrier.', 'Ceramides, shea butter and panthenol help restore comfort to dry or sensitive skin.', 'Restores the barrier\nLong-lasting moisture\nCalms sensitive skin', 'Apply to face and neck morning and night after serum.', 'For external use only.', '50 ml', 26.00, 0.00, 'moisturizer', 1, 1, 700, 190, 0.00, 0, '2026-06-08 22:38:34', '2026-10-14 22:38:34'),
(11, 4, 4, 'Oil-Free Gel Moisturizer', 'oil-free-gel-moisturizer', 'SKN-MO-002', 'Weightless gel cream that hydrates without shine.', 'A cooling gel moisturizer with hyaluronic acid and niacinamide for oily and combination skin.', 'Oil-free hydration\nMattifying finish\nWorks under makeup', 'Apply a pea to almond sized amount to clean skin morning and night.', 'Avoid eye area.', '50 ml', 21.00, 12.00, 'moisturizer', 0, 1, 480, 140, 0.00, 0, '2026-07-08 22:38:34', '2026-10-14 22:38:34'),
(12, 4, 2, 'Rich Shea Night Cream', 'rich-shea-night-cream', 'SKN-MO-003', 'Nourishing overnight cream for very dry skin.', 'Shea butter and squalane wrap skin in comforting moisture while you sleep.', 'Intense overnight moisture\nSoftens rough patches\nNourishing texture', 'Smooth over face and neck as the last step of your evening routine.', 'Contains fragrance. May feel heavy on oily skin.', '50 ml', 29.00, 0.00, 'moisturizer', 0, 1, 300, 65, 0.00, 0, '2026-07-16 22:38:34', '2026-10-14 22:38:34'),
(13, 5, 5, 'Mineral Sunscreen SPF 50', 'mineral-sunscreen-spf-50', 'SKN-SP-001', 'Broad-spectrum zinc oxide sunscreen, gentle on sensitive skin.', 'A fragrance-free mineral sunscreen with a smooth, tint-free finish.', 'Broad-spectrum SPF 50\nGentle for sensitive skin\nFragrance-free', 'Apply generously 15 minutes before sun exposure. Reapply every 2 hours.', 'May leave a slight white cast. Not a substitute for shade and protective clothing.', '50 ml', 24.00, 0.00, 'sunscreen', 1, 1, 950, 260, 0.00, 0, '2026-06-04 22:38:34', '2026-10-14 22:38:34'),
(14, 5, 5, 'Lightweight Daily Sunscreen SPF 40', 'lightweight-daily-sunscreen-spf-40', 'SKN-SP-002', 'Invisible, non-greasy daily SPF for oily skin.', 'A fast-absorbing fluid sunscreen with niacinamide that wears well under makeup.', 'Broad-spectrum SPF 40\nNon-greasy finish\nSuits oily skin', 'Apply generously as the final morning step. Reapply every 2 hours outdoors.', 'For external use only.', '50 ml', 20.00, 20.00, 'sunscreen', 0, 1, 680, 175, 0.00, 0, '2026-07-26 22:38:34', '2026-10-14 22:38:34'),
(15, 6, 1, 'Purifying Clay Mask', 'purifying-clay-mask', 'SKN-MK-001', 'Kaolin clay mask that absorbs excess oil and unclogs pores.', 'A weekly mask with kaolin and soothing centella to refresh oily or congested skin.', 'Absorbs excess oil\nRefreshes congested skin\nSoothing centella', 'Apply a thin layer on clean skin once or twice a week, leave 10 minutes, rinse well.', 'Do not let dry completely. Avoid eye area.', '100 ml', 17.00, 0.00, 'mask', 0, 1, 350, 70, 0.00, 0, '2026-08-05 22:38:34', '2026-10-14 22:38:34'),
(16, 6, 2, 'Soothing Centella Calming Mask', 'soothing-centella-calming-mask', 'SKN-MK-002', 'Calming cream mask for red, stressed or dry skin.', 'Centella, aloe and hyaluronic acid soothe visible redness and replenish moisture.', 'Calms redness\nHydrates dry skin\nFragrance-free', 'Apply an even layer 1-2 times per week, leave 10-15 minutes, then rinse or tissue off.', 'For external use only.', '100 ml', 18.50, 0.00, 'mask', 0, 1, 240, 45, 0.00, 0, '2026-08-15 22:38:34', '2026-10-14 22:38:34'),
(17, 7, 3, 'Spot Treatment Gel', 'spot-treatment-gel', 'SKN-TR-001', 'Targeted overnight gel for individual blemishes.', 'Salicylic acid and tea tree oil help dry out and calm occasional spots.', 'Targets blemishes\nFast-absorbing\nUse only where needed', 'Dab a small amount on clean spots at night. Do not apply to large areas.', 'Contains tea tree oil, a known allergen for some. Avoid broken skin.', '15 ml', 13.00, 0.00, 'treatment', 0, 1, 500, 160, 0.00, 0, '2026-08-20 22:38:34', '2026-10-14 22:38:34'),
(18, 7, 3, 'Azelaic Acid 10% Treatment', 'azelaic-acid-10-treatment', 'SKN-TR-002', 'Gentle treatment cream for redness, bumps and dark marks.', 'Azelaic acid with niacinamide helps even tone and calm breakouts, even on sensitive skin.', 'Calms redness\nFades post-acne marks\nGentle on sensitive skin', 'Apply a thin layer once daily in the evening, building to twice daily.', 'May tingle at first. Use sunscreen daily.', '30 ml', 27.00, 10.00, 'treatment', 1, 1, 430, 85, 0.00, 0, '2026-09-04 22:38:34', '2026-10-14 22:38:34'),
(19, 4, 2, 'Eye Repair Cream', 'eye-repair-cream', 'SKN-EY-001', 'Peptide eye cream to soften the look of dark circles and fine lines.', 'A silky eye cream with peptides, hyaluronic acid and vitamin E.', 'Softens fine lines\nBrightens the eye area\nHydrates delicate skin', 'Gently pat a small amount around the orbital bone morning and night.', 'Avoid direct contact with eyes.', '15 ml', 25.00, 0.00, 'eye_care', 0, 1, 280, 50, 0.00, 0, '2026-09-14 22:38:34', '2026-10-14 22:38:34'),
(20, 7, 4, 'Gentle Lactic Acid Treatment', 'gentle-lactic-acid-treatment', 'SKN-TR-003', 'Mild exfoliating treatment that smooths and hydrates.', 'A 5% lactic acid treatment for dull, rough skin that needs a gentle exfoliant.', 'Gently exfoliates\nImproves radiance\nHydrating', 'Use once or twice a week in the evening after cleansing. Follow with moisturizer.', 'Use sunscreen the next day. Not for broken skin.', '30 ml', 23.00, 0.00, 'exfoliant', 0, 1, 150, 20, 0.00, 0, '2026-10-04 22:38:34', '2026-10-14 22:38:34');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `image_path` varchar(255) NOT NULL COMMENT 'path relative to project root',
  `alt_text` varchar(190) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `alt_text`, `is_primary`, `sort_order`) VALUES
(1, 1, 'assets/images/products/p01.svg', 'Gentle Foaming Cleanser', 1, 0),
(2, 2, 'assets/images/products/p02.svg', 'Hydrating Cream Cleanser', 1, 0),
(3, 3, 'assets/images/products/p03.svg', 'Clarifying Salicylic Cleanser', 1, 0),
(4, 4, 'assets/images/products/p04.svg', 'Hydrating Facial Toner', 1, 0),
(5, 5, 'assets/images/products/p05.svg', 'Exfoliating AHA/BHA Toner', 1, 0),
(6, 6, 'assets/images/products/p06.svg', 'Niacinamide 10% Serum', 1, 0),
(7, 7, 'assets/images/products/p07.svg', 'Vitamin C Brightening Serum', 1, 0),
(8, 8, 'assets/images/products/p08.svg', 'Hyaluronic Acid Hydration Serum', 1, 0),
(9, 9, 'assets/images/products/p09.svg', 'Retinol 0.3% Night Serum', 1, 0),
(10, 10, 'assets/images/products/p10.svg', 'Ceramide Barrier Moisturizer', 1, 0),
(11, 11, 'assets/images/products/p11.svg', 'Oil-Free Gel Moisturizer', 1, 0),
(12, 12, 'assets/images/products/p12.svg', 'Rich Shea Night Cream', 1, 0),
(13, 13, 'assets/images/products/p13.svg', 'Mineral Sunscreen SPF 50', 1, 0),
(14, 14, 'assets/images/products/p14.svg', 'Lightweight Daily Sunscreen SPF 40', 1, 0),
(15, 15, 'assets/images/products/p15.svg', 'Purifying Clay Mask', 1, 0),
(16, 16, 'assets/images/products/p16.svg', 'Soothing Centella Calming Mask', 1, 0),
(17, 17, 'assets/images/products/p17.svg', 'Spot Treatment Gel', 1, 0),
(18, 18, 'assets/images/products/p18.svg', 'Azelaic Acid 10% Treatment', 1, 0),
(19, 19, 'assets/images/products/p19.svg', 'Eye Repair Cream', 1, 0),
(20, 20, 'assets/images/products/p20.svg', 'Gentle Lactic Acid Treatment', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `product_ingredients`
--

CREATE TABLE `product_ingredients` (
  `product_id` int(10) UNSIGNED NOT NULL,
  `ingredient_id` int(10) UNSIGNED NOT NULL,
  `position` smallint(5) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_ingredients`
--

INSERT INTO `product_ingredients` (`product_id`, `ingredient_id`, `position`) VALUES
(1, 1, 1),
(1, 2, 2),
(1, 14, 3),
(1, 16, 4),
(2, 1, 1),
(2, 2, 2),
(2, 10, 3),
(2, 11, 5),
(2, 14, 4),
(3, 1, 1),
(3, 2, 3),
(3, 5, 2),
(3, 15, 4),
(3, 16, 5),
(4, 1, 1),
(4, 2, 2),
(4, 3, 3),
(4, 14, 5),
(4, 16, 4),
(5, 1, 1),
(5, 2, 4),
(5, 5, 3),
(5, 6, 2),
(5, 14, 5),
(5, 18, 6),
(6, 1, 1),
(6, 2, 3),
(6, 4, 2),
(6, 14, 4),
(6, 15, 5),
(7, 1, 1),
(7, 2, 3),
(7, 3, 5),
(7, 9, 2),
(7, 25, 4),
(8, 1, 1),
(8, 2, 3),
(8, 3, 2),
(8, 14, 4),
(9, 1, 1),
(9, 2, 5),
(9, 8, 2),
(9, 11, 3),
(9, 25, 4),
(10, 1, 1),
(10, 2, 3),
(10, 10, 2),
(10, 11, 4),
(10, 14, 6),
(10, 22, 5),
(11, 1, 1),
(11, 2, 2),
(11, 3, 3),
(11, 4, 4),
(11, 14, 5),
(12, 1, 1),
(12, 2, 4),
(12, 11, 3),
(12, 18, 6),
(12, 22, 2),
(12, 25, 5),
(13, 2, 3),
(13, 11, 5),
(13, 12, 1),
(13, 13, 2),
(13, 25, 4),
(14, 1, 1),
(14, 2, 3),
(14, 4, 4),
(14, 12, 2),
(14, 25, 5),
(15, 1, 1),
(15, 2, 3),
(15, 15, 5),
(15, 16, 4),
(15, 23, 2),
(16, 1, 1),
(16, 2, 4),
(16, 3, 6),
(16, 14, 5),
(16, 15, 2),
(16, 16, 3),
(17, 1, 1),
(17, 2, 4),
(17, 5, 2),
(17, 16, 5),
(17, 17, 3),
(18, 1, 1),
(18, 2, 3),
(18, 4, 4),
(18, 14, 5),
(18, 21, 2),
(19, 1, 1),
(19, 2, 4),
(19, 3, 2),
(19, 11, 6),
(19, 24, 3),
(19, 25, 5),
(20, 1, 1),
(20, 2, 3),
(20, 3, 4),
(20, 7, 2),
(20, 14, 5);

-- --------------------------------------------------------

--
-- Table structure for table `product_skin_concerns`
--

CREATE TABLE `product_skin_concerns` (
  `product_id` int(10) UNSIGNED NOT NULL,
  `skin_concern_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_skin_concerns`
--

INSERT INTO `product_skin_concerns` (`product_id`, `skin_concern_id`) VALUES
(1, 5),
(1, 8),
(2, 3),
(2, 4),
(3, 1),
(3, 5),
(3, 8),
(4, 4),
(4, 9),
(5, 1),
(5, 7),
(5, 8),
(5, 9),
(6, 2),
(6, 5),
(6, 7),
(6, 8),
(7, 2),
(7, 6),
(7, 9),
(8, 4),
(8, 6),
(9, 1),
(9, 2),
(9, 6),
(9, 7),
(10, 3),
(10, 4),
(11, 5),
(11, 8),
(12, 4),
(12, 6),
(13, 3),
(13, 11),
(14, 5),
(14, 11),
(15, 1),
(15, 5),
(15, 8),
(16, 3),
(16, 4),
(17, 1),
(18, 1),
(18, 2),
(18, 3),
(19, 6),
(19, 10),
(20, 7),
(20, 9);

-- --------------------------------------------------------

--
-- Table structure for table `product_skin_types`
--

CREATE TABLE `product_skin_types` (
  `product_id` int(10) UNSIGNED NOT NULL,
  `skin_type` enum('normal','dry','oily','combination','sensitive') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_skin_types`
--

INSERT INTO `product_skin_types` (`product_id`, `skin_type`) VALUES
(1, 'normal'),
(1, 'oily'),
(1, 'combination'),
(1, 'sensitive'),
(2, 'normal'),
(2, 'dry'),
(2, 'sensitive'),
(3, 'oily'),
(3, 'combination'),
(4, 'normal'),
(4, 'dry'),
(4, 'oily'),
(4, 'combination'),
(4, 'sensitive'),
(5, 'normal'),
(5, 'oily'),
(5, 'combination'),
(6, 'normal'),
(6, 'oily'),
(6, 'combination'),
(7, 'normal'),
(7, 'dry'),
(7, 'combination'),
(8, 'normal'),
(8, 'dry'),
(8, 'oily'),
(8, 'combination'),
(8, 'sensitive'),
(9, 'normal'),
(9, 'oily'),
(9, 'combination'),
(10, 'normal'),
(10, 'dry'),
(10, 'sensitive'),
(11, 'oily'),
(11, 'combination'),
(12, 'normal'),
(12, 'dry'),
(13, 'normal'),
(13, 'dry'),
(13, 'oily'),
(13, 'combination'),
(13, 'sensitive'),
(14, 'normal'),
(14, 'oily'),
(14, 'combination'),
(15, 'oily'),
(15, 'combination'),
(16, 'dry'),
(16, 'sensitive'),
(17, 'oily'),
(17, 'combination'),
(18, 'normal'),
(18, 'combination'),
(18, 'sensitive'),
(19, 'normal'),
(19, 'dry'),
(19, 'oily'),
(19, 'combination'),
(19, 'sensitive'),
(20, 'normal'),
(20, 'dry');

-- --------------------------------------------------------

--
-- Table structure for table `promotions`
--

CREATE TABLE `promotions` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `promo_type` enum('product_discount','flash_sale','bundle','bogo','seasonal','first_order') NOT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL DEFAULT 'percentage',
  `discount_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `category_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'optional: apply to a whole category',
  `banner_image` varchar(255) DEFAULT NULL,
  `priority` int(11) NOT NULL DEFAULT 0,
  `starts_at` datetime DEFAULT NULL,
  `ends_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promotion_products`
--

CREATE TABLE `promotion_products` (
  `promotion_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rate_limits`
--

CREATE TABLE `rate_limits` (
  `bucket_key` varchar(190) NOT NULL,
  `hits` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `window_start` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recently_viewed`
--

CREATE TABLE `recently_viewed` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `viewed_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `refunds`
--

CREATE TABLE `refunds` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `return_id` int(10) UNSIGNED DEFAULT NULL,
  `payment_id` int(10) UNSIGNED DEFAULT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reason` varchar(300) DEFAULT NULL,
  `status` enum('pending','processed','rejected') NOT NULL DEFAULT 'pending',
  `processed_by` int(10) UNSIGNED DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `returns`
--

CREATE TABLE `returns` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `order_item_id` int(10) UNSIGNED DEFAULT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `reason` varchar(120) NOT NULL,
  `details` varchar(1000) DEFAULT NULL,
  `status` enum('requested','approved','rejected','received','refunded') NOT NULL DEFAULT 'requested',
  `admin_note` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED DEFAULT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL,
  `title` varchar(150) DEFAULT NULL,
  `body` text DEFAULT NULL,
  `is_verified_purchase` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `helpful_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `report_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `review_helpful`
--

CREATE TABLE `review_helpful` (
  `review_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `review_images`
--

CREATE TABLE `review_images` (
  `id` int(10) UNSIGNED NOT NULL,
  `review_id` int(10) UNSIGNED NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `review_reports`
--

CREATE TABLE `review_reports` (
  `id` int(10) UNSIGNED NOT NULL,
  `review_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `reason` varchar(300) NOT NULL,
  `status` enum('open','dismissed','actioned') NOT NULL DEFAULT 'open',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `routine_steps`
--

CREATE TABLE `routine_steps` (
  `id` int(10) UNSIGNED NOT NULL,
  `routine_id` int(10) UNSIGNED NOT NULL,
  `period` enum('morning','evening','weekly') NOT NULL,
  `step_order` tinyint(3) UNSIGNED NOT NULL,
  `step_name` varchar(60) NOT NULL,
  `product_id` int(10) UNSIGNED DEFAULT NULL,
  `instructions` varchar(600) DEFAULT NULL,
  `precautions` varchar(600) DEFAULT NULL,
  `reason` varchar(600) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `search_logs`
--

CREATE TABLE `search_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `term` varchar(190) NOT NULL,
  `results_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `setting_key` varchar(80) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
('consultation_default_fee', '20.00', '2026-10-14 22:38:34'),
('free_shipping_threshold', '60.00', '2026-10-14 22:38:34'),
('low_stock_alert_threshold', '5', '2026-10-14 22:38:34'),
('loyalty_earn_rate', '1', '2026-10-14 22:38:34'),
('loyalty_max_redeem_percent', '50', '2026-10-14 22:38:34'),
('loyalty_point_value', '0.01', '2026-10-14 22:38:34'),
('return_window_days', '14', '2026-10-14 22:38:34'),
('shipping_flat_fee', '5.00', '2026-10-14 22:38:34'),
('store_name', 'Skinova', '2026-10-14 22:38:34');

-- --------------------------------------------------------

--
-- Table structure for table `shipments`
--

CREATE TABLE `shipments` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `carrier` varchar(80) DEFAULT NULL,
  `tracking_number` varchar(80) DEFAULT NULL,
  `status` enum('pending','packed','shipped','out_for_delivery','delivered') NOT NULL DEFAULT 'pending',
  `estimated_delivery` date DEFAULT NULL,
  `shipped_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `skincare_routines`
--

CREATE TABLE `skincare_routines` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `skin_type` varchar(30) DEFAULT NULL,
  `concerns_summary` varchar(300) DEFAULT NULL,
  `budget_max` decimal(10,2) DEFAULT NULL,
  `total_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `generation_source` enum('rule_based','ai') NOT NULL DEFAULT 'rule_based',
  `ai_provider` varchar(40) DEFAULT NULL,
  `precautions` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `skin_analyses`
--

CREATE TABLE `skin_analyses` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `image_path` varchar(255) NOT NULL COMMENT 'private file under assets/uploads/skin-images',
  `method` enum('ai_vision','image_metrics') NOT NULL,
  `provider` varchar(40) DEFAULT NULL,
  `results` longtext DEFAULT NULL COMMENT 'JSON: acne_like_spots, dark_spots, redness, oiliness, uneven_tone',
  `summary` text DEFAULT NULL,
  `consent_given` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `skin_concerns`
--

CREATE TABLE `skin_concerns` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(80) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `skin_concerns`
--

INSERT INTO `skin_concerns` (`id`, `name`, `slug`, `description`) VALUES
(1, 'Acne & Breakouts', 'acne', 'Pimples, blackheads and recurring breakouts.'),
(2, 'Dark Spots', 'dark-spots', 'Hyperpigmentation, post-acne marks and uneven patches.'),
(3, 'Redness & Irritation', 'redness', 'Visible redness, sensitivity and easily irritated skin.'),
(4, 'Dryness & Dehydration', 'dryness', 'Tight, flaky or dehydrated skin.'),
(5, 'Excess Oil', 'oiliness', 'Shine and an oily feel through the day.'),
(6, 'Fine Lines & Wrinkles', 'aging', 'Early signs of ageing and loss of firmness.'),
(7, 'Uneven Texture', 'texture', 'Rough or bumpy skin surface.'),
(8, 'Large Pores', 'pores', 'Visible, enlarged-looking pores.'),
(9, 'Dullness', 'dullness', 'Lack of radiance or glow.'),
(10, 'Dark Circles', 'dark-circles', 'Under-eye darkness and puffiness.'),
(11, 'Sun Protection', 'sun-protection', 'Daily protection from UV damage.');

-- --------------------------------------------------------

--
-- Table structure for table `skin_profiles`
--

CREATE TABLE `skin_profiles` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `skin_type` enum('normal','dry','oily','combination','sensitive') DEFAULT NULL,
  `age` tinyint(3) UNSIGNED DEFAULT NULL,
  `budget_min` decimal(10,2) DEFAULT NULL,
  `budget_max` decimal(10,2) DEFAULT NULL,
  `preferences` varchar(500) DEFAULT NULL COMMENT 'e.g. fragrance-free, vegan, minimal routine',
  `allergies` varchar(500) DEFAULT NULL COMMENT 'free-text allergies reported by the user',
  `routine_complexity` enum('minimal','standard','extended') NOT NULL DEFAULT 'standard',
  `consent_given` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `skin_profiles`
--

INSERT INTO `skin_profiles` (`id`, `user_id`, `skin_type`, `age`, `budget_min`, `budget_max`, `preferences`, `allergies`, `routine_complexity`, `consent_given`, `created_at`, `updated_at`) VALUES
(1, 5, 'combination', 27, 20.00, 120.00, 'fragrance-free, lightweight textures', NULL, 'standard', 1, '2026-10-14 22:38:34', '2026-10-14 22:38:34');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('customer','admin','consultant') NOT NULL DEFAULT 'customer',
  `status` enum('active','pending','suspended') NOT NULL DEFAULT 'pending',
  `email_verified_at` datetime DEFAULT NULL,
  `phone_verified_at` datetime DEFAULT NULL,
  `loyalty_points` int(11) NOT NULL DEFAULT 0,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `password_hash`, `role`, `status`, `email_verified_at`, `phone_verified_at`, `loyalty_points`, `last_login_at`, `created_at`, `updated_at`) VALUES
(1, 'Admin User', 'admin@skinova.test', '+15550100001', '$2y$10$.kOQYwP7qUHkz82gOK4GnuJS512dMv4cxj14XibTWbOqzWdYiHarG', 'admin', 'active', '2026-10-14 22:38:34', '2026-10-14 22:38:34', 0, NULL, '2026-05-17 22:38:34', '2026-10-14 22:38:34'),
(2, 'Nadia Karim', 'nadia@skinova.test', '+15550100002', '$2y$10$.kOQYwP7qUHkz82gOK4GnuJS512dMv4cxj14XibTWbOqzWdYiHarG', 'consultant', 'active', '2026-10-14 22:38:34', '2026-10-14 22:38:34', 0, NULL, '2026-05-27 22:38:34', '2026-10-14 22:38:34'),
(3, 'Marcus Lee', 'marcus@skinova.test', '+15550100003', '$2y$10$.kOQYwP7qUHkz82gOK4GnuJS512dMv4cxj14XibTWbOqzWdYiHarG', 'consultant', 'active', '2026-10-14 22:38:34', '2026-10-14 22:38:34', 0, NULL, '2026-06-06 22:38:34', '2026-10-14 22:38:34'),
(4, 'Priya Sharma', 'priya@skinova.test', '+15550100004', '$2y$10$.kOQYwP7qUHkz82gOK4GnuJS512dMv4cxj14XibTWbOqzWdYiHarG', 'consultant', 'active', '2026-10-14 22:38:34', '2026-10-14 22:38:34', 0, NULL, '2026-06-16 22:38:34', '2026-10-14 22:38:34'),
(5, 'Aisha Rahman', 'aisha@skinova.test', '+15550100005', '$2y$10$.kOQYwP7qUHkz82gOK4GnuJS512dMv4cxj14XibTWbOqzWdYiHarG', 'customer', 'active', '2026-10-14 22:38:34', '2026-10-14 22:38:34', 120, NULL, '2026-07-16 22:38:34', '2026-10-14 22:38:34'),
(6, 'Daniel Carter', 'daniel@skinova.test', '+15550100006', '$2y$10$.kOQYwP7qUHkz82gOK4GnuJS512dMv4cxj14XibTWbOqzWdYiHarG', 'customer', 'active', '2026-10-14 22:38:34', NULL, 0, NULL, '2026-09-24 22:38:34', '2026-10-14 22:38:34');

-- --------------------------------------------------------

--
-- Table structure for table `user_addresses`
--

CREATE TABLE `user_addresses` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `label` varchar(50) NOT NULL DEFAULT 'Home',
  `recipient_name` varchar(120) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `address_line1` varchar(190) NOT NULL,
  `address_line2` varchar(190) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) NOT NULL,
  `country` varchar(100) NOT NULL DEFAULT 'United States',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_addresses`
--

INSERT INTO `user_addresses` (`id`, `user_id`, `label`, `recipient_name`, `phone`, `address_line1`, `address_line2`, `city`, `state`, `postal_code`, `country`, `is_default`, `created_at`, `updated_at`) VALUES
(1, 5, 'Home', 'Aisha Rahman', '+15550100005', '12 Lotus Avenue', 'Apt 4B', 'Springfield', 'IL', '62704', 'United States', 1, '2026-10-14 22:38:34', '2026-10-14 22:38:34'),
(2, 5, 'Office', 'Aisha Rahman', '+15550100005', '500 Market Street', 'Floor 3', 'Springfield', 'IL', '62701', 'United States', 0, '2026-10-14 22:38:34', '2026-10-14 22:38:34'),
(3, 6, 'Home', 'Daniel Carter', '+15550100006', '88 Maple Road', NULL, 'Riverton', 'UT', '84065', 'United States', 1, '2026-10-14 22:38:34', '2026-10-14 22:38:34');

-- --------------------------------------------------------

--
-- Table structure for table `user_ingredient_sensitivities`
--

CREATE TABLE `user_ingredient_sensitivities` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `ingredient_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_ingredient_sensitivities`
--

INSERT INTO `user_ingredient_sensitivities` (`user_id`, `ingredient_id`) VALUES
(5, 18);

-- --------------------------------------------------------

--
-- Table structure for table `user_profiles`
--

CREATE TABLE `user_profiles` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('female','male','other','prefer_not_to_say') DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `bio` varchar(500) DEFAULT NULL,
  `skin_data_consent` tinyint(1) NOT NULL DEFAULT 0,
  `skin_data_consent_at` datetime DEFAULT NULL,
  `marketing_opt_in` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_profiles`
--

INSERT INTO `user_profiles` (`id`, `user_id`, `date_of_birth`, `gender`, `avatar`, `bio`, `skin_data_consent`, `skin_data_consent_at`, `marketing_opt_in`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, NULL, NULL, 'Store administrator', 0, NULL, 0, '2026-10-14 22:38:34', '2026-10-14 22:38:34'),
(2, 2, '1984-03-12', 'female', NULL, 'Board-certified dermatologist focusing on acne and sensitive skin.', 0, NULL, 0, '2026-10-14 22:38:34', '2026-10-14 22:38:34'),
(3, 3, '1990-07-21', 'male', NULL, 'Licensed esthetician specialising in routines for combination skin.', 0, NULL, 0, '2026-10-14 22:38:34', '2026-10-14 22:38:34'),
(4, 4, '1987-11-02', 'female', NULL, 'Cosmetic chemist who loves explaining ingredients.', 0, NULL, 0, '2026-10-14 22:38:34', '2026-10-14 22:38:34'),
(5, 5, '1998-05-14', 'female', NULL, NULL, 1, '2026-08-15 22:38:34', 1, '2026-10-14 22:38:34', '2026-10-14 22:38:34'),
(6, 6, '1993-09-30', 'male', NULL, NULL, 0, NULL, 0, '2026-10-14 22:38:34', '2026-10-14 22:38:34');

-- --------------------------------------------------------

--
-- Table structure for table `user_skin_concerns`
--

CREATE TABLE `user_skin_concerns` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `skin_concern_id` int(10) UNSIGNED NOT NULL,
  `severity` enum('mild','moderate','severe') NOT NULL DEFAULT 'moderate'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_skin_concerns`
--

INSERT INTO `user_skin_concerns` (`user_id`, `skin_concern_id`, `severity`) VALUES
(5, 1, 'moderate'),
(5, 2, 'mild'),
(5, 5, 'moderate');

-- --------------------------------------------------------

--
-- Table structure for table `verification_tokens`
--

CREATE TABLE `verification_tokens` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `type` enum('email','phone','password_reset') NOT NULL,
  `token_hash` char(64) NOT NULL COMMENT 'sha256 of the token or OTP code',
  `attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wishlists`
--

CREATE TABLE `wishlists` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wishlist_items`
--

CREATE TABLE `wishlist_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `wishlist_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `price_when_added` decimal(10,2) NOT NULL,
  `added_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activity_user` (`user_id`),
  ADD KEY `idx_activity_action` (`action`),
  ADD KEY `idx_activity_created` (`created_at`);

--
-- Indexes for table `ai_conversations`
--
ALTER TABLE `ai_conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ai_conv_user` (`user_id`,`updated_at`);

--
-- Indexes for table `ai_knowledge_base`
--
ALTER TABLE `ai_knowledge_base`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_kb_category` (`category`,`is_active`),
  ADD KEY `idx_kb_faq` (`is_faq`,`is_active`);
ALTER TABLE `ai_knowledge_base` ADD FULLTEXT KEY `ft_kb_text` (`question`,`answer`,`keywords`);

--
-- Indexes for table `ai_messages`
--
ALTER TABLE `ai_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ai_messages_conv` (`conversation_id`,`id`);

--
-- Indexes for table `ai_query_logs`
--
ALTER TABLE `ai_query_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_aiql_module` (`module`,`created_at`),
  ADD KEY `idx_aiql_user` (`user_id`),
  ADD KEY `idx_aiql_created` (`created_at`);

--
-- Indexes for table `ai_recommendations`
--
ALTER TABLE `ai_recommendations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_air_user` (`user_id`,`created_at`),
  ADD KEY `idx_air_product` (`product_id`),
  ADD KEY `idx_air_batch` (`batch_key`);

--
-- Indexes for table `blogs`
--
ALTER TABLE `blogs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_blogs_slug` (`slug`),
  ADD KEY `idx_blogs_status_published` (`status`,`published_at`),
  ADD KEY `idx_blogs_category` (`category_id`),
  ADD KEY `fk_blogs_author` (`author_id`);
ALTER TABLE `blogs` ADD FULLTEXT KEY `ft_blogs_text` (`title`,`excerpt`,`content`);

--
-- Indexes for table `blog_categories`
--
ALTER TABLE `blog_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_blog_categories_slug` (`slug`);

--
-- Indexes for table `blog_comments`
--
ALTER TABLE `blog_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bc_blog` (`blog_id`,`status`),
  ADD KEY `fk_bc_user` (`user_id`),
  ADD KEY `fk_bc_parent` (`parent_id`);

--
-- Indexes for table `blog_likes`
--
ALTER TABLE `blog_likes`
  ADD PRIMARY KEY (`blog_id`,`user_id`),
  ADD KEY `fk_bl_user` (`user_id`);

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_brands_slug` (`slug`);

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_carts_user` (`user_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cart_product` (`cart_id`,`product_id`),
  ADD KEY `idx_cart_items_product` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_categories_slug` (`slug`),
  ADD KEY `idx_categories_parent` (`parent_id`);

--
-- Indexes for table `consultants`
--
ALTER TABLE `consultants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_consultants_user` (`user_id`);

--
-- Indexes for table `consultations`
--
ALTER TABLE `consultations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_consultations_user` (`user_id`,`status`),
  ADD KEY `idx_consultations_consultant` (`consultant_id`,`status`),
  ADD KEY `idx_consultations_created` (`created_at`);

--
-- Indexes for table `consultation_messages`
--
ALTER TABLE `consultation_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cm_consultation` (`consultation_id`,`id`),
  ADD KEY `idx_cm_unread` (`consultation_id`,`sender_id`,`is_read`),
  ADD KEY `fk_cm_sender` (`sender_id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_coupons_code` (`code`);

--
-- Indexes for table `coupon_usages`
--
ALTER TABLE `coupon_usages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_coupon_usage_order` (`coupon_id`,`order_id`),
  ADD KEY `idx_coupon_usage_user` (`coupon_id`,`user_id`),
  ADD KEY `fk_cu_user` (`user_id`),
  ADD KEY `fk_cu_order` (`order_id`);

--
-- Indexes for table `ingredients`
--
ALTER TABLE `ingredients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ingredients_slug` (`slug`),
  ADD KEY `idx_ingredients_name` (`name`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_inventory_product` (`product_id`);

--
-- Indexes for table `loyalty_transactions`
--
ALTER TABLE `loyalty_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_loyalty_user` (`user_id`,`created_at`),
  ADD KEY `fk_loyalty_order` (`order_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_user` (`user_id`,`is_read`,`created_at`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_orders_number` (`order_number`),
  ADD KEY `idx_orders_user` (`user_id`,`created_at`),
  ADD KEY `idx_orders_status` (`status`),
  ADD KEY `idx_orders_payment_status` (`payment_status`),
  ADD KEY `idx_orders_created` (`created_at`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_items_order` (`order_id`),
  ADD KEY `idx_order_items_product` (`product_id`);

--
-- Indexes for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_osh_order` (`order_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_payments_txn` (`provider`,`transaction_id`),
  ADD KEY `idx_payments_order` (`order_id`),
  ADD KEY `idx_payments_user` (`user_id`),
  ADD KEY `idx_payments_status` (`status`);

--
-- Indexes for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_prescriptions_user` (`user_id`),
  ADD KEY `idx_prescriptions_consultation` (`consultation_id`),
  ADD KEY `fk_prescriptions_uploader` (`uploaded_by`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_products_slug` (`slug`),
  ADD UNIQUE KEY `uq_products_sku` (`sku`),
  ADD KEY `idx_products_category` (`category_id`),
  ADD KEY `idx_products_brand` (`brand_id`),
  ADD KEY `idx_products_active_price` (`is_active`,`price`),
  ADD KEY `idx_products_sold` (`sold_count`),
  ADD KEY `idx_products_views` (`view_count`),
  ADD KEY `idx_products_created` (`created_at`),
  ADD KEY `idx_products_name` (`name`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_images_product` (`product_id`);

--
-- Indexes for table `product_ingredients`
--
ALTER TABLE `product_ingredients`
  ADD PRIMARY KEY (`product_id`,`ingredient_id`),
  ADD KEY `idx_pi_ingredient` (`ingredient_id`);

--
-- Indexes for table `product_skin_concerns`
--
ALTER TABLE `product_skin_concerns`
  ADD PRIMARY KEY (`product_id`,`skin_concern_id`),
  ADD KEY `idx_psc_concern` (`skin_concern_id`);

--
-- Indexes for table `product_skin_types`
--
ALTER TABLE `product_skin_types`
  ADD PRIMARY KEY (`product_id`,`skin_type`);

--
-- Indexes for table `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_promotions_active` (`is_active`,`starts_at`,`ends_at`),
  ADD KEY `idx_promotions_category` (`category_id`);

--
-- Indexes for table `promotion_products`
--
ALTER TABLE `promotion_products`
  ADD PRIMARY KEY (`promotion_id`,`product_id`),
  ADD KEY `idx_pp_product` (`product_id`);

--
-- Indexes for table `rate_limits`
--
ALTER TABLE `rate_limits`
  ADD PRIMARY KEY (`bucket_key`);

--
-- Indexes for table `recently_viewed`
--
ALTER TABLE `recently_viewed`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_recent_user_product` (`user_id`,`product_id`),
  ADD KEY `idx_recent_viewed_at` (`user_id`,`viewed_at`),
  ADD KEY `fk_recent_product` (`product_id`);

--
-- Indexes for table `refunds`
--
ALTER TABLE `refunds`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_refunds_order` (`order_id`),
  ADD KEY `idx_refunds_status` (`status`),
  ADD KEY `fk_refunds_return` (`return_id`),
  ADD KEY `fk_refunds_payment` (`payment_id`),
  ADD KEY `fk_refunds_user` (`user_id`);

--
-- Indexes for table `returns`
--
ALTER TABLE `returns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_returns_order` (`order_id`),
  ADD KEY `idx_returns_user` (`user_id`),
  ADD KEY `idx_returns_status` (`status`),
  ADD KEY `fk_returns_item` (`order_item_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_reviews_user_product` (`product_id`,`user_id`),
  ADD KEY `idx_reviews_status` (`status`),
  ADD KEY `idx_reviews_product_status` (`product_id`,`status`),
  ADD KEY `fk_reviews_user` (`user_id`),
  ADD KEY `fk_reviews_order` (`order_id`);

--
-- Indexes for table `review_helpful`
--
ALTER TABLE `review_helpful`
  ADD PRIMARY KEY (`review_id`,`user_id`),
  ADD KEY `fk_rh_user` (`user_id`);

--
-- Indexes for table `review_images`
--
ALTER TABLE `review_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_review_images_review` (`review_id`);

--
-- Indexes for table `review_reports`
--
ALTER TABLE `review_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_review_report_user` (`review_id`,`user_id`),
  ADD KEY `fk_rr_user` (`user_id`);

--
-- Indexes for table `routine_steps`
--
ALTER TABLE `routine_steps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_routine_steps_routine` (`routine_id`,`period`,`step_order`),
  ADD KEY `idx_routine_steps_product` (`product_id`);

--
-- Indexes for table `search_logs`
--
ALTER TABLE `search_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_search_term` (`term`),
  ADD KEY `idx_search_created` (`created_at`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `shipments`
--
ALTER TABLE `shipments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_shipments_order` (`order_id`),
  ADD KEY `idx_shipments_tracking` (`tracking_number`);

--
-- Indexes for table `skincare_routines`
--
ALTER TABLE `skincare_routines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_routines_user` (`user_id`,`created_at`);

--
-- Indexes for table `skin_analyses`
--
ALTER TABLE `skin_analyses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_skin_analyses_user` (`user_id`,`created_at`);

--
-- Indexes for table `skin_concerns`
--
ALTER TABLE `skin_concerns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_skin_concerns_slug` (`slug`);

--
-- Indexes for table `skin_profiles`
--
ALTER TABLE `skin_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_skin_profiles_user` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD UNIQUE KEY `uq_users_phone` (`phone`),
  ADD KEY `idx_users_role_status` (`role`,`status`),
  ADD KEY `idx_users_created` (`created_at`);

--
-- Indexes for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_addresses_user` (`user_id`);

--
-- Indexes for table `user_ingredient_sensitivities`
--
ALTER TABLE `user_ingredient_sensitivities`
  ADD PRIMARY KEY (`user_id`,`ingredient_id`),
  ADD KEY `idx_uis_ingredient` (`ingredient_id`);

--
-- Indexes for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_profiles_user` (`user_id`);

--
-- Indexes for table `user_skin_concerns`
--
ALTER TABLE `user_skin_concerns`
  ADD PRIMARY KEY (`user_id`,`skin_concern_id`),
  ADD KEY `idx_usc_concern` (`skin_concern_id`);

--
-- Indexes for table `verification_tokens`
--
ALTER TABLE `verification_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_vt_user_type` (`user_id`,`type`),
  ADD KEY `idx_vt_hash` (`token_hash`);

--
-- Indexes for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_wishlists_user` (`user_id`);

--
-- Indexes for table `wishlist_items`
--
ALTER TABLE `wishlist_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_wishlist_product` (`wishlist_id`,`product_id`),
  ADD KEY `idx_wishlist_items_product` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ai_conversations`
--
ALTER TABLE `ai_conversations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ai_knowledge_base`
--
ALTER TABLE `ai_knowledge_base`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ai_messages`
--
ALTER TABLE `ai_messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ai_query_logs`
--
ALTER TABLE `ai_query_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ai_recommendations`
--
ALTER TABLE `ai_recommendations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blogs`
--
ALTER TABLE `blogs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blog_categories`
--
ALTER TABLE `blog_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blog_comments`
--
ALTER TABLE `blog_comments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `brands`
--
ALTER TABLE `brands`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `consultants`
--
ALTER TABLE `consultants`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `consultations`
--
ALTER TABLE `consultations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `consultation_messages`
--
ALTER TABLE `consultation_messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `coupon_usages`
--
ALTER TABLE `coupon_usages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ingredients`
--
ALTER TABLE `ingredients`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `loyalty_transactions`
--
ALTER TABLE `loyalty_transactions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `promotions`
--
ALTER TABLE `promotions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recently_viewed`
--
ALTER TABLE `recently_viewed`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `refunds`
--
ALTER TABLE `refunds`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `returns`
--
ALTER TABLE `returns`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `review_images`
--
ALTER TABLE `review_images`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `review_reports`
--
ALTER TABLE `review_reports`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `routine_steps`
--
ALTER TABLE `routine_steps`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `search_logs`
--
ALTER TABLE `search_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shipments`
--
ALTER TABLE `shipments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `skincare_routines`
--
ALTER TABLE `skincare_routines`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `skin_analyses`
--
ALTER TABLE `skin_analyses`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `skin_concerns`
--
ALTER TABLE `skin_concerns`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `skin_profiles`
--
ALTER TABLE `skin_profiles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_profiles`
--
ALTER TABLE `user_profiles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `verification_tokens`
--
ALTER TABLE `verification_tokens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wishlists`
--
ALTER TABLE `wishlists`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wishlist_items`
--
ALTER TABLE `wishlist_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ai_conversations`
--
ALTER TABLE `ai_conversations`
  ADD CONSTRAINT `fk_ai_conv_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ai_messages`
--
ALTER TABLE `ai_messages`
  ADD CONSTRAINT `fk_ai_messages_conv` FOREIGN KEY (`conversation_id`) REFERENCES `ai_conversations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ai_recommendations`
--
ALTER TABLE `ai_recommendations`
  ADD CONSTRAINT `fk_air_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_air_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `blogs`
--
ALTER TABLE `blogs`
  ADD CONSTRAINT `fk_blogs_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_blogs_category` FOREIGN KEY (`category_id`) REFERENCES `blog_categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `blog_comments`
--
ALTER TABLE `blog_comments`
  ADD CONSTRAINT `fk_bc_blog` FOREIGN KEY (`blog_id`) REFERENCES `blogs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bc_parent` FOREIGN KEY (`parent_id`) REFERENCES `blog_comments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bc_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `blog_likes`
--
ALTER TABLE `blog_likes`
  ADD CONSTRAINT `fk_bl_blog` FOREIGN KEY (`blog_id`) REFERENCES `blogs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bl_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `fk_carts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `fk_cart_items_cart` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cart_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `consultants`
--
ALTER TABLE `consultants`
  ADD CONSTRAINT `fk_consultants_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `consultations`
--
ALTER TABLE `consultations`
  ADD CONSTRAINT `fk_consultations_consultant` FOREIGN KEY (`consultant_id`) REFERENCES `consultants` (`id`),
  ADD CONSTRAINT `fk_consultations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `consultation_messages`
--
ALTER TABLE `consultation_messages`
  ADD CONSTRAINT `fk_cm_consultation` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cm_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `coupon_usages`
--
ALTER TABLE `coupon_usages`
  ADD CONSTRAINT `fk_cu_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cu_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cu_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `fk_inventory_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loyalty_transactions`
--
ALTER TABLE `loyalty_transactions`
  ADD CONSTRAINT `fk_loyalty_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_loyalty_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD CONSTRAINT `fk_osh_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_payments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD CONSTRAINT `fk_prescriptions_consultation` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_prescriptions_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_prescriptions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_brand` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `fk_product_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_ingredients`
--
ALTER TABLE `product_ingredients`
  ADD CONSTRAINT `fk_pi_ingredient` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pi_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_skin_concerns`
--
ALTER TABLE `product_skin_concerns`
  ADD CONSTRAINT `fk_psc_concern` FOREIGN KEY (`skin_concern_id`) REFERENCES `skin_concerns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_psc_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_skin_types`
--
ALTER TABLE `product_skin_types`
  ADD CONSTRAINT `fk_pst_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `promotions`
--
ALTER TABLE `promotions`
  ADD CONSTRAINT `fk_promotions_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `promotion_products`
--
ALTER TABLE `promotion_products`
  ADD CONSTRAINT `fk_pp_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pp_promotion` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `recently_viewed`
--
ALTER TABLE `recently_viewed`
  ADD CONSTRAINT `fk_recent_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_recent_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `refunds`
--
ALTER TABLE `refunds`
  ADD CONSTRAINT `fk_refunds_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_refunds_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_refunds_return` FOREIGN KEY (`return_id`) REFERENCES `returns` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_refunds_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `returns`
--
ALTER TABLE `returns`
  ADD CONSTRAINT `fk_returns_item` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_returns_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_returns_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_reviews_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_reviews_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `review_helpful`
--
ALTER TABLE `review_helpful`
  ADD CONSTRAINT `fk_rh_review` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rh_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `review_images`
--
ALTER TABLE `review_images`
  ADD CONSTRAINT `fk_review_images_review` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `review_reports`
--
ALTER TABLE `review_reports`
  ADD CONSTRAINT `fk_rr_review` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `routine_steps`
--
ALTER TABLE `routine_steps`
  ADD CONSTRAINT `fk_routine_steps_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_routine_steps_routine` FOREIGN KEY (`routine_id`) REFERENCES `skincare_routines` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shipments`
--
ALTER TABLE `shipments`
  ADD CONSTRAINT `fk_shipments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `skincare_routines`
--
ALTER TABLE `skincare_routines`
  ADD CONSTRAINT `fk_routines_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `skin_analyses`
--
ALTER TABLE `skin_analyses`
  ADD CONSTRAINT `fk_skin_analyses_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `skin_profiles`
--
ALTER TABLE `skin_profiles`
  ADD CONSTRAINT `fk_skin_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD CONSTRAINT `fk_addresses_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_ingredient_sensitivities`
--
ALTER TABLE `user_ingredient_sensitivities`
  ADD CONSTRAINT `fk_uis_ingredient` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_uis_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD CONSTRAINT `fk_user_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_skin_concerns`
--
ALTER TABLE `user_skin_concerns`
  ADD CONSTRAINT `fk_usc_concern` FOREIGN KEY (`skin_concern_id`) REFERENCES `skin_concerns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_usc_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `verification_tokens`
--
ALTER TABLE `verification_tokens`
  ADD CONSTRAINT `fk_vt_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD CONSTRAINT `fk_wishlists_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist_items`
--
ALTER TABLE `wishlist_items`
  ADD CONSTRAINT `fk_wishlist_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_wishlist_items_wishlist` FOREIGN KEY (`wishlist_id`) REFERENCES `wishlists` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
