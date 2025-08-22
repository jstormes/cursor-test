<?php

declare(strict_types=1);

namespace Tests\Application\Validation;

use App\Application\Validation\TreeValidator;
use App\Application\Validation\ValidationResult;
use PHPUnit\Framework\TestCase;

class TreeValidatorTest extends TestCase
{
    private TreeValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new TreeValidator();
    }

    public function testValidateWithValidData(): void
    {
        $data = [
            'name' => 'Valid Tree Name',
            'description' => 'Valid description'
        ];

        $result = $this->validator->validate($data);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    public function testValidateWithValidDataWithoutDescription(): void
    {
        $data = ['name' => 'Valid Tree Name'];

        $result = $this->validator->validate($data);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    public function testValidateWithValidDataWithNullDescription(): void
    {
        $data = [
            'name' => 'Valid Tree Name',
            'description' => null
        ];

        $result = $this->validator->validate($data);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    public function testValidateWithMissingName(): void
    {
        $data = [];

        $result = $this->validator->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrorsForField('name'));
        $this->assertContains('Tree name is required', $result->getErrorsForField('name'));
    }

    public function testValidateWithEmptyName(): void
    {
        $data = ['name' => ''];

        $result = $this->validator->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrorsForField('name'));
        $this->assertContains('Tree name is required', $result->getErrorsForField('name'));
    }

    public function testValidateWithWhitespaceOnlyName(): void
    {
        $data = ['name' => '   '];

        $result = $this->validator->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrorsForField('name'));
        $this->assertContains('Tree name is required', $result->getErrorsForField('name'));
    }

    public function testValidateWithNameTooShort(): void
    {
        $data = ['name' => 'AB'];

        $result = $this->validator->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrorsForField('name'));
        $this->assertContains('Tree name must be at least 3 characters long', $result->getErrorsForField('name'));
    }

    public function testValidateWithNameMinimumLength(): void
    {
        $data = ['name' => 'ABC'];

        $result = $this->validator->validate($data);

        $this->assertTrue($result->isValid());
    }

    public function testValidateWithNameTooLong(): void
    {
        $data = ['name' => str_repeat('A', 256)];

        $result = $this->validator->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrorsForField('name'));
        $this->assertContains('Tree name must not exceed 255 characters', $result->getErrorsForField('name'));
    }

    public function testValidateWithNameMaximumLength(): void
    {
        $data = ['name' => str_repeat('A', 255)];

        $result = $this->validator->validate($data);

        $this->assertTrue($result->isValid());
    }

    public function testValidateWithInvalidCharactersInName(): void
    {
        $invalidNames = [
            'Name with @',
            'Name with #',
            'Name with $',
            'Name with %',
            'Name with &',
            'Name with *',
            'Name with <',
            'Name with >',
            'Name with [',
            'Name with ]',
            'Name with {',
            'Name with }',
            'Name with |',
            'Name with \\',
            'Name with /',
            'Name with ?',
            'Name with !',
            'Name with ^',
            'Name with ~',
            'Name with `',
            'Name with ='
        ];

        foreach ($invalidNames as $invalidName) {
            $data = ['name' => $invalidName];
            $result = $this->validator->validate($data);

            $this->assertFalse($result->isValid(), "Name '$invalidName' should be invalid");
            $this->assertTrue($result->hasErrorsForField('name'));
            $this->assertContains('Tree name contains invalid characters', $result->getErrorsForField('name'));
        }
    }

    public function testValidateWithValidCharactersInName(): void
    {
        $validNames = [
            'Valid Name',
            'Valid-Name',
            'Valid_Name',
            'Valid.Name',
            'Valid(Name)',
            'ValidName123',
            'Valid Name 123-_().'
        ];

        foreach ($validNames as $validName) {
            $data = ['name' => $validName];
            $result = $this->validator->validate($data);

            $this->assertTrue($result->isValid(), "Name '$validName' should be valid");
        }
    }

    public function testValidateWithDescriptionTooLong(): void
    {
        $data = [
            'name' => 'Valid Name',
            'description' => str_repeat('A', 1001)
        ];

        $result = $this->validator->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrorsForField('description'));
        $this->assertContains('Description must not exceed 1000 characters', $result->getErrorsForField('description'));
    }

    public function testValidateWithDescriptionMaximumLength(): void
    {
        $data = [
            'name' => 'Valid Name',
            'description' => str_repeat('A', 1000)
        ];

        $result = $this->validator->validate($data);

        $this->assertTrue($result->isValid());
    }

    public function testValidateWithHtmlInDescription(): void
    {
        $data = [
            'name' => 'Valid Name',
            'description' => 'Description with <script>alert("XSS")</script>'
        ];

        $result = $this->validator->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrorsForField('description'));
        $this->assertContains('Description cannot contain HTML tags', $result->getErrorsForField('description'));
    }

    public function testValidateWithSimpleHtmlInDescription(): void
    {
        $htmlDescriptions = [
            'Description with <b>bold</b>',
            'Description with <i>italic</i>',
            'Description with <p>paragraph</p>',
            'Description with <div>div</div>',
            'Description with <span>span</span>'
        ];

        foreach ($htmlDescriptions as $htmlDescription) {
            $data = [
                'name' => 'Valid Name',
                'description' => $htmlDescription
            ];

            $result = $this->validator->validate($data);

            $this->assertFalse($result->isValid(), "Description '$htmlDescription' should be invalid");
            $this->assertTrue($result->hasErrorsForField('description'));
            $this->assertContains('Description cannot contain HTML tags', $result->getErrorsForField('description'));
        }
    }

    public function testSanitizeWithValidData(): void
    {
        $data = [
            'name' => '  Test Name  ',
            'description' => '  Test Description  '
        ];

        $sanitized = $this->validator->sanitize($data);

        $this->assertEquals('Test Name', $sanitized['name']);
        $this->assertEquals('Test Description', $sanitized['description']);
    }

    public function testSanitizeWithHtmlCharacters(): void
    {
        $data = [
            'name' => '<script>alert("test")</script>Tree Name',
            'description' => '<b>Bold</b> description'
        ];

        $sanitized = $this->validator->sanitize($data);

        $this->assertEquals('&lt;script&gt;alert(&quot;test&quot;)&lt;/script&gt;Tree Name', $sanitized['name']);
        $this->assertEquals('&lt;b&gt;Bold&lt;/b&gt; description', $sanitized['description']);
    }

    public function testSanitizeWithNullDescription(): void
    {
        $data = [
            'name' => 'Test Name',
            'description' => null
        ];

        $sanitized = $this->validator->sanitize($data);

        $this->assertEquals('Test Name', $sanitized['name']);
        $this->assertNull($sanitized['description']);
    }

    public function testSanitizeWithMissingDescription(): void
    {
        $data = ['name' => 'Test Name'];

        $sanitized = $this->validator->sanitize($data);

        $this->assertEquals('Test Name', $sanitized['name']);
        $this->assertArrayNotHasKey('description', $sanitized);
    }

    public function testSanitizeWithEmptyData(): void
    {
        $data = [];

        $sanitized = $this->validator->sanitize($data);

        $this->assertEmpty($sanitized);
    }

    public function testSanitizePreservesQuotes(): void
    {
        $data = [
            'name' => "Name with 'single' and \"double\" quotes",
            'description' => "Description with 'quotes'"
        ];

        $sanitized = $this->validator->sanitize($data);

        $this->assertEquals('Name with &#039;single&#039; and &quot;double&quot; quotes', $sanitized['name']);
        $this->assertEquals('Description with &#039;quotes&#039;', $sanitized['description']);
    }
}