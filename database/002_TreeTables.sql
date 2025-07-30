-- Tree Database Schema
-- Version: 1.0
-- Description: Database schema for storing multiple tree structures using Composite pattern
-- Generated: 2025-07-30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- Table structure for table `trees`
-- --------------------------------------------------------

CREATE TABLE `trees` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL COMMENT 'Tree name/identifier',
    `description` text DEFAULT NULL COMMENT 'Optional description',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Soft delete flag',
    PRIMARY KEY (`id`),
    KEY `idx_trees_active` (`is_active`),
    KEY `idx_trees_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores multiple tree structures';

-- --------------------------------------------------------
-- Table structure for table `tree_nodes`
-- --------------------------------------------------------

CREATE TABLE `tree_nodes` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `tree_id` int(11) NOT NULL COMMENT 'Foreign key to trees table',
    `parent_id` int(11) DEFAULT NULL COMMENT 'Self-referencing foreign key, NULL for root nodes',
    `name` varchar(255) NOT NULL COMMENT 'Node name',
    `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT 'Position within siblings',
    `type_class` varchar(100) NOT NULL COMMENT 'Node type (e.g., SimpleNode, ButtonNode)',
    `type_data` text DEFAULT NULL COMMENT 'Node data serialized in JSON format',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_tree_nodes_tree_id` (`tree_id`),
    KEY `idx_tree_nodes_parent_id` (`parent_id`),
    KEY `idx_tree_nodes_sort_order` (`sort_order`),
    KEY `idx_tree_nodes_tree_parent_sort` (`tree_id`, `parent_id`, `sort_order`),
    KEY `idx_tree_nodes_created_at` (`created_at`), -- For time-based queries
    CONSTRAINT `fk_tree_nodes_tree_id` FOREIGN KEY (`tree_id`) REFERENCES `trees` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tree_nodes_parent_id` FOREIGN KEY (`parent_id`) REFERENCES `tree_nodes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores individual tree nodes with hierarchy';

-- --------------------------------------------------------
-- Sample data for testing
-- --------------------------------------------------------

-- Insert sample trees
INSERT INTO `trees` (`id`, `name`, `description`, `created_at`, `updated_at`, `is_active`) VALUES
(1, 'Company Structure', 'Organizational chart for the company', NOW(), NOW(), 1),
(2, 'File System', 'File system tree structure', NOW(), NOW(), 1),
(3, 'Menu System', 'Website navigation menu', NOW(), NOW(), 1),
(4, 'Product Catalog', 'E-commerce product categories', NOW(), NOW(), 1);

-- Insert sample tree nodes for Company Structure (Tree ID: 1)
INSERT INTO `tree_nodes` (`id`, `tree_id`, `parent_id`, `name`, `sort_order`, `type_class`, `type_data`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'CEO', 0, 'ButtonNode', '{"button_text": "Manage", "button_action": "manageCEO()"}', NOW(), NOW()),
(2, 1, 1, 'CTO', 0, 'SimpleNode', '{}', NOW(), NOW()),
(3, 1, 1, 'CFO', 1, 'SimpleNode', '{}', NOW(), NOW()),
(4, 1, 2, 'Dev Team', 0, 'SimpleNode', '{}', NOW(), NOW()),
(5, 1, 2, 'QA Team', 1, 'SimpleNode', '{}', NOW(), NOW()),
(6, 1, 3, 'Finance Team', 0, 'SimpleNode', '{}', NOW(), NOW()),
(7, 1, 2, 'DevOps', 2, 'ButtonNode', '{"button_text": "Deploy", "button_action": "deploySystem()"}', NOW(), NOW());

-- Insert sample tree nodes for File System (Tree ID: 2)
INSERT INTO `tree_nodes` (`id`, `tree_id`, `parent_id`, `name`, `sort_order`, `type_class`, `type_data`, `created_at`, `updated_at`) VALUES
(8, 2, NULL, 'Root', 0, 'SimpleNode', '{}', NOW(), NOW()),
(9, 2, 8, 'Documents', 0, 'SimpleNode', '{}', NOW(), NOW()),
(10, 2, 8, 'Pictures', 1, 'SimpleNode', '{}', NOW(), NOW()),
(11, 2, 9, 'Work', 0, 'SimpleNode', '{}', NOW(), NOW()),
(12, 2, 9, 'Personal', 1, 'SimpleNode', '{}', NOW(), NOW()),
(13, 2, 10, 'Vacation', 0, 'SimpleNode', '{}', NOW(), NOW()),
(14, 2, 10, 'Family', 1, 'SimpleNode', '{}', NOW(), NOW()),
(15, 2, 8, 'Downloads', 2, 'ButtonNode', '{"button_text": "Clear", "button_action": "clearDownloads()"}', NOW(), NOW());

-- Insert sample tree nodes for Menu System (Tree ID: 3)
INSERT INTO `tree_nodes` (`id`, `tree_id`, `parent_id`, `name`, `sort_order`, `type_class`, `type_data`, `created_at`, `updated_at`) VALUES
(16, 3, NULL, 'Home', 0, 'SimpleNode', '{}', NOW(), NOW()),
(17, 3, NULL, 'Products', 1, 'ButtonNode', '{"button_text": "View All", "button_action": "viewAllProducts()"}', NOW(), NOW()),
(18, 3, NULL, 'About', 2, 'SimpleNode', '{}', NOW(), NOW()),
(19, 3, 17, 'Software', 0, 'SimpleNode', '{}', NOW(), NOW()),
(20, 3, 17, 'Hardware', 1, 'SimpleNode', '{}', NOW(), NOW()),
(21, 3, 19, 'Desktop Apps', 0, 'SimpleNode', '{}', NOW(), NOW()),
(22, 3, 19, 'Mobile Apps', 1, 'SimpleNode', '{}', NOW(), NOW()),
(23, 3, NULL, 'Contact', 3, 'ButtonNode', '{"button_text": "Email Us", "button_action": "openContactForm()"}', NOW(), NOW());

-- Insert sample tree nodes for Product Catalog (Tree ID: 4)
INSERT INTO `tree_nodes` (`id`, `tree_id`, `parent_id`, `name`, `sort_order`, `type_class`, `type_data`, `created_at`, `updated_at`) VALUES
(24, 4, NULL, 'Electronics', 0, 'SimpleNode', '{}', NOW(), NOW()),
(25, 4, NULL, 'Clothing', 1, 'SimpleNode', '{}', NOW(), NOW()),
(26, 4, NULL, 'Books', 2, 'SimpleNode', '{}', NOW(), NOW()),
(27, 4, 24, 'Computers', 0, 'ButtonNode', '{"button_text": "Shop Now", "button_action": "shopComputers()"}', NOW(), NOW()),
(28, 4, 24, 'Phones', 1, 'ButtonNode', '{"button_text": "Shop Now", "button_action": "shopPhones()"}', NOW(), NOW()),
(29, 4, 25, 'Men', 0, 'SimpleNode', '{}', NOW(), NOW()),
(30, 4, 25, 'Women', 1, 'SimpleNode', '{}', NOW(), NOW()),
(31, 4, 26, 'Fiction', 0, 'SimpleNode', '{}', NOW(), NOW()),
(32, 4, 26, 'Non-Fiction', 1, 'SimpleNode', '{}', NOW(), NOW()),
(33, 4, NULL, 'Sale Items', 3, 'ButtonNode', '{"button_text": "View Sale", "button_action": "viewSaleItems()"}', NOW(), NOW());

-- --------------------------------------------------------
-- Example queries for working with the simplified system
-- --------------------------------------------------------

-- Get all nodes with their type information
-- SELECT id, name, type_class, type_data FROM tree_nodes WHERE tree_id = 1 ORDER BY sort_order;

-- Get all button nodes
-- SELECT id, name, type_data FROM tree_nodes WHERE type_class = 'ButtonNode';

-- Get all simple nodes
-- SELECT id, name FROM tree_nodes WHERE type_class = 'SimpleNode';

-- Get tree structure with type information
-- SELECT 
--     tn.id,
--     tn.name,
--     tn.type_class,
--     tn.type_data,
--     CONCAT(REPEAT('  ', (LENGTH(tn.path) - LENGTH(REPLACE(tn.path, '.', '')))), tn.name) as display_name
-- FROM tree_nodes tn
-- WHERE tn.tree_id = 1
-- ORDER BY tn.sort_order;

COMMIT; 