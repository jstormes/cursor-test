<?php

declare(strict_types=1);

namespace App\Tests\Domain\Tree;

use App\Domain\Tree\Tree;
use App\Tests\Utilities\MockClock;
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

        $tree = new Tree($id, $name, $description, $createdAt, $updatedAt, $isActive, new MockClock());

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

        $tree = new Tree(null, $name, null, null, null, true, new MockClock());

        $this->assertNull($tree->getId());
        $this->assertEquals($name, $tree->getName());
        $this->assertNull($tree->getDescription());
        $this->assertInstanceOf(DateTime::class, $tree->getCreatedAt());
        $this->assertInstanceOf(DateTime::class, $tree->getUpdatedAt());
        $this->assertTrue($tree->isActive());
    }

    public function testConstructorWithNullId(): void
    {
        $tree = new Tree(null, 'Tree with Null ID', null, null, null, true, new MockClock());

        $this->assertNull($tree->getId());
        $this->assertEquals('Tree with Null ID', $tree->getName());
    }

    public function testConstructorWithZeroId(): void
    {
        $tree = new Tree(0, 'Tree with Zero ID', null, null, null, true, new MockClock());

        $this->assertEquals(0, $tree->getId());
        $this->assertEquals('Tree with Zero ID', $tree->getName());
    }

    public function testConstructorWithEmptyName(): void
    {
        $tree = new Tree(1, '', null, null, null, true, new MockClock());

        $this->assertEquals(1, $tree->getId());
        $this->assertEquals('', $tree->getName());
    }

    public function testConstructorWithNullDescription(): void
    {
        $tree = new Tree(1, 'Tree Name', null, null, null, true, new MockClock());

        $this->assertNull($tree->getDescription());
    }

    public function testConstructorWithEmptyDescription(): void
    {
        $tree = new Tree(1, 'Tree Name', '', null, null, true, new MockClock());

        $this->assertEquals('', $tree->getDescription());
    }

    public function testConstructorWithInactiveTree(): void
    {
        $tree = new Tree(1, 'Inactive Tree', 'Description', null, null, false, new MockClock());

        $this->assertFalse($tree->isActive());
    }

    public function testGetId(): void
    {
        $tree = new Tree(123, 'Test Tree', null, null, null, true, new MockClock());

        $this->assertEquals(123, $tree->getId());
    }

    public function testGetName(): void
    {
        $tree = new Tree(1, 'My Tree Name', null, null, null, true, new MockClock());

        $this->assertEquals('My Tree Name', $tree->getName());
    }

    public function testGetDescription(): void
    {
        $tree = new Tree(1, 'Tree Name', 'Tree Description', null, null, true, new MockClock());

        $this->assertEquals('Tree Description', $tree->getDescription());
    }

    public function testGetCreatedAt(): void
    {
        $createdAt = new DateTime('2023-01-01 10:00:00');
        $tree = new Tree(1, 'Tree Name', null, $createdAt, null, true, new MockClock());

        $this->assertEquals($createdAt, $tree->getCreatedAt());
    }

    public function testGetUpdatedAt(): void
    {
        $updatedAt = new DateTime('2023-01-01 11:00:00');
        $tree = new Tree(1, 'Tree Name', null, null, $updatedAt, true, new MockClock());

        $this->assertEquals($updatedAt, $tree->getUpdatedAt());
    }

    public function testIsActive(): void
    {
        $tree = new Tree(1, 'Tree Name', null, null, null, true, new MockClock());

        $this->assertTrue($tree->isActive());
    }

    public function testSetName(): void
    {
        $mockClock = new MockClock(new DateTime('2023-01-01 10:00:00'));
        $tree = new Tree(1, 'Original Name', null, null, null, true, $mockClock);
        $originalUpdatedAt = $tree->getUpdatedAt();

        // Advance clock time to simulate time passing
        $mockClock->setTime(new DateTime('2023-01-01 10:00:01'));

        $tree->setName('New Name');

        $this->assertEquals('New Name', $tree->getName());
        $this->assertGreaterThan($originalUpdatedAt, $tree->getUpdatedAt());
    }

    public function testSetNameWithEmptyString(): void
    {
        $mockClock = new MockClock(new DateTime('2023-01-01 10:00:00'));
        $tree = new Tree(1, 'Original Name', null, null, null, true, $mockClock);
        $originalUpdatedAt = $tree->getUpdatedAt();

        $mockClock->setTime(new DateTime('2023-01-01 10:00:01'));

        $tree->setName('');

        $this->assertEquals('', $tree->getName());
        $this->assertGreaterThan($originalUpdatedAt, $tree->getUpdatedAt());
    }

    public function testSetDescription(): void
    {
        $mockClock = new MockClock(new DateTime('2023-01-01 10:00:00'));
        $tree = new Tree(1, 'Tree Name', 'Original Description', null, null, true, $mockClock);
        $originalUpdatedAt = $tree->getUpdatedAt();

        $mockClock->setTime(new DateTime('2023-01-01 10:00:01'));

        $tree->setDescription('New Description');

        $this->assertEquals('New Description', $tree->getDescription());
        $this->assertGreaterThan($originalUpdatedAt, $tree->getUpdatedAt());
    }

    public function testSetDescriptionWithNull(): void
    {
        $mockClock = new MockClock(new DateTime('2023-01-01 10:00:00'));
        $tree = new Tree(1, 'Tree Name', 'Original Description', null, null, true, $mockClock);
        $originalUpdatedAt = $tree->getUpdatedAt();

        $mockClock->setTime(new DateTime('2023-01-01 10:00:01'));

        $tree->setDescription(null);

        $this->assertNull($tree->getDescription());
        $this->assertGreaterThan($originalUpdatedAt, $tree->getUpdatedAt());
    }

    public function testSetDescriptionWithEmptyString(): void
    {
        $mockClock = new MockClock(new DateTime('2023-01-01 10:00:00'));
        $tree = new Tree(1, 'Tree Name', 'Original Description', null, null, true, $mockClock);
        $originalUpdatedAt = $tree->getUpdatedAt();

        $mockClock->setTime(new DateTime('2023-01-01 10:00:01'));

        $tree->setDescription('');

        $this->assertEquals('', $tree->getDescription());
        $this->assertGreaterThan($originalUpdatedAt, $tree->getUpdatedAt());
    }

    public function testSetActive(): void
    {
        $mockClock = new MockClock(new DateTime('2023-01-01 10:00:00'));
        $tree = new Tree(1, 'Tree Name', null, null, null, true, $mockClock);
        $originalUpdatedAt = $tree->getUpdatedAt();

        $mockClock->setTime(new DateTime('2023-01-01 10:00:01'));

        $tree->setActive(false);

        $this->assertFalse($tree->isActive());
        $this->assertGreaterThan($originalUpdatedAt, $tree->getUpdatedAt());
    }

    public function testSetActiveToTrue(): void
    {
        $mockClock = new MockClock(new DateTime('2023-01-01 10:00:00'));
        $tree = new Tree(1, 'Tree Name', null, null, null, false, $mockClock);
        $originalUpdatedAt = $tree->getUpdatedAt();

        $mockClock->setTime(new DateTime('2023-01-01 10:00:01'));

        $tree->setActive(true);

        $this->assertTrue($tree->isActive());
        $this->assertGreaterThan($originalUpdatedAt, $tree->getUpdatedAt());
    }

    public function testJsonSerialize(): void
    {
        $createdAt = new DateTime('2023-01-01 10:00:00');
        $updatedAt = new DateTime('2023-01-01 11:00:00');
        $tree = new Tree(1, 'Test Tree', 'Test Description', $createdAt, $updatedAt, true, new MockClock());

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
        $tree = new Tree(null, 'Test Tree', null, $createdAt, $updatedAt, false, new MockClock());

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
        $tree = new Tree(1, 'Test Tree', '', $createdAt, $updatedAt, true, new MockClock());

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
        $tree = new Tree(0, 'Test Tree', 'Description', $createdAt, $updatedAt, true, new MockClock());

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
        $tree = new Tree(1, 'Tree with "quotes" & symbols', 'Description with "quotes" & <tags>', $createdAt, $updatedAt, true, new MockClock());

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
        $tree = new Tree(1, 'Tree with émojis 🌳', 'Description with émojis 🌲', $createdAt, $updatedAt, true, new MockClock());

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
        $mockClock = new MockClock(new DateTime('2023-01-01 10:00:00'));
        $tree = new Tree(1, 'Original Name', 'Original Description', null, null, true, $mockClock);
        $originalUpdatedAt = $tree->getUpdatedAt();

        $mockClock->setTime(new DateTime('2023-01-01 10:00:01'));
        $tree->setName('New Name');

        $mockClock->setTime(new DateTime('2023-01-01 10:00:02'));
        $tree->setDescription('New Description');

        $mockClock->setTime(new DateTime('2023-01-01 10:00:03'));
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

        $tree = new Tree(1, 'Custom Tree', 'Custom Description', $customCreatedAt, $customUpdatedAt, true, new MockClock());

        $this->assertEquals($customCreatedAt, $tree->getCreatedAt());
        $this->assertEquals($customUpdatedAt, $tree->getUpdatedAt());
    }

    public function testConstructorWithOnlyCreatedAt(): void
    {
        $customCreatedAt = new DateTime('2023-01-01 10:00:00');
        $mockClock = new MockClock(new DateTime('2023-01-01 11:00:00'));
        $tree = new Tree(1, 'Tree Name', 'Description', $customCreatedAt, null, true, $mockClock);

        $this->assertEquals($customCreatedAt, $tree->getCreatedAt());
        $this->assertInstanceOf(DateTime::class, $tree->getUpdatedAt());
        $this->assertNotEquals($customCreatedAt, $tree->getUpdatedAt());
    }

    public function testConstructorWithOnlyUpdatedAt(): void
    {
        $customUpdatedAt = new DateTime('2023-01-01 11:00:00');
        $tree = new Tree(1, 'Tree Name', 'Description', null, $customUpdatedAt, true, new MockClock());

        $this->assertInstanceOf(DateTime::class, $tree->getCreatedAt());
        $this->assertEquals($customUpdatedAt, $tree->getUpdatedAt());
        $this->assertNotEquals($customUpdatedAt, $tree->getCreatedAt());
    }
}
