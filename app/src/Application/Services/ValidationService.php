<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Validation\TreeValidator;
use App\Application\Validation\TreeNodeValidator;
use App\Application\Validation\ValidationResult;
use App\Application\Exceptions\ValidationException;

class ValidationService
{
    public function __construct(
        private TreeValidator $treeValidator,
        private TreeNodeValidator $nodeValidator
    ) {
    }

    /**
     * Validate tree data from request
     * 
     * @param array $data
     * @return array Sanitized data
     * @throws ValidationException
     */
    public function validateTreeData(array $data): array
    {
        $validation = $this->treeValidator->validate($data);
        
        if (!$validation->isValid()) {
            throw new ValidationException($validation, 'Tree validation failed');
        }
        
        return $this->treeValidator->sanitize($data);
    }

    /**
     * Validate node data from request
     * 
     * @param array $data
     * @return array Sanitized data
     * @throws ValidationException
     */
    public function validateNodeData(array $data): array
    {
        $validation = $this->nodeValidator->validate($data);
        
        if (!$validation->isValid()) {
            throw new ValidationException($validation, 'Node validation failed');
        }
        
        return $this->nodeValidator->sanitize($data);
    }

    /**
     * Validate multiple nodes at once
     * 
     * @param array[] $nodesData
     * @return array[] Sanitized nodes data
     * @throws ValidationException
     */
    public function validateMultipleNodes(array $nodesData): array
    {
        $sanitizedNodes = [];
        $allErrors = [];
        
        foreach ($nodesData as $index => $nodeData) {
            $validation = $this->nodeValidator->validate($nodeData);
            
            if (!$validation->isValid()) {
                $allErrors["node_$index"] = $validation->getErrors();
            } else {
                $sanitizedNodes[] = $this->nodeValidator->sanitize($nodeData);
            }
        }
        
        if (!empty($allErrors)) {
            $result = new ValidationResult(false, $allErrors);
            throw new ValidationException($result, 'Multiple nodes validation failed');
        }
        
        return $sanitizedNodes;
    }

    /**
     * Validate tree with nodes in a single operation
     * 
     * @param array $treeData
     * @param array[] $nodesData
     * @return array ['tree' => sanitized tree data, 'nodes' => sanitized nodes data]
     * @throws ValidationException
     */
    public function validateTreeWithNodes(array $treeData, array $nodesData): array
    {
        // Validate tree first
        $sanitizedTree = $this->validateTreeData($treeData);
        
        // Then validate nodes
        $sanitizedNodes = $this->validateMultipleNodes($nodesData);
        
        return [
            'tree' => $sanitizedTree,
            'nodes' => $sanitizedNodes
        ];
    }

    /**
     * Extract and validate tree data from request body
     * 
     * @param array $requestData
     * @return array
     * @throws ValidationException
     */
    public function extractAndValidateTreeFromRequest(array $requestData): array
    {
        $treeData = [
            'name' => $requestData['name'] ?? '',
            'description' => $requestData['description'] ?? null
        ];
        
        return $this->validateTreeData($treeData);
    }

    /**
     * Extract and validate node data from request body
     * 
     * @param array $requestData
     * @param int|null $treeId
     * @return array
     * @throws ValidationException
     */
    public function extractAndValidateNodeFromRequest(array $requestData, ?int $treeId = null): array
    {
        $nodeData = [
            'name' => $requestData['name'] ?? '',
            'type' => $requestData['type'] ?? 'simple',
            'parent_id' => isset($requestData['parent_id']) ? (int)$requestData['parent_id'] : null,
            'sort_order' => isset($requestData['sort_order']) ? (int)$requestData['sort_order'] : 0,
            'tree_id' => $treeId ?? ($requestData['tree_id'] ?? null)
        ];
        
        // Add type-specific data
        if ($nodeData['type'] === 'button' && isset($requestData['button_text'])) {
            $nodeData['button_text'] = $requestData['button_text'];
        }
        
        return $this->validateNodeData($nodeData);
    }
}