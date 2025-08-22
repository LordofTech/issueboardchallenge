<?php

namespace Database\Factories;

use App\Models\Issue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Issue Factory
 * 
 * Generates fake issue data for testing and seeding
 * Provides realistic test data with varied scenarios
 */
class IssueFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Issue::class;

    /**
     * Define the model's default state.
     * Creates realistic issue data for testing
     * 
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Common issue types and problems for realistic data
        $issueTitles = [
            'Database connection timeout',
            'User login fails intermittently', 
            'Email notifications not sending',
            'Page loading performance issues',
            'Mobile app crashes on startup',
            'Search functionality returns no results',
            'File upload fails for large files',
            'Dashboard widgets not loading',
            'API endpoint returning 500 errors',
            'User profile data not saving',
            'Payment processing errors',
            'Session timeout too aggressive',
            'CSS styles not loading properly',
            'Form validation not working',
            'Image thumbnails not generating',
            'Export feature produces corrupt files',
            'Notification preferences reset randomly',
            'Password reset emails delayed',
            'Chart data displays incorrectly',
            'Third-party integration failing'
        ];

        $descriptionTemplates = [
            'Users are experiencing {issue} when attempting to {action}. This occurs approximately {frequency} and affects {impact}. Error logs show {error_details}. {additional_context}',
            'The {component} is not functioning correctly, causing {problem}. This has been reported by {reporters} and needs {urgency}. {technical_details}',
            'Performance issue with {feature} - response times are {timing} instead of expected {expected_timing}. This impacts {user_impact} and requires {solution_type}.',
            'Integration with {service} is failing with {error_type}. This prevents {functionality} from working properly. {business_impact}',
            'UI component {element} is displaying {display_issue} on {devices}. Users are unable to {user_action} which affects {workflow}.'
        ];

        // Pick a random title
        $title = $this->faker->randomElement($issueTitles);

        // Generate contextual description based on title
        $description = $this->generateContextualDescription($title);

        return [
            'title' => $title,
            'description' => $description,
            'status' => $this->faker->randomElement(Issue::STATUSES),
            'priority' => $this->faker->randomElement(Issue::PRIORITIES),
            'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'updated_at' => function (array $attributes) {
                // Updated_at should be same as or after created_at
                return $this->faker->dateTimeBetween($attributes['created_at'], 'now');
            },
        ];
    }

    /**
     * Generate a contextual description based on the issue title
     * 
     * @param string $title
     * @return string
     */
    private function generateContextualDescription(string $title): string
    {
        $descriptions = [
            'Database connection timeout' => 'Database connections are timing out after {time} seconds, causing users to see error pages. This affects approximately {percentage}% of requests during peak hours. Database monitoring shows high CPU usage and slow query performance. Need to investigate connection pooling and query optimization.',
            
            'User login fails intermittently' => 'User authentication is failing sporadically, with users reporting they cannot log in despite correct credentials. Session management appears to be the issue, with tokens expiring unexpectedly. This affects user retention and customer satisfaction.',
            
            'Email notifications not sending' => 'Email delivery system is not processing outbound messages. SMTP server connectivity appears normal, but emails are stuck in the queue. This prevents users from receiving important notifications about their account activity and system updates.',
            
            'Page loading performance issues' => 'Several pages are loading significantly slower than baseline performance metrics. Average load time has increased from {baseline}ms to {current}ms. This impacts user experience and potentially SEO rankings. Need to analyze database queries and caching strategies.',
            
            'Mobile app crashes on startup' => 'Mobile application crashes immediately upon launch for users on {platform} version {version} and above. Crash reports indicate memory allocation issues during initialization. This prevents users from accessing core functionality on mobile devices.',
            
            'Search functionality returns no results' => 'Search queries are returning empty result sets even when matching data exists in the database. Search indexing may be corrupted or out of sync. This severely impacts user ability to find relevant content and reduces platform usability.',
        ];

        // If we have a specific description for this title, use it with placeholder replacement
        if (isset($descriptions[$title])) {
            $description = $descriptions[$title];
            
            // Replace placeholders with random values
            $replacements = [
                '{time}' => $this->faker->numberBetween(5, 30),
                '{percentage}' => $this->faker->numberBetween(10, 80),
                '{baseline}' => $this->faker->numberBetween(200, 500),
                '{current}' => $this->faker->numberBetween(1000, 5000),
                '{platform}' => $this->faker->randomElement(['iOS', 'Android']),
                '{version}' => $this->faker->randomElement(['15.0', '16.0', '17.0', '13.0', '14.0']),
            ];
            
            foreach ($replacements as $placeholder => $value) {
                $description = str_replace($placeholder, $value, $description);
            }
            
            return $description;
        }

        // Generic description generation for other titles
        $templates = [
            'Users are experiencing issues with {feature} functionality. This occurs {frequency} and affects user workflow. Investigation needed to identify root cause and implement fix.',
            
            'The {component} component is not working as expected. Users report {problem} when trying to {action}. This impacts {percentage}% of users and requires immediate attention.',
            
            'Performance degradation observed in {area}. Response times have increased beyond acceptable thresholds. This affects user experience and may require infrastructure optimization.',
            
            'Integration failure with external service causing {impact}. Error rates have increased significantly over the past {timeframe}. Need to review API connectivity and error handling.',
        ];

        $template = $this->faker->randomElement($templates);
        
        // Replace template variables with contextual values
        $variables = [
            '{feature}' => strtolower(str_replace([' functionality', ' issues', ' errors'], '', $title)),
            '{component}' => $this->faker->randomElement(['authentication', 'dashboard', 'navigation', 'form', 'search']),
            '{problem}' => $this->faker->randomElement(['errors', 'slow loading', 'unresponsive behavior', 'incorrect data']),
            '{action}' => $this->faker->randomElement(['save data', 'submit forms', 'navigate pages', 'load content']),
            '{percentage}' => $this->faker->numberBetween(15, 85),
            '{area}' => $this->faker->randomElement(['page loading', 'data processing', 'API responses', 'database queries']),
            '{impact}' => $this->faker->randomElement(['data sync failures', 'user authentication problems', 'notification delivery issues']),
            '{timeframe}' => $this->faker->randomElement(['24 hours', '48 hours', '3 days', 'past week']),
            '{frequency}' => $this->faker->randomElement(['frequently', 'occasionally', 'during peak hours', 'randomly']),
        ];

        foreach ($variables as $placeholder => $value) {
            $template = str_replace($placeholder, $value, $template);
        }

        return $template;
    }

    /**
     * Create an issue with 'open' status
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'open',
        ]);
    }

    /**
     * Create an issue with 'in_progress' status
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
        ]);
    }

    /**
     * Create an issue with 'closed' status
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'closed',
        ]);
    }

    /**
     * Create an issue with 'critical' priority
     */
    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'critical',
        ]);
    }

    /**
     * Create an issue with 'high' priority
     */
    public function high(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'high',
        ]);
    }

    /**
     * Create an issue with 'medium' priority
     */
    public function medium(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'medium',
        ]);
    }

    /**
     * Create an issue with 'low' priority
     */
    public function low(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'low',
        ]);
    }

    /**
     * Create a recent issue (within last 7 days)
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
            'updated_at' => function (array $attributes) {
                return $this->faker->dateTimeBetween($attributes['created_at'], 'now');
            },
        ]);
    }

    /**
     * Create an old issue (older than 30 days)
     */
    public function old(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $this->faker->dateTimeBetween('-90 days', '-30 days'),
            'updated_at' => function (array $attributes) {
                return $this->faker->dateTimeBetween($attributes['created_at'], '-30 days');
            },
        ]);
    }
}