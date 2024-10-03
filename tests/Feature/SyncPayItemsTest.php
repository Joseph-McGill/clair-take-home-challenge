<?php

namespace Tests\Feature;

use App\Jobs\SyncPayItems;
use App\Models\Business;
use Database\Seeders\DatabaseSeeder;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ReflectionClass;
use Tests\TestCase;

class SyncPayItemsTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    /**
     * Test handle() success
     */
    public function test_handle_success(): void
    {
        $fakeResponse = $this->fakeResponseData();
        Http::fake(['*' => Http::response($fakeResponse['validItems'], 200)]);

        $job = $this->setUpJob();
        $job->handle();

        $this->assertDatabaseCount('pay_items', 3);
    }

    /**
     * Test handle() failure
     */
    public function test_handle_fail(): void
    {
        Http::fake(['*' => Http::response([], 401)]);
        Log::shouldReceive('critical');

        $job = $this->setUpJob();
        $job->handle();

        $this->assertDatabaseCount('pay_items', 20);
    }

    /**
     * Test updatePayItems() success
     */
    public function test_updatePayItems_success(): void
    {
        $job = $this->setUpJob();
        $reflection = new ReflectionClass($job);
        $method = $reflection->getMethod('updatePayItems');
        $method->setAccessible(true);

        $data = $this->fakeResponseData()['validItems']['payItems'];
        $method->invokeArgs($job, [$data]);

        $this->assertDatabaseCount('pay_items', 23);
    }

    /**
     * Test updatePayItems() skipping over invalid users
     */
    public function test_updatePayItems_skips_invalid_users(): void
    {
        $job = $this->setUpJob();
        $reflection = new ReflectionClass($job);
        $method = $reflection->getMethod('updatePayItems');
        $method->setAccessible(true);

        $data = $this->fakeResponseData()['invalidUsers']['payItems'];
        $method->invokeArgs($job, [$data]);

        $this->assertDatabaseCount('pay_items', 20);
    }

    /**
     * Test fetchPayItems() success (200 returned from mock endpoint)
     */
    public function test_fetchPayItems_200(): void
    {
        $fakeResponse = $this->fakeResponseData();
        Http::fake(['*' => Http::response($fakeResponse['validItems'], 200)]);

        $job = $this->setUpJob();
        $reflection = new ReflectionClass($job);
        $method = $reflection->getMethod('fetchPayItems');
        $method->setAccessible(true);

        $response = $method->invokeArgs($job, [1]);
        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * Test fetchPayItems() failure (404 returned from mock endpoint)
     */
    public function test_fetchPayItems_404(): void
    {
        Http::fake(['*' => Http::response([], 404)]);

        $job = $this->setUpJob();
        $reflection = new ReflectionClass($job);
        $method = $reflection->getMethod('fetchPayItems');
        $method->setAccessible(true);

        $this->expectException(Exception::class);
        Log::shouldReceive('critical');
        $method->invokeArgs($job, [1]);
    }

    /**
     * Test fetchPayItems() failure (401 returned from mock endpoint)
     */
    public function test_fetchPayItems_401(): void
    {
        Http::fake(['*' => Http::response([], 401)]);

        $job = $this->setUpJob();
        $reflection = new ReflectionClass($job);
        $method = $reflection->getMethod('fetchPayItems');
        $method->setAccessible(true);

        $this->expectException(Exception::class);
        Log::shouldReceive('alert');
        $method->invokeArgs($job, [1]);
    }

    /**
     * Test pruneDeadPayItems() when no pay items were processed (delete all from DB)
     */
    public function test_pruneDeadPayItems_empty(): void
    {
        $job = $this->setUpJob();
        $reflection = new ReflectionClass($job);
        $reflection->getProperty('processedPayItems')->setValue($job, []);
        $method = $reflection->getMethod('pruneDeadPayItems');
        $method->setAccessible(true);

        $method->invokeArgs($job, []);
        $this->assertDatabaseEmpty('pay_items');
    }

    /**
     * Test pruneDeadPayItems() when 3 pay items were processed (should only be 3 in DB)
     */
    public function test_pruneDeadPayItems_success(): void
    {
        $processedPayItems = array_slice(DatabaseSeeder::TEST_PAY_ITEM_IDS, 0, 3);

        $job = $this->setUpJob();
        $reflection = new ReflectionClass($job);

        $reflection->getProperty('processedPayItems')->setValue($job, $processedPayItems);
        $method = $reflection->getMethod('pruneDeadPayItems');
        $method->setAccessible(true);

        $method->invokeArgs($job, []);
        $this->assertDatabaseCount('pay_items', 3);
    }

    /**
     * Set up SyncPayItems job for testing
     */
    private function setUpJob(): SyncPayItems
    {
        $this->seed();
        $this->business = Business::where('external_id', DatabaseSeeder::TEST_BUSINESS_ID)->first();

        return new SyncPayItems($this->business);
    }

    /**
     * Generate fake response data for mock endpoint
     */
    private function fakeResponseData(): array
    {
        $validItems = [
            "payItems" => [
                [
                    "id"=> "3a1e8caa-9716-3dc9-ba0b-022c45b16cfe",
                    "employeeId"=> "29fd42bd-5396-3d37-85c2-6fe286ee02ce",
                    "hoursWorked"=> 16,
                    "payRate"=> 81.7543,
                    "date"=> "2021-10-20"
                ],
                [
                    "id"=> "4923bdf7-97c4-36f4-80a4-fad3c61d32e0",
                    "employeeId"=> "fc22e067-02b8-32b0-a6d6-a6bfd9ff8a5f",
                    "hoursWorked"=> 2.45,
                    "payRate"=> 8.23,
                    "date"=> "2021-10-31"
                ],
                [
                    "id"=> fake()->uuid(),
                    "employeeId"=> "fc22e067-02b8-32b0-a6d6-a6bfd9ff8a5f",
                    "hoursWorked"=> 26,
                    "payRate"=> 21,
                    "date"=> "2021-10-20"
                ],
            ],
            "isLastPage" => true
        ];

        $invalidUsers = [
            "payItems" => [
                [
                    "id"=> "3a1e8caa-9716-3dc9-ba0b-022c45b16cfe",
                    "employeeId"=> "123",
                    "hoursWorked"=> 16,
                    "payRate"=> 81.7543,
                    "date"=> "2021-10-20"
                ],
                [
                    "id"=> "4923bdf7-97c4-36f4-80a4-fad3c61d32e0",
                    "employeeId"=> "456",
                    "hoursWorked"=> 2.45,
                    "payRate"=> 8.23,
                    "date"=> "2021-10-31"
                ],
                [
                    "id"=> fake()->uuid(),
                    "employeeId"=> "789",
                    "hoursWorked"=> 26,
                    "payRate"=> 21,
                    "date"=> "2021-10-20"
                ],
            ],
            "isLastPage" => true
        ];

        return [
            'validItems' => $validItems,
            'invalidUsers' => $invalidUsers,
        ];
    }
}
