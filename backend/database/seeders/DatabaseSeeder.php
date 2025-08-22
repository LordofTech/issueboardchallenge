<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Main Database Seeder
 * 
 * Coordinates all database seeding operations
 * Run with: php artisan db:seed
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * 
     * This method runs all necessary seeders in the correct order
     */
    public function run(): void
    {
        // Output start message
        $this->command->info('Starting database seeding...');
        
        // Seed issues table with sample data
        $this->call([
            IssuesTableSeeder::class, //  Correct seeder name
        ]);

        // Add more seeders here as the application grows
        // Example:
        // $this->call([
        //     UserSeeder::class,
        //     IssuesTableSeeder::class,
        //     CommentSeeder::class,
        // ]);

        // Output completion message
        $this->command->info('Database seeding completed successfully!');
        $this->command->info('You can now start the application servers.');
        $this->command->newLine();
        $this->command->info('Next steps:');
        $this->command->info('1. Start Laravel: php artisan serve');
        $this->command->info('2. Start Reverb: php artisan reverb:start');
        $this->command->info('3. Start Queue: php artisan queue:work');
        $this->command->info('4. Start Frontend: npm start (in frontend directory)');
    }
}
