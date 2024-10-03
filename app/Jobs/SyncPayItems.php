<?php

namespace App\Jobs;

use App\Models\Business;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class SyncPayItems implements ShouldQueue
{
    use Queueable;

    protected $processedPayItems = [];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Business $business,
    )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // TODO uncomment if you want to test the job
        // $validItems = [
        //     "payItems" => [
        //         [
        //             "id"=> "3a1e8caa-9716-3dc9-ba0b-022c45b16cfe",
        //             "employeeId"=> "29fd42bd-5396-3d37-85c2-6fe286ee02ce",
        //             "hoursWorked"=> 16,
        //             "payRate"=> 81.7543,
        //             "date"=> "2021-10-20"
        //         ],
        //         [
        //             "id"=> "4923bdf7-97c4-36f4-80a4-fad3c61d32e0",
        //             "employeeId"=> "fc22e067-02b8-32b0-a6d6-a6bfd9ff8a5f",
        //             "hoursWorked"=> 2.45,
        //             "payRate"=> 8.23,
        //             "date"=> "2021-10-31"
        //         ],
        //         [
        //             "id"=> fake()->uuid(),
        //             "employeeId"=> "fc22e067-02b8-32b0-a6d6-a6bfd9ff8a5f",
        //             "hoursWorked"=> 26,
        //             "payRate"=> 21,
        //             "date"=> "2021-10-20"
        //         ],
        //     ],
        //     "isLastPage" => true
        // ];
        // Http::fake(['*' => Http::response($validItems, 200)]);

        DB::beginTransaction();
        try {
            do {
                $page = 1;
                $response = $this->fetchPayItems($page)->json();
                $this->updatePayItems($response['payItems']);
                $page++;
            } while (!$response['isLastPage']);

            $this->pruneDeadPayItems();

            DB::commit();
        } catch (\Throwable $t) {
            DB::rollBack();
            Log::critical($t);
            $this->fail($t);
        }
    }

    /**
     * Fetch pay items by page from endpoint
     */
    private function fetchPayItems(int $page): Response
    {
        $response = Http::withHeaders(['x-api-key' => 'CLAIR-ABC-123'])
        ->get("https://some-partner-website.com/clair-pay-item-sync/{$this->business->external_id}", ['page' => $page]);

        $status = $response->status();
        if ($status !== 200) {
            if ($status === 401) {
                Log::alert("Job for {$this->business->name} failed: Unauthorized");
            } elseif ($status === 404) {
                Log::critical("Job for {$this->business->name} failed: Business does not exist for external ID {$this->business->external_id}");
            }

            throw new Exception('Job failed');
        }

        return $response;
    }

    /**
     * Create/Update pay items
     */
    private function updatePayItems(array $payItems): void
    {
        $users = $this->getUsers();
        foreach ($payItems as $payItem) {
            [
                'id' => $itemId,
                'employeeId' => $userId,
                'hoursWorked' => $hours,
                'payRate' => $rate,
                'date' => $date
            ] = $payItem;

            if (!isset($users[$userId])) {
                continue;
            }

            $deductionPercentage = ($this->business->deduction_percentage ?? 30)/ 100;
            $amount = round($hours * $rate * $deductionPercentage, 2);

            $this->business->payItems()->updateOrCreate(
                [
                    'external_id' => $itemId,
                    'business_id' => $this->business->external_id,
                    'user_id' => $userId
                ],
                [
                    'amount' => $amount,
                    'hours' => $hours,
                    'rate' => $rate,
                    'item_date' => $date
                ]
            );

            $this->processedPayItems[$itemId] = $itemId;
        }
    }

    /**
     * Remove pay items that aren't present from endpoint
     */
    private function pruneDeadPayItems(): void
    {
        DB::table('pay_items')
        ->where('business_id', '=', $this->business->external_id)
        ->whereNotIn('external_id', array_values($this->processedPayItems))
        ->delete();
    }

    /**
     * Get users for business
     */
    private function getUsers(): array
    {
        static $users = null;

        if (is_null($users)) {
            $users = [];
            foreach ($this->business->users as $userModel) {
                $users[$userModel->pivot->user_external_id] = $userModel;
            }
        }

        return $users;
    }
}
