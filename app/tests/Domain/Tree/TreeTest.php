<?php

declare(strict_types=1);

namespace App\Tests\Domain\Tree;

use App\Domain\Tree\Tree;
use PHPUnit\Framework\TestCase;
use DateTime;

class TreeTest extends TestCase
{
    public function testConstructorWithAllParameters(): void
    {
        $id = 1;
        $name = 'Test Tree';
        $description = 'A test tree';
        $createdAt = new DateTime('2023-01-01 10:00:00');
        $updatedAt = new DateTime('2023-01-01 11:00:00');
        $isActive = true;

        $tree = new Tree($id, $name, $description, $createdAt, $updatedAt, $isActive);

        $this->assertEquals($id, $tree->getId());
        $this->assertEquals($name, $tree->getName());
        $this->assertEquals($description, $tree->getDescription());
        $this->assertEquals($createdAt, $tree->getCreatedAt());
        $this->assertEquals($updatedAt, $tree->getUpdatedAt());
        $this->assertEquals($isActive, $tree->isActive());
    }

    public function testConstructorWithMinimalParameters(): void
    {
        $name = 'Minimal Tree';

        $tree = new Tree(null, $name);

        $this->assertNull($tree->getId());
        $this->assertEquals($name, $tree->getName());
        $this->assertNull($tree->getDescription());
        $this->assertInstanceOf(DateTime::class, $tree->getCreatedAt());
        $this->assertInstanceOf(DateTime::class, $tree->getUpdatedAt());
        $this->assertTrue($tree->isActive());
    }

    public function testConstructorWithNullId(): void
    {
        $tree = new Tree(null, 'Tree with Null ID');

        $this->assertNull($tree->getId());
        $this->assertEquals('Tree with Null ID', $tree->getName());
    }

    public function testConstructorWithZeroId(): void
    {
        $tree = new Tree(0, 'Tree with Zero ID');

        $this->assertEquals(0, $tree->getId());
        $this->assertEquals('Tree with Zero ID', $tree->getName());
    }

    public function testConstructorWithEmptyName(): void
    {
        $tree = new Tree(1, '');

        $this->assertEquals(1, $tree->getId());
        $this->assertEquals('', $tree->getName());
    }

    public function testConstructorWithNullDescription(): void
    {
        $tree = new Tree(1, 'Tree Name', null);

        $this->assertNull($tree->getDescription());
    }

    public function testConstructorWithEmptyDescription(): void
    {
        $tree = new Tree(1, 'Tree Name', '');

        $this->assertEquals('', $tree->getDescription());
    }

    public function testConstructorWithInactiveTree(): void
    {
        $tree = new Tree(1, 'Inactive Tree', 'Description', null, null, false);

        $this->assertFalse($tree->isActive());
    }

    public function testGetId(): void
    {
        $tree = new Tree(123, 'Test Tree');

        $this->assertEquals(123, $tree->getId());
    }

    public function testGetName(): void
    {
        $tree = new Tree(1, 'My Tree Name');

        $this->assertEquals('My Tree Name', $tree->getName());
    }

    public function testGetDescription(): void
    {
        $tree = new Tree(1, 'Tree Name', 'Tree Description');

        $this->assertEquals('Tree Description', $tree->getDescription());
    }

    public function testGetCreatedAt(): void
    {
        $createdAt = new DateTime('2023-01-01 10:00:00');
        $tree = new Tree(1, 'Tree Name', null, $createdAt);

        $this->assertEquals($createdAt, $tree->getCreatedAt());
    }

    public function testGetUpdatedAt(): void
    {
        $updatedAt = new DateTime('2023-01-01 11:00:00');
        $tree = new Tree(1, 'Tree Name', null, null, $updatedAt);

        $this->assertEquals($updatedAt, $tree->getUpdatedAt());
    }

    public function testIsActive(): void
    {
        $tree = new Tree(1, 'Tree Name', null, null, null, true);

        $this->assertTrue($tree->isActive());
    }

    public function testSetName(): void
    {
        $tree = new Tree(1, 'Original Name');
        $originalUpdatedAt = $tree->getUpdatedAt();

        // Wait a moment to ensure different timestamps
        usleep(1000);

        $tree->setName('New Name');

        $this->assertEquals('New Name', $tree->getName());
        $this->assertGreaterThan($originalUpdatedAt, $tree->getUpdatedAt());
    }

