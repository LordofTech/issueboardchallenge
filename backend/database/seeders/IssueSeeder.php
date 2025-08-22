<?php

namespace Database\Seeders;

use App\Models\Issue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Issue Seeder
 * 
 * Seeds the database with sample issues for testing and demonstration
 * Creates a variety of issues with different statuses and priorities
 */
class IssueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates sample issues with realistic data for testing
     */
    public function run(): void
    {
        // Clear existing data
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Issue::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Sample issue data with realistic scenarios
        $issues = [
            // Critical Issues (High Priority)
            [
                'title' => 'Database Connection Failing',
                'description' => 'The main database connection is intermittently failing, causing users to experience login issues and data loss. This affects approximately 30% of user sessions. Error logs show connection timeout issues with the MySQL server. Immediate attention required to prevent further service disruption.',
                'status' => 'open',
                'priority' => 'critical',
                'created_at' => now()->subHours(2),
                'updated_at' => now()->subHours(2),
            ],
            [
                'title' => 'Payment Processing Down',
                'description' => 'Payment gateway is returning error 500 for all transactions. No payments have been processed in the last 4 hours. Customer support is receiving multiple complaints. Revenue impact is significant. Payment provider API seems to be responding but our integration is failing.',
                'status' => 'in_progress',
                'priority' => 'critical',
                'created_at' => now()->subHours(4),
                'updated_at' => now()->subHour(),
            ],

            // High Priority Issues
            [
                'title' => 'User Registration Email Not Sending',
                'description' => 'New users are not receiving confirmation emails after registration. SMTP server appears to be working but emails are not being queued or sent. This prevents users from activating their accounts and completing the onboarding process.',
                'status' => 'open',
                'priority' => 'high',
                'created_at' => now()->subHours(6),
                'updated_at' => now()->subHours(6),
            ],
            [
                'title' => 'Search Results Page Loading Slowly',
                'description' => 'Search functionality is taking 15-20 seconds to return results, which is significantly slower than the expected 2-3 seconds. Database queries appear to be inefficient. Users are abandoning searches due to poor performance.',
                'status' => 'in_progress',
                'priority' => 'high',
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subHours(3),
            ],
            [
                'title' => 'Mobile App Crashes on iOS 17',
                'description' => 'Users on iOS 17 are experiencing frequent crashes when navigating between screens. Crash logs indicate memory management issues. This affects approximately 25% of our mobile user base.',
                'status' => 'closed',
                'priority' => 'high',
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(1),
            ],

            // Medium Priority Issues
            [
                'title' => 'Profile Image Upload Bug',
                'description' => 'Users cannot upload profile images larger than 2MB even though the limit should be 5MB. Error message is not user-friendly and says "Unknown error occurred". Need to fix file size validation and improve error messaging.',
                'status' => 'open',
                'priority' => 'medium',
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ],
            [
                'title' => 'Notification Preferences Not Saving',
                'description' => 'When users update their notification preferences, changes are not being saved to the database. The form submission appears successful but settings revert to defaults on page refresh.',
                'status' => 'in_progress',
                'priority' => 'medium',
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subHours(5),
            ],
            [
                'title' => 'Dashboard Charts Not Responsive',
                'description' => 'Dashboard charts are not adapting properly to smaller screen sizes. On mobile devices and tablets, charts are cut off and difficult to read. Need to implement responsive design for better user experience.',
                'status' => 'open',
                'priority' => 'medium',
                'created_at' => now()->subDays(4),
                'updated_at' => now()->subDays(4),
            ],
            [
                'title' => 'Export Feature Missing Date Filter',
                'description' => 'The data export functionality does not allow users to filter by date range. Users want to export data for specific time periods but currently can only export all data, which creates very large files.',
                'status' => 'closed',
                'priority' => 'medium',
                'created_at' => now()->subWeek(),
                'updated_at' => now()->subDays(2),
            ],

            // Low Priority Issues
            [
                'title' => 'Footer Links Need Updating',
                'description' => 'Some links in the footer are outdated and point to old pages. Privacy policy and terms of service links need to be updated to reflect current versions. Also, the help center link is broken.',
                'status' => 'open',
                'priority' => 'low',
                'created_at' => now()->subWeek(),
                'updated_at' => now()->subWeek(),
            ],
            [
                'title' => 'Improve Button Hover Effects',
                'description' => 'Button hover effects could be more polished. Current transitions are too abrupt and could benefit from smoother animations. This would improve overall user interface experience.',
                'status' => 'open',
                'priority' => 'low',
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(5),
            ],
            [
                'title' => 'Add Dark Mode Toggle',
                'description' => 'Users have requested a dark mode option for better viewing experience during evening hours. This would be a nice-to-have feature that could improve user satisfaction and accessibility.',
                'status' => 'in_progress',
                'priority' => 'low',
                'created_at' => now()->subDays(10),
                'updated_at' => now()->subDays(3),
            ],
            [
                'title' => 'Optimize CSS File Size',
                'description' => 'CSS bundle is larger than necessary and could be optimized by removing unused styles and implementing better compression. This would improve page load times slightly.',
                'status' => 'closed',
                'priority' => 'low',
                'created_at' => now()->subWeeks(2),
                'updated_at' => now()->subWeek(),
            ],

            // Additional varied issues for better testing
            [
                'title' => 'API Rate Limiting Too Aggressive',
                'description' => 'Current API rate limiting is blocking legitimate requests from power users. Need to implement more sophisticated rate limiting that accounts for user tiers and request patterns.',
                'status' => 'open',
                'priority' => 'medium',
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(3),
            ],
            [
                'title' => 'Security Vulnerability in User Input',
                'description' => 'Potential XSS vulnerability discovered in comment fields. User input is not properly sanitized before display, which could allow malicious scripts to be executed. This needs immediate security review and patching.',
                'status' => 'in_progress',
                'priority' => 'critical',
                'created_at' => now()->subHours(8),
                'updated_at' => now()->subHours(1),
            ],
            [
                'title' => 'Inconsistent Date Formatting',
                'description' => 'Date formats are inconsistent across different pages of the application. Some use MM/DD/YYYY while others use DD/MM/YYYY. Need to standardize date formatting for better user experience.',
                'status' => 'open',
                'priority' => 'low',
                'created_at' => now()->subDays(6),
                'updated_at' => now()->subDays(6),
            ],
        ];

        // Insert all issues
        foreach ($issues as $issueData) {
            Issue::create($issueData);
        }

        // Output success message
        $this->command->info('✅ Created ' . count($issues) . ' sample issues');
        
        // Show summary of created issues
        $statusCounts = Issue::selectRaw('status, COUNT(*) as count')
                           ->groupBy('status')
                           ->pluck('count', 'status')
                           ->toArray();

        $priorityCounts = Issue::selectRaw('priority, COUNT(*) as count')
                             ->groupBy('priority') 
                             ->pluck('count', 'priority')
                             ->toArray();

        $this->command->info('📊 Issues by status:');
        foreach ($statusCounts as $status => $count) {
            $this->command->info("   {$status}: {$count}");
        }

        $this->command->info('📊 Issues by priority:');
        foreach ($priorityCounts as $priority => $count) {
            $this->command->info("   {$priority}: {$count}");
        }
    }
}