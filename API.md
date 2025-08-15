# Tree Structure Manager - RESTful API Documentation

This document provides comprehensive details on how to use the RESTful endpoints for the Tree Structure Manager application. The API supports both JSON and HTML responses for managing hierarchical tree structures using the Composite and Visitor design patterns.

## 🌐 Base URL

- **Development**: `http://localhost:8088`
- **Production**: `http://localhost:9088`

## 📋 API Overview

The API provides endpoints for:
- **Tree Management**: Create, view, update, delete, and restore trees
- **Node Management**: Add, view, and delete nodes within trees
- **Tree Structure Visualization**: Retrieve tree structures with composite pattern implementation
- **Soft Delete Support**: Archive and restore functionality

## 🔗 Endpoint Categories

### 1. Tree Operations
- List trees, create new trees, view specific trees
- Support for both HTML views and JSON APIs

### 2. Node Operations  
- Add nodes to trees (SimpleNode, ButtonNode)
- Delete nodes with parent-child relationship management

### 3. Tree Lifecycle
- Soft delete (archive) and restore operations
- View deleted/archived trees

---

## 📖 Tree Management Endpoints

### List All Active Trees

**HTML View**
```http
GET /trees
```
Returns an HTML page displaying all active trees.

**JSON API**
```http
GET /tree/json
```
Returns the first active tree with complete node structure in JSON format.

**Response Example:**
```json
{
  "success": true,
  "message": "Tree structure retrieved successfully",
  "data": {
    "tree": {
      "id": 1,
      "name": "Main Navigation",
      "description": "Primary site navigation tree",
      "created_at": "2024-01-15 10:30:00",
      "updated_at": "2024-01-15 10:30:00",
      "is_active": true,
      "root_nodes": [
        {
          "id": 1,
          "name": "Home",
          "type": "SimpleNode",
          "tree_id": 1,
          "parent_id": null,
          "sort_order": 0,
          "has_children": true,
          "children_count": 2,
          "type_data": {},
          "children": [
            {
              "id": 2,
              "name": "About",
              "type": "SimpleNode",
              "tree_id": 1,
              "parent_id": 1,
              "sort_order": 0,
              "has_children": false,
              "children_count": 0,
              "type_data": {}
            }
          ]
        }
      ]
    },
    "total_nodes": 5,
    "total_levels": 3,
    "total_root_nodes": 1
  }
}
```

### View Specific Tree

**HTML View**
```http
GET /tree/{id}
```
Returns an HTML page with tree editor interface.

**Read-Only HTML View**
```http
GET /tree/{id}/view
```
Returns an HTML page with read-only tree display.

**JSON API**
```http
GET /tree/{id}/json
```
Returns complete tree structure with all nodes in JSON format.

**Path Parameters:**
- `id` (integer, required): The tree ID

**Response Example:**
```json
{
  "success": true,
  "message": "Tree structure retrieved successfully",
  "data": {
    "tree": {
      "id": 1,
      "name": "Product Categories",
      "description": "E-commerce product category tree",
      "created_at": "2024-01-15 10:30:00",
      "updated_at": "2024-01-15 10:30:00",
      "is_active": true,
      "root_nodes": [...]
    },
    "total_nodes": 12,
    "total_levels": 4,
    "total_root_nodes": 2
  }
}
```

### Create New Tree

**HTML Form**
```http
GET /tree/add
POST /tree/add
```
HTML form interface for creating trees.

**JSON API**
```http
POST /tree/add/json
Content-Type: application/json
```

**Request Body:**
```json
{
  "name": "New Tree Name",
  "description": "Optional tree description"
}
```

**Validation Rules:**
- `name` (required): 1-255 characters, must be unique among active trees
- `description` (optional): Maximum 1000 characters

**Success Response:**
```json
{
  "success": true,
  "message": "Tree created successfully",
  "tree": {
    "id": 3,
    "name": "New Tree Name", 
    "description": "Optional tree description",
    "created_at": "2024-01-15 14:22:30",
    "updated_at": "2024-01-15 14:22:30"
  },
  "links": {
    "view_tree": "/tree/3",
    "view_tree_json": "/tree/3/json",
    "add_node": "/tree/3/add-node",
    "add_node_json": "/tree/3/add-node/json",
    "view_trees": "/trees"
  }
}
```

**Error Response:**
```json
{
  "success": false,
  "error": {
    "message": "Tree name is required",
    "details": {}
  }
}
```

### Delete Tree (Soft Delete)

**HTML Interface**
```http
GET /tree/{id}/delete
POST /tree/{id}/delete
```

**JSON API**
```http
POST /tree/{id}/delete/json
```

**Path Parameters:**
- `id` (integer, required): The tree ID to delete

