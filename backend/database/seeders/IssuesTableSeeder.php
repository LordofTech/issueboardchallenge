<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class IssuesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = ['open', 'in_progress', 'closed'];
        $priorities = ['low', 'medium', 'high'];

        for ($i = 1; $i <= 20; $i++) {
            DB::table('issues')->insert([
                'title'       => 'Issue ' . $i,
                'description' => 'This is a description for issue ' . $i,
                'status'      => $statuses[array_rand($statuses)],
                'priority'    => $priorities[array_rand($priorities)],
                'created_at'  => Carbon::now(),
                'updated_at'  => Carbon::now(),
            ]);
        }
    }
}
