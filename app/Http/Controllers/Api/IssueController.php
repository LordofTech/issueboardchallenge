<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Issue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Events\IssueCreated;
use App\Events\IssueUpdated;

class IssueController extends Controller
{
    /**
     * Display a listing of issues with filtering and pagination
     */
    public function index(Request $request)
    {
        $startTime = microtime(true);
        
        try {
            $query = Issue::query();

            // Filter by status
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Filter by priority
            if ($request->filled('priority')) {
                $query->where('priority', $request->priority);
            }

            // Search in title and description
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', '%' . $search . '%')
                      ->orWhere('description', 'like', '%' . $search . '%');
                });
            }

            // Get paginated results
            $perPage = min($request->get('per_page', 10), 50); // Max 50 items per page
            $issues = $query->orderBy('updated_at', 'desc')
                           ->orderBy('created_at', 'desc')
                           ->paginate($perPage);

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::info('Issues list requested', [
                'method' => 'GET',
                'path' => '/api/issues',
                'response_code' => 200,
                'duration_ms' => $duration,
                'filters' => $request->only(['status', 'priority', 'search']),
                'total_results' => $issues->total()
            ]);

            return response()->json($issues);
            
        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::error('Error loading issues', [
                'method' => 'GET',
                'path' => '/api/issues',
                'response_code' => 500,
                'duration_ms' => $duration,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Error loading issues',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Store a newly created issue
     */
    public function store(Request $request)
    {
        $startTime = microtime(true);
        
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255|min:3',
                'description' => 'required|string|min:10',
                'status' => 'sometimes|in:open,in_progress,resolved,closed',
                'priority' => 'required|in:low,medium,high,critical',
            ]);

            // Set default status if not provided
            $validated['status'] = $validated['status'] ?? 'open';

            $issue = Issue::create($validated);

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::info('Issue created successfully', [
                'method' => 'POST',
                'path' => '/api/issues',
                'response_code' => 201,
                'duration_ms' => $duration,
                'issue_id' => $issue->id,
                'issue_data' => $issue->toArray()
            ]);

            // Broadcast the event
            try {
                broadcast(new IssueCreated($issue))->toOthers();
                Log::info('Issue created event broadcasted', ['issue_id' => $issue->id]);
            } catch (\Exception $e) {
                Log::warning('Failed to broadcast issue created event', [
                    'issue_id' => $issue->id,
                    'error' => $e->getMessage()
                ]);
            }

            return response()->json([
                'message' => 'Issue created successfully',
                'data' => $issue
            ], 201);
            
        } catch (ValidationException $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::warning('Issue creation validation failed', [
                'method' => 'POST',
                'path' => '/api/issues',
                'response_code' => 422,
                'duration_ms' => $duration,
                'validation_errors' => $e->errors(),
                'input' => $request->all()
            ]);

            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::error('Error creating issue', [
                'method' => 'POST',
                'path' => '/api/issues',
                'response_code' => 500,
                'duration_ms' => $duration,
                'error' => $e->getMessage(),
                'input' => $request->all()
            ]);

            return response()->json([
                'message' => 'Error creating issue',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update the specified issue
     */
    public function update(Request $request, Issue $issue)
    {
        $startTime = microtime(true);
        
        try {
            $validated = $request->validate([
                'title' => 'sometimes|string|max:255|min:3',
                'description' => 'sometimes|string|min:10',
                'status' => 'sometimes|in:open,in_progress,resolved,closed',
                'priority' => 'sometimes|in:low,medium,high,critical',
            ]);

            // Store original data for logging
            $originalData = $issue->toArray();
            
            $issue->update($validated);

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::info('Issue updated successfully', [
                'method' => 'PATCH',
                'path' => '/api/issues/' . $issue->id,
                'response_code' => 200,
                'duration_ms' => $duration,
                'issue_id' => $issue->id,
                'original_data' => $originalData,
                'updated_data' => $issue->fresh()->toArray(),
                'changes' => $validated
            ]);

            // Broadcast the event
            try {
                broadcast(new IssueUpdated($issue))->toOthers();
                Log::info('Issue updated event broadcasted', ['issue_id' => $issue->id]);
            } catch (\Exception $e) {
                Log::warning('Failed to broadcast issue updated event', [
                    'issue_id' => $issue->id,
                    'error' => $e->getMessage()
                ]);
            }

            return response()->json([
                'message' => 'Issue updated successfully',
                'data' => $issue->fresh()
            ]);
            
        } catch (ValidationException $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::warning('Issue update validation failed', [
                'method' => 'PATCH',
                'path' => '/api/issues/' . $issue->id,
                'response_code' => 422,
                'duration_ms' => $duration,
                'validation_errors' => $e->errors(),
                'input' => $request->all()
            ]);

            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::error('Error updating issue', [
                'method' => 'PATCH',
                'path' => '/api/issues/' . $issue->id,
                'response_code' => 500,
                'duration_ms' => $duration,
                'error' => $e->getMessage(),
                'input' => $request->all()
            ]);

            return response()->json([
                'message' => 'Error updating issue',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}