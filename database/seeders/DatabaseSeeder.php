<?php

namespace Database\Seeders;
use Faker\Factory as Faker;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
		
		        $faker = Faker::create();
     
        foreach (range(1, 10) as $index) {
            DB::table('products')->insert([
                'sku' => $faker->unique()->md5,
                'Name' => $faker->word,
                'price' =>  $faker->numberBetween($min = 1, $max = 1000),
                'description' =>  $faker->text,
                'Category' =>  $faker->word,
                'UnitsInStock' =>  $faker->numberBetween($min = 1, $max = 50),
            ]);
        }
    }
}