    public function testSetNameWithEmptyString(): void
    {
        $tree = new Tree(1, 'Original Name');
        $originalUpdatedAt = $tree->getUpdatedAt();

        usleep(1000);

        $tree->setName('');

        $this->assertEquals('', $tree->getName());
        $this->assertGreaterThan($originalUpdatedAt, $tree->getUpdatedAt());
    }

    public function testSetDescription(): void
    {
        $tree = new Tree(1, 'Tree Name', 'Original Description');
        $originalUpdatedAt = $tree->getUpdatedAt();

        usleep(1000);

        $tree->setDescription('New Description');

        $this->assertEquals('New Description', $tree->getDescription());
        $this->assertGreaterThan($originalUpdatedAt, $tree->getUpdatedAt());
    }

    public function testSetDescriptionWithNull(): void
    {
        $tree = new Tree(1, 'Tree Name', 'Original Description');
        $originalUpdatedAt = $tree->getUpdatedAt();

        usleep(1000);

        $tree->setDescription(null);

        $this->assertNull($tree->getDescription());
        $this->assertGreaterThan($originalUpdatedAt, $tree->getUpdatedAt());
    }

    public function testSetDescriptionWithEmptyString(): void
    {
        $tree = new Tree(1, 'Tree Name', 'Original Description');
        $originalUpdatedAt = $tree->getUpdatedAt();

        usleep(1000);

        $tree->setDescription('');

        $this->assertEquals('', $tree->getDescription());
        $this->assertGreaterThan($originalUpdatedAt, $tree->getUpdatedAt());
    }

    public function testSetActive(): void
    {
        $tree = new Tree(1, 'Tree Name', null, null, null, true);
        $originalUpdatedAt = $tree->getUpdatedAt();

        usleep(1000);

        $tree->setActive(false);

        $this->assertFalse($tree->isActive());
        $this->assertGreaterThan($originalUpdatedAt, $tree->getUpdatedAt());
    }

    public function testSetActiveToTrue(): void
    {
        $tree = new Tree(1, 'Tree Name', null, null, null, false);
        $originalUpdatedAt = $tree->getUpdatedAt();

        usleep(1000);

        $tree->setActive(true);

        $this->assertTrue($tree->isActive());
        $this->assertGreaterThan($originalUpdatedAt, $tree->getUpdatedAt());
    }

    public function testJsonSerialize(): void
    {
        $createdAt = new DateTime('2023-01-01 10:00:00');
        $updatedAt = new DateTime('2023-01-01 11:00:00');
        $tree = new Tree(1, 'Test Tree', 'Test Description', $createdAt, $updatedAt, true);

        $serialized = $tree->jsonSerialize();

        $expected = [
            'id' => 1,
            'name' => 'Test Tree',
            'description' => 'Test Description',
            'createdAt' => '2023-01-01 10:00:00',
            'updatedAt' => '2023-01-01 11:00:00',
            'isActive' => true,
        ];

        $this->assertEquals($expected, $serialized);
    }

    public function testJsonSerializeWithNullValues(): void
    {
        $createdAt = new DateTime('2023-01-01 10:00:00');
        $updatedAt = new DateTime('2023-01-01 11:00:00');
        $tree = new Tree(null, 'Test Tree', null, $createdAt, $updatedAt, false);

        $serialized = $tree->jsonSerialize();

        $expected = [
            'id' => null,
            'name' => 'Test Tree',
            'description' => null,
            'createdAt' => '2023-01-01 10:00:00',
            'updatedAt' => '2023-01-01 11:00:00',
            'isActive' => false,
        ];

        $this->assertEquals($expected, $serialized);
    }

    public function testJsonSerializeWithEmptyDescription(): void
    {
        $createdAt = new DateTime('2023-01-01 10:00:00');
        $updatedAt = new DateTime('2023-01-01 11:00:00');
        $tree = new Tree(1, 'Test Tree', '', $createdAt, $updatedAt, true);

        $serialized = $tree->jsonSerialize();

        $expected = [
            'id' => 1,
            'name' => 'Test Tree',
            'description' => '',
            'createdAt' => '2023-01-01 10:00:00',
            'updatedAt' => '2023-01-01 11:00:00',
            'isActive' => true,
        ];

        $this->assertEquals($expected, $serialized);
    }

