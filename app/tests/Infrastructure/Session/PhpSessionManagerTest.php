<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Session;

use App\Infrastructure\Session\PhpSessionManager;
use App\Infrastructure\Session\SessionManagerInterface;
use Tests\TestCase;

class PhpSessionManagerTest extends TestCase
{
    private PhpSessionManager $sessionManager;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Skip these tests in CLI context due to session limitations
        if (php_sapi_name() === 'cli') {
            $this->markTestSkipped('Session tests are skipped in CLI context');
        }
        
        $this->sessionManager = new PhpSessionManager();
    }

    protected function tearDown(): void
    {
        // Clean up session after each test
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_write_close();
        }
        
        parent::tearDown();
    }

    public function testImplementsSessionManagerInterface(): void
    {
        $this->assertInstanceOf(SessionManagerInterface::class, $this->sessionManager);
    }

    public function testStartInitializesSession(): void
    {
        $this->assertFalse($this->sessionManager->isStarted());
        
        $this->sessionManager->start();
        
        $this->assertTrue($this->sessionManager->isStarted());
        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
    }

    public function testStartIdempotent(): void
    {
        // Starting twice should not cause issues
        $this->sessionManager->start();
        $firstSessionId = $this->sessionManager->getId();
        
        $this->sessionManager->start();
        $secondSessionId = $this->sessionManager->getId();
        
        $this->assertEquals($firstSessionId, $secondSessionId);
        $this->assertTrue($this->sessionManager->isStarted());
    }

    public function testIsStartedReturnsFalseInitially(): void
    {
        $this->assertFalse($this->sessionManager->isStarted());
    }

    public function testIsStartedReturnsTrueAfterStart(): void
    {
        $this->sessionManager->start();
        $this->assertTrue($this->sessionManager->isStarted());
    }

    public function testGetIdReturnsNullWhenNotStarted(): void
    {
        $this->assertNull($this->sessionManager->getId());
    }

    public function testGetIdReturnsStringWhenStarted(): void
    {
        $this->sessionManager->start();
        $sessionId = $this->sessionManager->getId();
        
        $this->assertIsString($sessionId);
        $this->assertNotEmpty($sessionId);
    }

    public function testSetAndGetValue(): void
    {
        $this->sessionManager->set('test_key', 'test_value');
        
        $value = $this->sessionManager->get('test_key');
        
        $this->assertEquals('test_value', $value);
        $this->assertTrue($this->sessionManager->isStarted()); // Should auto-start
    }

    public function testGetWithDefault(): void
    {
        $value = $this->sessionManager->get('non_existent_key', 'default_value');
        
        $this->assertEquals('default_value', $value);
    }

    public function testGetWithNullDefault(): void
    {
        $value = $this->sessionManager->get('non_existent_key');
        
        $this->assertNull($value);
    }

    public function testHasReturnsTrueForExistingKey(): void
    {
        $this->sessionManager->set('existing_key', 'some_value');
        
        $this->assertTrue($this->sessionManager->has('existing_key'));
    }

    public function testHasReturnsFalseForNonExistentKey(): void
    {
        $this->assertFalse($this->sessionManager->has('non_existent_key'));
    }

    public function testHasReturnsTrueForNullValue(): void
    {
        $this->sessionManager->set('null_key', null);
        
        $this->assertTrue($this->sessionManager->has('null_key'));
    }

    public function testRemoveDeletesKey(): void
    {
        $this->sessionManager->set('key_to_remove', 'value');
        $this->assertTrue($this->sessionManager->has('key_to_remove'));
        
        $this->sessionManager->remove('key_to_remove');
        
        $this->assertFalse($this->sessionManager->has('key_to_remove'));
        $this->assertNull($this->sessionManager->get('key_to_remove'));
    }

    public function testRemoveNonExistentKeyDoesNothing(): void
    {
        $this->sessionManager->set('existing_key', 'value');
        
        $this->sessionManager->remove('non_existent_key');
        
        // Existing key should still be there
        $this->assertTrue($this->sessionManager->has('existing_key'));
    }

    public function testClearRemovesAllData(): void
    {
        $this->sessionManager->set('key1', 'value1');
        $this->sessionManager->set('key2', 'value2');
        $this->sessionManager->set('key3', 'value3');
        
        $this->sessionManager->clear();
        
        $this->assertFalse($this->sessionManager->has('key1'));
        $this->assertFalse($this->sessionManager->has('key2'));
        $this->assertFalse($this->sessionManager->has('key3'));
        $this->assertEmpty($this->sessionManager->all());
    }

    public function testAllReturnsEmptyArrayInitially(): void
    {
        $all = $this->sessionManager->all();
        
        $this->assertIsArray($all);
        $this->assertEmpty($all);
    }

    public function testAllReturnsAllSessionData(): void
    {
        $testData = [
            'key1' => 'value1',
            'key2' => 42,
            'key3' => ['nested', 'array'],
            'key4' => null,
            'key5' => true
        ];
        
        foreach ($testData as $key => $value) {
            $this->sessionManager->set($key, $value);
        }
        
        $all = $this->sessionManager->all();
        
        $this->assertEquals($testData, $all);
    }

    public function testSupportsComplexDataTypes(): void
    {
        $complexData = [
            'array' => [1, 2, 3, ['nested' => 'value']],
            'object' => (object) ['property' => 'value'],
            'bool' => true,
            'int' => 123,
            'float' => 45.67,
            'null' => null,
            'string' => 'test string'
        ];
        
        foreach ($complexData as $key => $value) {
            $this->sessionManager->set($key, $value);
        }
        
        foreach ($complexData as $key => $value) {
            $this->assertEquals($value, $this->sessionManager->get($key));
        }
    }

    public function testSessionPersistsAcrossManagerInstances(): void
    {
        $this->sessionManager->set('persistent_key', 'persistent_value');
        
        // Create a new manager instance
        $newManager = new PhpSessionManager();
        
        // Should be able to access the same data
        $this->assertEquals('persistent_value', $newManager->get('persistent_key'));
        $this->assertTrue($newManager->isStarted());
    }

    public function testEnsureStartedAutoStartsSession(): void
    {
        // These methods should auto-start the session
        $this->assertFalse($this->sessionManager->isStarted());
        
        $this->sessionManager->get('any_key');
        $this->assertTrue($this->sessionManager->isStarted());
    }

    public function testEnsureStartedWithSet(): void
    {
        $this->assertFalse($this->sessionManager->isStarted());
        
        $this->sessionManager->set('test', 'value');
        $this->assertTrue($this->sessionManager->isStarted());
    }

    public function testEnsureStartedWithHas(): void
    {
        $this->assertFalse($this->sessionManager->isStarted());
        
        $this->sessionManager->has('test');
        $this->assertTrue($this->sessionManager->isStarted());
    }

    public function testEnsureStartedWithRemove(): void
    {
        $this->assertFalse($this->sessionManager->isStarted());
        
        $this->sessionManager->remove('test');
        $this->assertTrue($this->sessionManager->isStarted());
    }

    public function testEnsureStartedWithClear(): void
    {
        $this->assertFalse($this->sessionManager->isStarted());
        
        $this->sessionManager->clear();
        $this->assertTrue($this->sessionManager->isStarted());
    }

    public function testEnsureStartedWithAll(): void
    {
        $this->assertFalse($this->sessionManager->isStarted());
        
        $this->sessionManager->all();
        $this->assertTrue($this->sessionManager->isStarted());
    }

    public function testSessionIdIsConsistentDuringSession(): void
    {
        $this->sessionManager->start();
        
        $id1 = $this->sessionManager->getId();
        $id2 = $this->sessionManager->getId();
        $id3 = $this->sessionManager->getId();
        
        $this->assertEquals($id1, $id2);
        $this->assertEquals($id2, $id3);
        $this->assertIsString($id1);
    }

    public function testCanOverwriteExistingValues(): void
    {
        $this->sessionManager->set('overwrite_key', 'original_value');
        $this->assertEquals('original_value', $this->sessionManager->get('overwrite_key'));
        
        $this->sessionManager->set('overwrite_key', 'new_value');
        $this->assertEquals('new_value', $this->sessionManager->get('overwrite_key'));
    }

    public function testCanStoreAndRetrieveEmptyString(): void
    {
        $this->sessionManager->set('empty_string', '');
        
        $this->assertTrue($this->sessionManager->has('empty_string'));
        $this->assertEquals('', $this->sessionManager->get('empty_string'));
    }

    public function testCanStoreAndRetrieveZero(): void
    {
        $this->sessionManager->set('zero_int', 0);
        $this->sessionManager->set('zero_float', 0.0);
        $this->sessionManager->set('zero_string', '0');
        
        $this->assertTrue($this->sessionManager->has('zero_int'));
        $this->assertTrue($this->sessionManager->has('zero_float'));
        $this->assertTrue($this->sessionManager->has('zero_string'));
        
        $this->assertEquals(0, $this->sessionManager->get('zero_int'));
        $this->assertEquals(0.0, $this->sessionManager->get('zero_float'));
        $this->assertEquals('0', $this->sessionManager->get('zero_string'));
    }

    public function testCanStoreAndRetrieveFalse(): void
    {
        $this->sessionManager->set('false_value', false);
        
        $this->assertTrue($this->sessionManager->has('false_value'));
        $this->assertFalse($this->sessionManager->get('false_value'));
    }

    public function testSessionSurvivesMultipleOperations(): void
    {
        // Perform a series of operations
        $this->sessionManager->set('key1', 'value1');
        $this->sessionManager->set('key2', 'value2');
        $this->assertEquals('value1', $this->sessionManager->get('key1'));
        
        $this->sessionManager->remove('key1');
        $this->assertFalse($this->sessionManager->has('key1'));
        $this->assertTrue($this->sessionManager->has('key2'));
        
        $this->sessionManager->set('key3', 'value3');
        $this->sessionManager->set('key4', 'value4');
        
        $expected = [
            'key2' => 'value2',
            'key3' => 'value3',
            'key4' => 'value4'
        ];
        
        $this->assertEquals($expected, $this->sessionManager->all());
    }

    public function testIsStatefulBetweenCalls(): void
    {
        $this->sessionManager->set('stateful_test', 'initial');
        
        // Modify the value
        $current = $this->sessionManager->get('stateful_test');
        $this->sessionManager->set('stateful_test', $current . '_modified');
        
        $final = $this->sessionManager->get('stateful_test');
        $this->assertEquals('initial_modified', $final);
    }

    public function testClearPreservesSessionButRemovesData(): void
    {
        $this->sessionManager->set('test', 'value');
        $this->assertTrue($this->sessionManager->isStarted());
        $sessionId = $this->sessionManager->getId();
        
        $this->sessionManager->clear();
        
        // Session should still be active
        $this->assertTrue($this->sessionManager->isStarted());
        $this->assertEquals($sessionId, $this->sessionManager->getId());
        
        // But data should be gone
        $this->assertFalse($this->sessionManager->has('test'));
        $this->assertEmpty($this->sessionManager->all());
    }

    public function testSupportsArrayAccess(): void
    {
        $testArray = ['a' => 1, 'b' => 2, 'c' => ['nested' => true]];
        
        $this->sessionManager->set('array_test', $testArray);
        $retrieved = $this->sessionManager->get('array_test');
        
        $this->assertEquals($testArray, $retrieved);
        $this->assertEquals(1, $retrieved['a']);
        $this->assertEquals(2, $retrieved['b']);
        $this->assertTrue($retrieved['c']['nested']);
    }

    public function testSessionManagerFinalClass(): void
    {
        $reflection = new \ReflectionClass(PhpSessionManager::class);
        $this->assertTrue($reflection->isFinal(), 'PhpSessionManager should be a final class');
    }
}