**Success Response:**
```json
{
  "success": true,
  "message": "Tree deleted successfully",
  "tree": {
    "id": 1,
    "name": "Deleted Tree",
    "is_active": false
  },
  "links": {
    "restore_tree": "/tree/1/restore",
    "view_deleted_trees": "/trees/deleted"
  }
}
```

### Restore Tree

**HTML Interface**
```http
GET /tree/{id}/restore  
POST /tree/{id}/restore
```

**JSON API**
```http
POST /tree/{id}/restore/json
```

**Path Parameters:**
- `id` (integer, required): The tree ID to restore

**Success Response:**
```json
{
  "success": true,
  "message": "Tree restored successfully",
  "tree": {
    "id": 1,
    "name": "Restored Tree",
    "is_active": true
  },
  "links": {
    "view_tree": "/tree/1",
    "view_trees": "/trees"
  }
}
```

### View Deleted Trees

**HTML View**
```http
GET /trees/deleted
```

**JSON API**
```http
GET /trees/deleted/json
```

**Response Example:**
```json
{
  "success": true,
  "message": "Deleted trees retrieved successfully",
  "data": {
    "trees": [
      {
        "id": 5,
        "name": "Archived Navigation",
        "description": "Old navigation structure",
        "created_at": "2024-01-10 09:15:00",
        "deleted_at": "2024-01-15 16:45:00",
        "is_active": false
      }
    ],
    "total_deleted": 1
  }
}
```

---

## 🌳 Node Management Endpoints

### Add Node to Tree

**HTML Form**
```http
GET /tree/{treeId}/add-node
POST /tree/{treeId}/add-node
```

**JSON API**
```http
POST /tree/{treeId}/add-node/json
Content-Type: application/json
```

**Path Parameters:**
- `treeId` (integer, required): The tree ID to add the node to

**Request Body for SimpleNode:**
```json
{
  "name": "New Node",
  "node_type": "SimpleNode",
  "parent_id": 1,
  "sort_order": 0
}
```

**Request Body for ButtonNode:**
```json
{
  "name": "Action Button",
  "node_type": "ButtonNode",
  "parent_id": 1,
  "sort_order": 0,
  "button_text": "Click Me",
  "button_action": "javascript:alert('Hello')"
}
```

**Field Validation:**
- `name` (required): 1-255 characters
- `node_type` (optional): "SimpleNode" (default) or "ButtonNode"
- `parent_id` (optional): Must exist in the same tree, null for root node
- `sort_order` (optional): Integer for ordering siblings, default 0
- `button_text` (required for ButtonNode): 1-100 characters
- `button_action` (optional for ButtonNode): Maximum 255 characters

**Success Response:**
```json
{
  "success": true,
  "message": "Node created successfully",
  "node": {
    "id": 15,
    "name": "New Node",
    "type": "SimpleNode",
    "tree_id": 1,
    "parent_id": 1,
    "sort_order": 0,
    "type_data": {}
  },
  "tree": {
    "id": 1,
    "name": "Main Tree",
    "description": "Primary tree structure"
  },
  "links": {
    "view_tree": "/tree/1",
    "view_tree_json": "/tree/1/json",
    "add_another_node": "/tree/1/add-node"
  }
}
```

**ButtonNode Success Response:**
```json
{
  "success": true,
  "message": "Node created successfully",
  "node": {
    "id": 16,
    "name": "Action Button",
    "type": "ButtonNode",
    "tree_id": 1,
    "parent_id": 1,
    "sort_order": 0,
    "type_data": {
      "button_text": "Click Me",
      "button_action": "javascript:alert('Hello')"
    }
  },
  "tree": {
    "id": 1,
    "name": "Main Tree",
    "description": "Primary tree structure"
  },
  "links": {
    "view_tree": "/tree/1",
    "view_tree_json": "/tree/1/json",
    "add_another_node": "/tree/1/add-node"
  }
}
```

### Delete Node

**HTML Interface**
```http
GET /tree/{treeId}/node/{nodeId}/delete
POST /tree/{treeId}/node/{nodeId}/delete
```

**Path Parameters:**
- `treeId` (integer, required): The tree ID containing the node
- `nodeId` (integer, required): The node ID to delete

**Notes:**
- Deleting a node will also delete all its children (cascade delete)
- This is a hard delete operation (permanent removal)

---

## 📊 Response Formats

### Success Response Structure
```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": {
    // Response-specific data
  },
  "links": {
    // Related endpoint links
  }
}
```

### Error Response Structure
```json
{
  "success": false,
  "error": {
    "message": "Error description",
    "details": {
      // Additional error context
    }
  }
}
```

### Tree Object Structure
```json
{
  "id": 1,
  "name": "Tree Name",
  "description": "Optional description",
  "created_at": "2024-01-15 10:30:00",
  "updated_at": "2024-01-15 10:30:00", 
  "is_active": true
}
```

### Node Object Structure

