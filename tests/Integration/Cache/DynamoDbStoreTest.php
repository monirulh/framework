<?php

namespace Illuminate\Tests\Integration\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase;

/**
 * @group integration
 */
class DynamoDbStoreTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        if (! env('DYNAMODB_CACHE_TABLE')) {
            $this->markTestSkipped('DynamoDB not configured.');
        }

        $app['config']->set('cache.default', 'dynamodb');
    }

    protected function setUp(): void
    {
        if (! env('DYNAMODB_CACHE_TABLE')) {
            $this->markTestSkipped('DynamoDB not configured.');
        }

        parent::setUp();

        $this->artisan('cache:dynamodb');
    }

    public function testItemsCanBeStoredAndRetrieved()
    {
        Cache::driver('dynamodb')->put('name', 'Taylor', 10);
        $this->assertSame('Taylor', Cache::driver('dynamodb')->get('name'));

        Cache::driver('dynamodb')->put(['name' => 'Abigail', 'age' => 28], 10);
        $this->assertSame('Abigail', Cache::driver('dynamodb')->get('name'));
        $this->assertEquals(28, Cache::driver('dynamodb')->get('age'));

        $this->assertEquals([
            'name' => 'Abigail',
            'age' => 28,
            'height' => null,
        ], Cache::driver('dynamodb')->many(['name', 'age', 'height']));

        Cache::driver('dynamodb')->forget('name');
        $this->assertNull(Cache::driver('dynamodb')->get('name'));
    }

    public function testItemsCanBeAtomicallyAdded()
    {
        $key = Str::random(6);

        $this->assertTrue(Cache::driver('dynamodb')->add($key, 'Taylor', 10));
        $this->assertFalse(Cache::driver('dynamodb')->add($key, 'Taylor', 10));
    }

    public function testItemsCanBeIncrementedAndDecremented()
    {
        Cache::driver('dynamodb')->put('counter', 0, 10);
        Cache::driver('dynamodb')->increment('counter');
        Cache::driver('dynamodb')->increment('counter', 4);

        $this->assertEquals(5, Cache::driver('dynamodb')->get('counter'));

        Cache::driver('dynamodb')->decrement('counter', 5);
        $this->assertEquals(0, Cache::driver('dynamodb')->get('counter'));
    }

    public function testLocksCanBeAcquired()
    {
        Cache::driver('dynamodb')->lock('lock', 10)->get(function () {
            $this->assertFalse(Cache::driver('dynamodb')->lock('lock', 10)->get());
        });
    }
}
