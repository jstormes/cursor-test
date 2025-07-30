-- Simplified Tree System Analysis
-- Version: 1.0
-- Description: Analysis of the simplified tree system with type_class and type_data approach
-- Generated: 2025-07-30

-- --------------------------------------------------------
-- System Overview
-- --------------------------------------------------------

/*
SIMPLIFIED TREE SYSTEM ANALYSIS

The simplified system uses two key fields in tree_nodes:
1. type_class: Stores the PHP class name (e.g., 'SimpleNode', 'ButtonNode')
2. type_data: Stores JSON data for the node type (e.g., button text, actions)

This approach is much simpler than the decoupled node type system because:
- No additional tables needed
- Direct mapping to PHP classes
- JSON data provides flexibility
- Easy to understand and implement
*/

-- --------------------------------------------------------
-- Sample Data Analysis
-- --------------------------------------------------------

-- Analyze the sample data structure
SELECT 
    'Sample Data Overview' as analysis_type,
    COUNT(*) as total_nodes,
    COUNT(DISTINCT tree_id) as total_trees,
    COUNT(DISTINCT type_class) as unique_node_types
FROM tree_nodes;

-- Analyze node type distribution
SELECT 
    type_class,
    COUNT(*) as node_count,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM tree_nodes), 2) as percentage
FROM tree_nodes
GROUP BY type_class
ORDER BY node_count DESC;

-- Analyze tree structures
SELECT 
    t.name as tree_name,
    COUNT(tn.id) as node_count,
    COUNT(CASE WHEN tn.type_class = 'ButtonNode' THEN 1 END) as button_nodes,
    COUNT(CASE WHEN tn.type_class = 'SimpleNode' THEN 1 END) as simple_nodes
FROM trees t
LEFT JOIN tree_nodes tn ON t.id = tn.tree_id
WHERE t.is_active = 1
GROUP BY t.id, t.name
ORDER BY t.name;

-- --------------------------------------------------------
-- Type Data Analysis
-- --------------------------------------------------------

-- Analyze ButtonNode type_data patterns
SELECT 
    tn.name,
    tn.type_data,
    JSON_EXTRACT(tn.type_data, '$.button_text') as button_text,
    JSON_EXTRACT(tn.type_data, '$.button_action') as button_action
FROM tree_nodes tn
WHERE tn.type_class = 'ButtonNode'
ORDER BY tn.name;

-- Check for empty type_data in SimpleNodes
SELECT 
    COUNT(*) as simple_nodes_with_data,
    COUNT(CASE WHEN type_data = '{}' OR type_data IS NULL THEN 1 END) as empty_data_nodes
FROM tree_nodes
WHERE type_class = 'SimpleNode';

-- --------------------------------------------------------
-- Tree Structure Queries
-- --------------------------------------------------------

-- Get complete tree structure with type information
SELECT 
    tn.id,
    tn.tree_id,
    tn.parent_id,
    tn.name,
    tn.sort_order,
    tn.type_class,
    tn.type_data,
    CASE 
        WHEN tn.parent_id IS NULL THEN tn.name
        WHEN tn.parent_id = (SELECT parent_id FROM tree_nodes WHERE id = tn.parent_id) THEN CONCAT('  ', tn.name)
        ELSE CONCAT('    ', tn.name)
    END as display_name
FROM tree_nodes tn
WHERE tn.tree_id = 1  -- Company Structure
ORDER BY tn.sort_order;

-- Get all button nodes with their actions
SELECT 
    tn.id,
    tn.name,
    JSON_EXTRACT(tn.type_data, '$.button_text') as button_text,
    JSON_EXTRACT(tn.type_data, '$.button_action') as button_action,
    t.name as tree_name
FROM tree_nodes tn
JOIN trees t ON tn.tree_id = t.id
WHERE tn.type_class = 'ButtonNode'
ORDER BY t.name, tn.name;

-- --------------------------------------------------------
-- Practical Usage Examples
-- --------------------------------------------------------

-- Example 1: Create a new button node
/*
INSERT INTO tree_nodes (tree_id, parent_id, name, sort_order, type_class, type_data) VALUES
(1, 2, 'New Button', 3, 'ButtonNode', '{"button_text": "Click Me", "button_action": "alert(\"Hello!\")"}');
*/

-- Example 2: Update button node data
/*
UPDATE tree_nodes 
SET type_data = JSON_SET(type_data, '$.button_text', 'New Text', '$.button_action', 'newAction()')
WHERE id = 1 AND type_class = 'ButtonNode';
*/