    public function testJsonSerializeWithZeroId(): void
    {
        $createdAt = new DateTime('2023-01-01 10:00:00');
        $updatedAt = new DateTime('2023-01-01 11:00:00');
        $tree = new Tree(0, 'Test Tree', 'Description', $createdAt, $updatedAt, true);

        $serialized = $tree->jsonSerialize();

        $expected = [
            'id' => 0,
            'name' => 'Test Tree',
            'description' => 'Description',
            'createdAt' => '2023-01-01 10:00:00',
            'updatedAt' => '2023-01-01 11:00:00',
            'isActive' => true,
        ];

        $this->assertEquals($expected, $serialized);
    }

    public function testJsonSerializeWithSpecialCharacters(): void
    {
        $createdAt = new DateTime('2023-01-01 10:00:00');
        $updatedAt = new DateTime('2023-01-01 11:00:00');
        $tree = new Tree(1, 'Tree with "quotes" & symbols', 'Description with "quotes" & <tags>', $createdAt, $updatedAt, true);

        $serialized = $tree->jsonSerialize();

        $expected = [
            'id' => 1,
            'name' => 'Tree with "quotes" & symbols',
            'description' => 'Description with "quotes" & <tags>',
            'createdAt' => '2023-01-01 10:00:00',
            'updatedAt' => '2023-01-01 11:00:00',
            'isActive' => true,
        ];

        $this->assertEquals($expected, $serialized);
    }

    public function testJsonSerializeWithUnicodeCharacters(): void
    {
        $createdAt = new DateTime('2023-01-01 10:00:00');
        $updatedAt = new DateTime('2023-01-01 11:00:00');
        $tree = new Tree(1, 'Tree with émojis 🌳', 'Description with émojis 🌲', $createdAt, $updatedAt, true);

        $serialized = $tree->jsonSerialize();

        $expected = [
            'id' => 1,
            'name' => 'Tree with émojis 🌳',
            'description' => 'Description with émojis 🌲',
            'createdAt' => '2023-01-01 10:00:00',
            'updatedAt' => '2023-01-01 11:00:00',
            'isActive' => true,
        ];

        $this->assertEquals($expected, $serialized);
    }

    public function testMultipleSettersUpdateTimestamp(): void
    {
        $tree = new Tree(1, 'Original Name', 'Original Description');
        $originalUpdatedAt = $tree->getUpdatedAt();

        usleep(1000);
        $tree->setName('New Name');

        usleep(1000);
        $tree->setDescription('New Description');

        usleep(1000);
        $tree->setActive(false);

        $this->assertEquals('New Name', $tree->getName());
        $this->assertEquals('New Description', $tree->getDescription());
        $this->assertFalse($tree->isActive());
        $this->assertGreaterThan($originalUpdatedAt, $tree->getUpdatedAt());
    }

    public function testConstructorWithCustomDateTimeObjects(): void
    {
        $customCreatedAt = new DateTime('2022-12-31 23:59:59');
        $customUpdatedAt = new DateTime('2023-01-01 00:00:01');
        
        $tree = new Tree(1, 'Custom Tree', 'Custom Description', $customCreatedAt, $customUpdatedAt, true);

        $this->assertEquals($customCreatedAt, $tree->getCreatedAt());
        $this->assertEquals($customUpdatedAt, $tree->getUpdatedAt());
    }

    public function testConstructorWithOnlyCreatedAt(): void
    {
        $customCreatedAt = new DateTime('2023-01-01 10:00:00');
        $tree = new Tree(1, 'Tree Name', 'Description', $customCreatedAt);

        $this->assertEquals($customCreatedAt, $tree->getCreatedAt());
        $this->assertInstanceOf(DateTime::class, $tree->getUpdatedAt());
        $this->assertNotEquals($customCreatedAt, $tree->getUpdatedAt());
    }

    public function testConstructorWithOnlyUpdatedAt(): void
    {
        $customUpdatedAt = new DateTime('2023-01-01 11:00:00');
        $tree = new Tree(1, 'Tree Name', 'Description', null, $customUpdatedAt);

        $this->assertInstanceOf(DateTime::class, $tree->getCreatedAt());
        $this->assertEquals($customUpdatedAt, $tree->getUpdatedAt());
        $this->assertNotEquals($customUpdatedAt, $tree->getCreatedAt());
    }
} 