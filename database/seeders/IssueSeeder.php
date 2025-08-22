<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Issue;

class IssueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder will create 10 dummy issues using the Issue factory.
     */
    public function run()
    {
        // Create 10 issues with random titles, descriptions, status, and priority
        Issue::factory()->count(10)->create();
    }
}