-- Example 3: Get button nodes by tree
/*
SELECT id, name, type_data 
FROM tree_nodes 
WHERE tree_id = 1 AND type_class = 'ButtonNode'
ORDER BY sort_order;
*/

-- Example 4: Add a new node type (LinkNode)
/*
INSERT INTO tree_nodes (tree_id, parent_id, name, sort_order, type_class, type_data) VALUES
(3, 17, 'External Link', 2, 'LinkNode', '{"url": "https://example.com", "target": "_blank", "link_text": "Visit Site"}');
*/

-- --------------------------------------------------------
-- PHP Integration Examples
-- --------------------------------------------------------

/*
// Example PHP code for working with the simplified system:

// 1. Load a node from database
$nodeData = [
    'id' => 1,
    'name' => 'CEO',
    'type_class' => 'ButtonNode',
    'type_data' => '{"button_text": "Manage", "button_action": "manageCEO()"}'
];

// 2. Create the appropriate node object
$typeClass = $nodeData['type_class'];
$typeData = json_decode($nodeData['type_data'], true);

switch ($typeClass) {
    case 'ButtonNode':
        $node = new ButtonNode(
            $nodeData['name'],
            $typeData['button_text'] ?? 'Click Me',
            $typeData['button_action'] ?? ''
        );
        break;
    case 'SimpleNode':
        $node = new SimpleNode($nodeData['name']);
        break;
    case 'LinkNode':
        $node = new LinkNode(
            $nodeData['name'],
            $typeData['url'] ?? '',
            $typeData['target'] ?? '_self',
            $typeData['link_text'] ?? ''
        );
        break;
}

// 3. Render the node
$renderer = new HtmlTreeNodeRenderer();
$html = $renderer->render($node);
*/

-- --------------------------------------------------------
-- Advantages of Simplified System
-- --------------------------------------------------------

/*
ADVANTAGES:

1. **Simplicity**: Only 2 tables (trees, tree_nodes) instead of 4-5 tables
2. **Direct Mapping**: type_class directly maps to PHP class names
3. **Flexibility**: JSON type_data can store any node-specific data
4. **Performance**: Fewer joins needed for queries
5. **Easy Migration**: Simple to migrate from existing systems
6. **Extensibility**: Easy to add new node types without schema changes
7. **Maintainability**: Less complex database structure

DISADVANTAGES:

1. **No Validation**: JSON data isn't validated at database level
2. **No Type Registry**: No central registry of available node types
3. **Limited Analytics**: Harder to analyze type usage patterns
4. **No Default Values**: No automatic default value handling
5. **Manual Type Management**: Need to handle type creation in application code
*/

-- --------------------------------------------------------
-- Comparison with Decoupled System
-- --------------------------------------------------------

/*
SIMPLIFIED vs DECOUPLED SYSTEM:

Simplified System:
- Tables: 2 (trees, tree_nodes)
- Type Storage: type_class + type_data fields
- Validation: Application-level only
- Performance: Excellent (fewer joins)
- Complexity: Low
- Migration: Easy

Decoupled System:
- Tables: 4 (trees, tree_nodes, node_types, node_type_attributes, tree_node_type_instances, tree_node_type_values)
- Type Storage: Separate tables with relationships
- Validation: Database-level with constraints
- Performance: Good (more joins but indexed)
- Complexity: Medium
- Migration: More complex

RECOMMENDATION:
Use Simplified System for:
- Small to medium projects
- When you need quick implementation
- When you have a limited number of node types
- When performance is critical

Use Decoupled System for:
- Large projects with many node types
- When you need database-level validation
- When you need type analytics
- When you need runtime type registration
*/

-- --------------------------------------------------------
-- Migration Path
-- --------------------------------------------------------

-- Example: Migrate from hard-coded has_button system to simplified system
/*
-- Old system had: has_button, button_text columns
-- New system uses: type_class, type_data columns

-- Migration query:
UPDATE tree_nodes 
SET 
    type_class = CASE 
        WHEN has_button = 1 THEN 'ButtonNode'
        ELSE 'SimpleNode'
    END,
    type_data = CASE 
        WHEN has_button = 1 THEN JSON_OBJECT('button_text', COALESCE(button_text, 'Click Me'))
        ELSE '{}'
    END
WHERE type_class IS NULL;

-- Then drop old columns:
-- ALTER TABLE tree_nodes DROP COLUMN has_button;
-- ALTER TABLE tree_nodes DROP COLUMN button_text;
*/ 