**SimpleNode:**
```json
{
  "id": 1,
  "name": "Node Name",
  "type": "SimpleNode",
  "tree_id": 1,
  "parent_id": null,
  "sort_order": 0,
  "has_children": false,
  "children_count": 0,
  "type_data": {}
}
```

**ButtonNode:**
```json
{
  "id": 2,
  "name": "Button Node",
  "type": "ButtonNode", 
  "tree_id": 1,
  "parent_id": 1,
  "sort_order": 0,
  "has_children": false,
  "children_count": 0,
  "type_data": {
    "button_text": "Click Me",
    "button_action": "https://example.com"
  },
  "button": {
    "text": "Click Me",
    "action": "https://example.com"
  }
}
```

---

## 🔧 Usage Examples

### Example 1: Create a Complete Tree Structure

1. **Create a new tree:**
```bash
curl -X POST http://localhost:8088/tree/add/json \
  -H "Content-Type: application/json" \
  -d '{"name": "Website Menu", "description": "Main website navigation"}'
```

2. **Add root node:**
```bash
curl -X POST http://localhost:8088/tree/1/add-node/json \
  -H "Content-Type: application/json" \
  -d '{"name": "Home", "node_type": "SimpleNode"}'
```

3. **Add child nodes:**
```bash
curl -X POST http://localhost:8088/tree/1/add-node/json \
  -H "Content-Type: application/json" \
  -d '{"name": "About", "node_type": "SimpleNode", "parent_id": 1}'
```

4. **Add button node:**
```bash
curl -X POST http://localhost:8088/tree/1/add-node/json \
  -H "Content-Type: application/json" \
  -d '{"name": "Contact", "node_type": "ButtonNode", "parent_id": 1, "button_text": "Get in Touch", "button_action": "/contact"}'
```

### Example 2: Retrieve Tree Structure

```bash
curl http://localhost:8088/tree/1/json
```

### Example 3: Archive and Restore Tree

**Archive:**
```bash
curl -X POST http://localhost:8088/tree/1/delete/json
```

**Restore:**
```bash
curl -X POST http://localhost:8088/tree/1/restore/json
```

---

## ⚠️ Error Handling

### Common Error Codes

| HTTP Status | Error Type | Description |
|-------------|------------|-------------|
| 400 | Bad Request | Invalid input data or malformed JSON |
| 404 | Not Found | Tree or node not found |
| 422 | Unprocessable Entity | Validation errors |
| 500 | Internal Server Error | Server-side error |

### Validation Errors

**Tree Name Already Exists:**
```json
{
  "success": false,
  "error": {
    "message": "A tree with this name already exists",
    "details": {}
  }
}
```

**Invalid Parent Node:**
```json
{
  "success": false,
  "error": {
    "message": "Invalid parent node selected",
    "details": {}
  }
}
```

**Tree Not Found:**
```json
{
  "success": false,
  "message": "Tree not found",
  "error": true,
  "data": {
    "tree_id": 999,
    "message": "Tree with ID 999 was not found in the database"
  }
}
```

---

## 🏗️ Design Pattern Implementation

### Composite Pattern
The API implements the Composite Pattern for tree node management:
- **Component**: `AbstractTreeNode` interface
- **Leaf**: `SimpleNode`, `ButtonNode` (nodes without children)
- **Composite**: `TreeNode` (nodes with children)

### Visitor Pattern
HTML rendering uses the Visitor Pattern:
- **Visitor**: `HtmlTreeNodeRenderer`
- **Visited Elements**: All node types implement `accept(TreeNodeVisitor $visitor)`

### Repository Pattern
Data access abstraction:
- `TreeRepository` and `TreeNodeRepository` interfaces
- Concrete implementations handle database operations
- Unit of Work pattern for transaction management

---

## 📚 Additional Resources

### Related Documentation
- [README.md](README.md) - Project overview and setup
- [CLAUDE.md](CLAUDE.md) - Development commands and architecture

### Design Pattern References
- [Composite Pattern](https://refactoring.guru/design-patterns/composite)
- [Visitor Pattern](https://refactoring.guru/design-patterns/visitor)
- [Repository Pattern](https://martinfowler.com/eaaCatalog/repository.html)

### Testing
Run API tests with:
```bash
docker-compose exec php-dev bash -c "cd /app && composer test"
```

### Development
Start development environment:
```bash
docker-compose up -d
```

Access PhpMyAdmin for database inspection:
- URL: http://localhost:7088
- Username: root
- Password: password

---

## 📝 Notes

- All JSON endpoints return properly formatted JSON with appropriate HTTP headers
- HTML endpoints return rendered pages with forms for interactive tree management
- The API follows RESTful principles with logical URL structures
- Soft delete functionality preserves data integrity while allowing restoration
- Comprehensive validation ensures data quality and consistency
- The composite pattern allows uniform handling of simple nodes and complex tree structures
- Visitor pattern separation enables easy addition of new rendering formats (XML, CSV, etc.)