<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Events\IssueCreated;
use App\Events\IssueUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Feature Tests for Issue API
 * 
 * Tests all CRUD operations, validation, filtering, and real-time broadcasting
 */
class IssueTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Setup method runs before each test
     * Prepare the test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Fake events to test broadcasting without actually broadcasting
        Event::fake();
    }

    /**
     * Test: Can retrieve paginated list of issues
     * 
     * @test
     */
    public function can_get_paginated_issues_list()
    {
        // Arrange: Create test issues
        Issue::factory()->count(25)->create();

        // Act: Make request to issues endpoint
        $response = $this->getJson('/api/v1/issues?page=1&per_page=10');

        // Assert: Check response structure and data
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'title', 
                            'description',
                            'status',
                            'priority',
                            'created_at',
                            'updated_at'
                        ]
                    ],
                    'meta' => [
                        'current_page',
                        'per_page',
                        'total',
                        'last_page'
                    ],
                    'links' => [
                        'first',
                        'last',
                        'prev',
                        'next'
                    ]
                ]);

        $responseData = $response->json();
        $this->assertEquals(10, count($responseData['data']));
        $this->assertEquals(25, $responseData['meta']['total']);
        $this->assertEquals(1, $responseData['meta']['current_page']);
    }

    /**
     * Test: Can filter issues by status
     * 
     * @test
     */
    public function can_filter_issues_by_status()
    {
        // Arrange: Create issues with different statuses
        Issue::factory()->count(5)->create(['status' => 'open']);
        Issue::factory()->count(3)->create(['status' => 'closed']);
        Issue::factory()->count(2)->create(['status' => 'in_progress']);

        // Act: Filter by open status
        $response = $this->getJson('/api/v1/issues?status=open');

        // Assert: Only open issues returned
        $response->assertStatus(200);
        $data = $response->json()['data'];
        
        $this->assertEquals(5, count($data));
        foreach ($data as $issue) {
            $this->assertEquals('open', $issue['status']);
        }
    }

    /**
     * Test: Can filter issues by priority
     * 
     * @test
     */
    public function can_filter_issues_by_priority()
    {
        // Arrange: Create issues with different priorities
        Issue::factory()->count(3)->create(['priority' => 'critical']);
        Issue::factory()->count(4)->create(['priority' => 'high']);
        Issue::factory()->count(2)->create(['priority' => 'medium']);

        // Act: Filter by critical priority
        $response = $this->getJson('/api/v1/issues?priority=critical');

        // Assert: Only critical issues returned
        $response->assertStatus(200);
        $data = $response->json()['data'];
        
        $this->assertEquals(3, count($data));
        foreach ($data as $issue) {
            $this->assertEquals('critical', $issue['priority']);
        }
    }

    /**
     * Test: Can search issues by title and description
     * 
     * @test
     */
    public function can_search_issues()
    {
        // Arrange: Create issues with specific content
        Issue::factory()->create([
            'title' => 'Database Bug Fix',
            'description' => 'Fix connection issues with MySQL'
        ]);
        Issue::factory()->create([
            'title' => 'UI Enhancement',
            'description' => 'Update button styles for better UX'
        ]);
        Issue::factory()->create([
            'title' => 'Performance Issue',
            'description' => 'Database queries are running slow'
        ]);

        // Act: Search for "database"
        $response = $this->getJson('/api/v1/issues?search=database');

        // Assert: Issues containing "database" are returned
        $response->assertStatus(200);
        $data = $response->json()['data'];
        
        $this->assertGreaterThanOrEqual(2, count($data));
        // Check that returned issues contain the search term
        $foundTerms = 0;
        foreach ($data as $issue) {
            if (stripos($issue['title'] . ' ' . $issue['description'], 'database') !== false) {
                $foundTerms++;
            }
        }
        $this->assertGreaterThan(0, $foundTerms);
    }

    /**
     * Test: Can create a new issue with valid data
     * 
     * @test
     */
    public function can_create_issue_with_valid_data()
    {
        // Arrange: Prepare valid issue data
        $issueData = [
            'title' => 'New Test Issue',
            'description' => 'This is a test issue with sufficient description length',
            'status' => 'open',
            'priority' => 'medium'
        ];

        // Act: Create issue via API
        $response = $this->postJson('/api/v1/issues', $issueData);

        // Assert: Issue created successfully
        $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'title',
                        'description', 
                        'status',
                        'priority',
                        'created_at',
                        'updated_at'
                    ],
                    'message'
                ])
                ->assertJson([
                    'data' => $issueData,
                    'message' => 'Issue created successfully'
                ]);

        // Assert: Issue exists in database
        $this->assertDatabaseHas('issues', $issueData);

        // Assert: Event was fired for real-time updates
        Event::assertDispatched(IssueCreated::class, function ($event) use ($issueData) {
            return $event->issue->title === $issueData['title'];
        });
    }

    /**
     * Test: Cannot create issue with invalid data
     * 
     * @test
     */
    public function cannot_create_issue_with_invalid_data()
    {
        // Test cases with invalid data
        $invalidDataSets = [
            // Missing required fields
            ['title' => '', 'description' => '', 'status' => '', 'priority' => ''],
            // Invalid enum values
            ['title' => 'Test', 'description' => 'Test desc', 'status' => 'invalid', 'priority' => 'invalid'],
            // Title too short
            ['title' => 'Hi', 'description' => 'Valid description', 'status' => 'open', 'priority' => 'medium'],
            // Description too short
            ['title' => 'Valid Title', 'description' => 'Short', 'status' => 'open', 'priority' => 'medium']
        ];

        foreach ($invalidDataSets as $invalidData) {
            // Act: Attempt to create issue with invalid data
            $response = $this->postJson('/api/v1/issues', $invalidData);

            // Assert: Validation fails
            $response->assertStatus(422)
                    ->assertJsonStructure([
                        'message',
                        'errors'
                    ]);
        }

        // Assert: No issues were created
        $this->assertEquals(0, Issue::count());

        // Assert: No events were fired
        Event::assertNotDispatched(IssueCreated::class);
    }

    /**
     * Test: Can retrieve a specific issue
     * 
     * @test
     */
    public function can_get_specific_issue()
    {
        // Arrange: Create a test issue
        $issue = Issue::factory()->create();

        // Act: Retrieve the issue
        $response = $this->getJson("/api/v1/issues/{$issue->id}");

        // Assert: Issue data returned correctly
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'title',
                        'description',
                        'status', 
                        'priority',
                        'created_at',
                        'updated_at'
                    ]
                ])
                ->assertJson([
                    'data' => [
                        'id' => $issue->id,
                        'title' => $issue->title,
                        'description' => $issue->description,
                        'status' => $issue->status,
                        'priority' => $issue->priority
                    ]
                ]);
    }

    /**
     * Test: Returns 404 for non-existent issue
     * 
     * @test
     */
    public function returns_404_for_non_existent_issue()
    {
        // Act: Try to get non-existent issue
        $response = $this->getJson('/api/v1/issues/999999');

        // Assert: 404 response
        $response->assertStatus(404);
    }

    /**
     * Test: Can update an existing issue
     * 
     * @test
     */
    public function can_update_existing_issue()
    {
        // Arrange: Create a test issue
        $issue = Issue::factory()->create([
            'status' => 'open',
            'priority' => 'medium'
        ]);

        $updateData = [
            'status' => 'in_progress',
            'priority' => 'high'
        ];

        // Act: Update the issue
        $response = $this->patchJson("/api/v1/issues/{$issue->id}", $updateData);

        // Assert: Issue updated successfully
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'title',
                        'description',
                        'status',
                        'priority',
                        'created_at', 
                        'updated_at'
                    ],
                    'message',
                    'changes'
                ])
                ->assertJson([
                    'data' => array_merge($issue->toArray(), $updateData),
                    'message' => 'Issue updated successfully'
                ]);

        // Assert: Changes tracked correctly
        $changes = $response->json()['changes'];
        $this->assertArrayHasKey('status', $changes);
        $this->assertArrayHasKey('priority', $changes);
        $this->assertEquals('open', $changes['status']['old']);
        $this->assertEquals('in_progress', $changes['status']['new']);

        // Assert: Database updated
        $this->assertDatabaseHas('issues', array_merge(['id' => $issue->id], $updateData));

        // Assert: Event was fired
        Event::assertDispatched(IssueUpdated::class, function ($event) use ($issue) {
            return $event->issue->id === $issue->id;
        });
    }

    /**
     * Test: Cannot update issue with invalid data
     * 
     * @test
     */
    public function cannot_update_issue_with_invalid_data()
    {
        // Arrange: Create a test issue
        $issue = Issue::factory()->create();

        $invalidData = [
            'status' => 'invalid_status',
            'priority' => 'invalid_priority'
        ];

        // Act: Attempt to update with invalid data
        $response = $this->patchJson("/api/v1/issues/{$issue->id}", $invalidData);

        // Assert: Validation fails
        $response->assertStatus(422)
                ->assertJsonStructure([
                    'message',
                    'errors'
                ]);

        // Assert: No events fired
        Event::assertNotDispatched(IssueUpdated::class);
    }

    /**
     * Test: Can delete an issue
     * 
     * @test
     */
    public function can_delete_issue()
    {
        // Arrange: Create a test issue
        $issue = Issue::factory()->create();

        // Act: Delete the issue
        $response = $this->deleteJson("/api/v1/issues/{$issue->id}");

        // Assert: Issue deleted successfully
        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Issue deleted successfully'
                ]);

        // Assert: Issue removed from database
        $this->assertDatabaseMissing('issues', ['id' => $issue->id]);
    }

    /**
     * Test: Can get issue statistics
     * 
     * @test
     */
    public function can_get_issue_statistics()
    {
        // Arrange: Create issues with different statuses and priorities
        Issue::factory()->count(3)->create(['status' => 'open', 'priority' => 'high']);
        Issue::factory()->count(2)->create(['status' => 'closed', 'priority' => 'medium']);
        Issue::factory()->count(1)->create(['status' => 'in_progress', 'priority' => 'critical']);

        // Act: Get statistics
        $response = $this->getJson('/api/v1/issues-stats');

        // Assert: Statistics returned correctly
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'total',
                        'by_status' => [
                            'open',
                            'in_progress',
                            'closed'
                        ],
                        'by_priority' => [
                            'low',
                            'medium', 
                            'high',
                            'critical'
                        ],
                        'recent'
                    ]
                ]);

        $stats = $response->json()['data'];
        $this->assertEquals(6, $stats['total']);
        $this->assertEquals(3, $stats['by_status']['open']);
        $this->assertEquals(2, $stats['by_status']['closed']);
        $this->assertEquals(1, $stats['by_status']['in_progress']);
    }

    /**
     * Test: API health check endpoint works
     * 
     * @test
     */
    public function health_check_endpoint_works()
    {
        // Act: Check API health
        $response = $this->getJson('/api/v1/health');

        // Assert: Health check passes
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'timestamp',
                    'version'
                ])
                ->assertJson([
                    'status' => 'ok'
                ]);
    }

    /**
     * Test: API returns proper 404 for invalid endpoints
     * 
     * @test
     */
    public function returns_404_for_invalid_endpoints()
    {
        // Act: Try invalid endpoint
        $response = $this->getJson('/api/v1/invalid-endpoint');

        // Assert: 404 with helpful message
        $response->assertStatus(404)
                ->assertJsonStructure([
                    'message',
                    'available_endpoints'
                ]);
    }
}