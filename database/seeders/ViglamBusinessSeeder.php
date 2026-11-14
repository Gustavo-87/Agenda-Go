<?php

namespace Database\Seeders;

use App\Models\Business;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ViglamBusinessSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Identify Viglam as the first business and link every unassigned
     * record to it. The links are written through the query builder so
     * the existing data and timestamps of those records stay untouched.
     */
    public function run(): void
    {
        $business = Business::firstOrCreate(
            ['slug' => 'viglam'],
            ['name' => 'Viglam', 'active' => true],
        );

        DB::table('professionals')->whereNull('business_id')->update(['business_id' => $business->id]);
        DB::table('services')->whereNull('business_id')->update(['business_id' => $business->id]);
        DB::table('appointments')->whereNull('business_id')->update(['business_id' => $business->id]);
    }
}
