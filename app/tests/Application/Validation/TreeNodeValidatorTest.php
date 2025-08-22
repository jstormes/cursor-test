<?php

declare(strict_types=1);

namespace Tests\Application\Validation;

use App\Application\Validation\TreeNodeValidator;
use App\Application\Validation\ValidationResult;
use PHPUnit\Framework\TestCase;

class TreeNodeValidatorTest extends TestCase
{
    private TreeNodeValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new TreeNodeValidator();
    }

    public function testValidateWithValidSimpleNodeData(): void
    {
        $data = [
            'name' => 'Valid Node Name',
            'tree_id' => 1,
            'parent_id' => null,
            'sort_order' => 0,
            'type' => 'SimpleNode'
        ];

        $result = $this->validator->validate($data);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    public function testValidateWithValidButtonNodeData(): void
    {
        $data = [
            'name' => 'Valid Button Node',
            'tree_id' => 1,
            'parent_id' => 2,
            'sort_order' => 1,
            'type' => 'ButtonNode',
            'type_data' => [
                'button_text' => 'Click me',
                'button_action' => 'console.log("clicked")'
            ]
        ];

        $result = $this->validator->validate($data);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    public function testValidateWithMinimalValidData(): void
    {
        $data = [
            'name' => 'AB',
            'tree_id' => 1
        ];

        $result = $this->validator->validate($data);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    // Name validation tests
    public function testValidateWithMissingName(): void
    {
        $data = ['tree_id' => 1];

        $result = $this->validator->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrorsForField('name'));
        $this->assertContains('Node name is required', $result->getErrorsForField('name'));
    }

    public function testValidateWithEmptyName(): void
    {
        $data = [
            'name' => '',
            'tree_id' => 1
        ];

        $result = $this->validator->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrorsForField('name'));
        $this->assertContains('Node name is required', $result->getErrorsForField('name'));
    }

    public function testValidateWithWhitespaceOnlyName(): void
    {
        $data = [
            'name' => '   ',
            'tree_id' => 1
        ];

        $result = $this->validator->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrorsForField('name'));
        $this->assertContains('Node name is required', $result->getErrorsForField('name'));
    }

    public function testValidateWithNameTooShort(): void
    {
        $data = [
            'name' => 'A',
            'tree_id' => 1
        ];

        $result = $this->validator->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrorsForField('name'));
        $this->assertContains('Node name must be at least 2 characters long', $result->getErrorsForField('name'));
    }

    public function testValidateWithNameMinimumLength(): void
    {
        $data = [
            'name' => 'AB',
            'tree_id' => 1
        ];

        $result = $this->validator->validate($data);

        $this->assertTrue($result->isValid());
    }

    public function testValidateWithNameTooLong(): void
    {
        $data = [
            'name' => str_repeat('A', 256),
            'tree_id' => 1
        ];

        $result = $this->validator->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrorsForField('name'));
        $this->assertContains('Node name must not exceed 255 characters', $result->getErrorsForField('name'));
    }

    public function testValidateWithNameMaximumLength(): void
    {
        $data = [
            'name' => str_repeat('A', 255),
            'tree_id' => 1
        ];

        $result = $this->validator->validate($data);

        $this->assertTrue($result->isValid());
    }

    // Tree ID validation tests
    public function testValidateWithMissingTreeId(): void
    {
        $data = ['name' => 'Valid Name'];

        $result = $this->validator->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrorsForField('tree_id'));
        $this->assertContains('Valid tree ID is required', $result->getErrorsForField('tree_id'));
    }

    public function testValidateWithNonNumericTreeId(): void
    {
        $data = [
            'name' => 'Valid Name',
            'tree_id' => 'invalid'
        ];

        $result = $this->validator->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrorsForField('tree_id'));
        $this->assertContains('Valid tree ID is required', $result->getErrorsForField('tree_id'));
    }

    public function testValidateWithZeroTreeId(): void
    {
        $data = [
            'name' => 'Valid Name',
            'tree_id' => 0
        ];

        $result = $this->validator->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrorsForField('tree_id'));
        $this->assertContains('Valid tree ID is required', $result->getErrorsForField('tree_id'));
    }

    public function testValidateWithNegativeTreeId(): void
    {
        $data = [
            'name' => 'Valid Name',
            'tree_id' => -1
        ];

        $result = $this->validator->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrorsForField('tree_id'));
        $this->assertContains('Valid tree ID is required', $result->getErrorsForField('tree_id'));
    }

    public function testValidateWithValidTreeId(): void
    {
        $data = [
            'name' => 'Valid Name',
            'tree_id' => 1
        ];

        $result = $this->validator->validate($data);

        $this->assertTrue($result->isValid());
    }

    // Parent ID validation tests
    public function testValidateWithValidParentId(): void
    {
        $data = [
            'name' => 'Valid Name',
            'tree_id' => 1,
            'parent_id' => 2
        ];

        $result = $this->validator->validate($data);

        $this->assertTrue($result->isValid());
    }

    public function testValidateWithNullParentId(): void
    {
        $data = [
            'name' => 'Valid Name',
            'tree_id' => 1,
            'parent_id' => null
        ];

        $result = $this->validator->validate($data);

        $this->assertTrue($result->isValid());
    }

    public function testValidateWithNonNumericParentId(): void
    {
        $data = [
            'name' => 'Valid Name',
            'tree_id' => 1,
            'parent_id' => 'invalid'
        ];

        $result = $this->validator->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrorsForField('parent_id'));
        $this->assertContains('Parent ID must be a valid positive integer', $result->getErrorsForField('parent_id'));
    }

    public function testValidateWithZeroParentId(): void
    {
        $data = [
            'name' => 'Valid Name',
            'tree_id' => 1,
            'parent_id' => 0
        ];

        $result = $this->validator->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrorsForField('parent_id'));
        $this->assertContains('Parent ID must be a valid positive integer', $result->getErrorsForField('parent_id'));
    }

    public function testValidateWithNegativeParentId(): void
    {
        $data = [
            'name' => 'Valid Name',
            'tree_id' => 1,
            'parent_id' => -1
        ];

        $result = $this->validator->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrorsForField('parent_id'));
        $this->assertContains('Parent ID must be a valid positive integer', $result->getErrorsForField('parent_id'));
    }

    // Sort order validation tests
    public function testValidateWithValidSortOrder(): void
    {
        $data = [
            'name' => 'Valid Name',
            'tree_id' => 1,
            'sort_order' => 5
        ];

        $result = $this->validator->validate($data);

        $this->assertTrue($result->isValid());
    }

    public function testValidateWithZeroSortOrder(): void
    {
        $data = [
            'name' => 'Valid Name',
            'tree_id' => 1,
            'sort_order' => 0
        ];

        $result = $this->validator->validate($data);

        $this->assertTrue($result->isValid());
    }

    public function testValidateWithNegativeSortOrder(): void
    {
        $data = [
            'name' => 'Valid Name',
            'tree_id' => 1,
            'sort_order' => -1
        ];

        $result = $this->validator->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrorsForField('sort_order'));
        $this->assertContains('Sort order must be a non-negative integer', $result->getErrorsForField('sort_order'));
    }

    public function testValidateWithNonNumericSortOrder(): void
    {
        $data = [
            'name' => 'Valid Name',
            'tree_id' => 1,
            'sort_order' => 'invalid'
        ];

        $result = $this->validator->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrorsForField('sort_order'));
        $this->assertContains('Sort order must be a non-negative integer', $result->getErrorsForField('sort_order'));
    }

    // Type validation tests
    public function testValidateWithValidSimpleNodeType(): void
    {
        $data = [
            'name' => 'Valid Name',
            'tree_id' => 1,
            'type' => 'SimpleNode'
        ];

        $result = $this->validator->validate($data);

        $this->assertTrue($result->isValid());
    }

    public function testValidateWithValidButtonNodeType(): void
    {
        $data = [
            'name' => 'Valid Name',
            'tree_id' => 1,
            'type' => 'ButtonNode'
        ];

        $result = $this->validator->validate($data);

        $this->assertTrue($result->isValid());
    }

    public function testValidateWithInvalidType(): void
    {
        $data = [
            'name' => 'Valid Name',
            'tree_id' => 1,
            'type' => 'InvalidType'
        ];

        $result = $this->validator->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrorsForField('type'));
        $this->assertContains('Invalid node type. Allowed types: SimpleNode, ButtonNode', $result->getErrorsForField('type'));
    }

    // Button node type_data validation tests
    public function testValidateButtonNodeWithValidTypeData(): void
    {
        $data = [
            'name' => 'Valid Name',
            'tree_id' => 1,
            'type' => 'ButtonNode',
            'type_data' => [
                'button_text' => 'Click me',
                'button_action' => 'alert("Hello")'
            ]
        ];

        $result = $this->validator->validate($data);

        $this->assertTrue($result->isValid());
    }

    public function testValidateButtonNodeWithEmptyButtonText(): void
    {
        $data = [
            'name' => 'Valid Name',
            'tree_id' => 1,
            'type' => 'ButtonNode',
            'type_data' => [
                'button_text' => '',
                'button_action' => 'alert("Hello")'
            ]
        ];

        $result = $this->validator->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrorsForField('type_data.button_text'));
        $this->assertContains('Button text cannot be empty', $result->getErrorsForField('type_data.button_text'));
    }

    public function testValidateButtonNodeWithWhitespaceOnlyButtonText(): void
    {
        $data = [
            'name' => 'Valid Name',
            'tree_id' => 1,
            'type' => 'ButtonNode',
            'type_data' => [
                'button_text' => '   ',
                'button_action' => 'alert("Hello")'
            ]
        ];

        $result = $this->validator->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrorsForField('type_data.button_text'));
        $this->assertContains('Button text cannot be empty', $result->getErrorsForField('type_data.button_text'));
    }

    public function testValidateButtonNodeWithButtonTextTooLong(): void
    {
        $data = [
            'name' => 'Valid Name',
            'tree_id' => 1,
            'type' => 'ButtonNode',
            'type_data' => [
                'button_text' => str_repeat('A', 101),
                'button_action' => 'alert("Hello")'
            ]
        ];

        $result = $this->validator->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrorsForField('type_data.button_text'));
        $this->assertContains('Button text must not exceed 100 characters', $result->getErrorsForField('type_data.button_text'));
    }

    public function testValidateButtonNodeWithButtonTextMaxLength(): void
    {
        $data = [
            'name' => 'Valid Name',
            'tree_id' => 1,
            'type' => 'ButtonNode',
            'type_data' => [
                'button_text' => str_repeat('A', 100),
                'button_action' => 'alert("Hello")'
            ]
        ];

        $result = $this->validator->validate($data);

        $this->assertTrue($result->isValid());
    }

    public function testValidateButtonNodeWithButtonActionTooLong(): void
    {
        $data = [
            'name' => 'Valid Name',
            'tree_id' => 1,
            'type' => 'ButtonNode',
            'type_data' => [
                'button_text' => 'Click me',
                'button_action' => str_repeat('A', 501)
            ]
        ];

        $result = $this->validator->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrorsForField('type_data.button_action'));
        $this->assertContains('Button action must not exceed 500 characters', $result->getErrorsForField('type_data.button_action'));
    }

    public function testValidateButtonNodeWithButtonActionMaxLength(): void
    {
        $data = [
            'name' => 'Valid Name',
            'tree_id' => 1,
            'type' => 'ButtonNode',
            'type_data' => [
                'button_text' => 'Click me',
                'button_action' => str_repeat('A', 500)
            ]
        ];

        $result = $this->validator->validate($data);

        $this->assertTrue($result->isValid());
    }

    public function testValidateButtonNodeWithDangerousJavaScriptPatterns(): void
    {
        $dangerousActions = [
            'javascript:alert("XSS")',
            'JAVASCRIPT:alert("XSS")',
            'eval("malicious code")',
            'document.write("<script>alert(1)</script>")',
            'element.innerHTML = "<script>alert(1)</script>"',
            'element.outerHTML = "<script>alert(1)</script>"'
        ];

        foreach ($dangerousActions as $action) {
            $data = [
                'name' => 'Valid Name',
                'tree_id' => 1,
                'type' => 'ButtonNode',
                'type_data' => [
                    'button_text' => 'Click me',
                    'button_action' => $action
                ]
            ];

            $result = $this->validator->validate($data);

            $this->assertFalse($result->isValid(), "Action '$action' should be rejected");
            $this->assertTrue($result->hasErrorsForField('type_data.button_action'));
            $this->assertContains('Button action contains potentially dangerous code', $result->getErrorsForField('type_data.button_action'));
        }
    }

    public function testValidateButtonNodeWithSafeActions(): void
    {
        $safeActions = [
            'alert("Hello")',
            'console.log("Safe action")',
            'window.location.href = "/safe-url"',
            'showModal()',
            'toggleDisplay()'
        ];

        foreach ($safeActions as $action) {
            $data = [
                'name' => 'Valid Name',
                'tree_id' => 1,
                'type' => 'ButtonNode',
                'type_data' => [
                    'button_text' => 'Click me',
                    'button_action' => $action
                ]
            ];

            $result = $this->validator->validate($data);

            $this->assertTrue($result->isValid(), "Action '$action' should be accepted");
        }
    }

    // Sanitization tests
    public function testSanitizeWithValidData(): void
    {
        $data = [
            'name' => '  Test Node  ',
            'tree_id' => '123',
            'parent_id' => '456',
            'sort_order' => '789',
            'type' => 'ButtonNode',
            'type_data' => [
                'button_text' => '  Click me  ',
                'button_action' => '  alert("test")  '
            ]
        ];

        $sanitized = $this->validator->sanitize($data);

        $this->assertEquals('Test Node', $sanitized['name']);
        $this->assertEquals(123, $sanitized['tree_id']);
        $this->assertEquals(456, $sanitized['parent_id']);
        $this->assertEquals(789, $sanitized['sort_order']);
        $this->assertEquals('ButtonNode', $sanitized['type']);
        $this->assertEquals([
            'button_text' => 'Click me',
            'button_action' => 'alert(&quot;test&quot;)'
        ], $sanitized['type_data']);
    }

    public function testSanitizeWithNullParentId(): void
    {
        $data = [
            'name' => 'Test Node',
            'tree_id' => '123',
            'parent_id' => null
        ];

        $sanitized = $this->validator->sanitize($data);

        $this->assertEquals('Test Node', $sanitized['name']);
        $this->assertEquals(123, $sanitized['tree_id']);
        $this->assertNull($sanitized['parent_id']);
    }

    public function testSanitizeWithHtmlCharacters(): void
    {
        $data = [
            'name' => '<script>alert("XSS")</script>',
            'tree_id' => '1',
            'type_data' => [
                'button_text' => '<b>Bold</b>',
                'button_action' => '<script>alert("XSS")</script>'
            ]
        ];

        $sanitized = $this->validator->sanitize($data);

        $this->assertEquals('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;', $sanitized['name']);
        $this->assertEquals(1, $sanitized['tree_id']);
        $this->assertEquals([
            'button_text' => '&lt;b&gt;Bold&lt;/b&gt;',
            'button_action' => '&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;'
        ], $sanitized['type_data']);
    }

    public function testSanitizeWithEmptyData(): void
    {
        $data = [];

        $sanitized = $this->validator->sanitize($data);

        $this->assertEmpty($sanitized);
    }

    public function testSanitizeWithPartialData(): void
    {
        $data = [
            'name' => 'Test Node',
            'tree_id' => '123'
        ];

        $sanitized = $this->validator->sanitize($data);

        $this->assertEquals('Test Node', $sanitized['name']);
        $this->assertEquals(123, $sanitized['tree_id']);
        $this->assertArrayNotHasKey('parent_id', $sanitized);
        $this->assertArrayNotHasKey('sort_order', $sanitized);
        $this->assertArrayNotHasKey('type', $sanitized);
        $this->assertArrayNotHasKey('type_data', $sanitized);
    }
}