<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\PayItem;


use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{

    /**
     * Constants for feature tests
     */
    const TEST_BUSINESS_ID = 'a85f79a6-886a-3b57-9478-55d8737e60b8';
    const TEST_USER_IDS = [
        1 => '29fd42bd-5396-3d37-85c2-6fe286ee02ce',
        2 => 'fc22e067-02b8-32b0-a6d6-a6bfd9ff8a5f',
        3 => 'ef68d1a0-f044-3a42-b9f1-ab33c0ca73cb'
    ];
    const TEST_PAY_ITEM_IDS = [
        '28efc04d-7d00-3e8f-bbea-3eb4db313931',
        '0db1e540-d52e-36df-8ce5-4722de840365',
        '00facc03-1c2d-38cc-b344-230b0cd6f3ff',
        'b822a070-4696-3e2b-b08b-dcde9b0d0175',
        '61416073-95f3-362e-b168-715115211901',
        'f9cc5640-67f8-3f7f-8dde-ff7315af7c34',
    ];


    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create 10 random users
        User::factory(10)->create();

        // Create a specific business for testing
        Business::factory(1)->create(['external_id' => self::TEST_BUSINESS_ID, 'enabled' => true]);

        // Create other random businesses
        Business::factory(6)->create(['enabled' => true]);
        Business::factory(3)->create(['enabled' => false]);

        // Link 5 of the random users to the testing business
        foreach (self::TEST_USER_IDS as $userId => $userExternalID) {
            BusinessUser::factory()->create([
                'business_id' => self::TEST_BUSINESS_ID,
                'user_id' => $userId,
                'user_external_id' => $userExternalID
            ]);
        }

        // Create 5 pay items with specific external IDs
        foreach (self::TEST_PAY_ITEM_IDS as $payItemId) {
            PayItem::factory()->create([
                'user_id' => fake()->randomElement(self::TEST_USER_IDS),
                'business_id' => self::TEST_BUSINESS_ID,
                'external_id' => $payItemId
            ]);
        }

        // Create 15 random pay items
        for ($i = 0; $i < 14; $i++) {
            PayItem::factory()->create([
                'user_id' => fake()->randomElement(self::TEST_USER_IDS),
                'business_id' => self::TEST_BUSINESS_ID
            ]);
        }
    }
}
