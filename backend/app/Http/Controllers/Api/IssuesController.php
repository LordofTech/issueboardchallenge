<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class IssuesController extends Controller
{
    /**
     * Display a listing of the issues.
     */
    public function index()
    {
        return response()->json([
            'message' => 'List of issues',
            'data' => [],
        ]);
    }

    /**
     * Store a newly created issue.
     */
    public function store(Request $request)
    {
        return response()->json([
            'message' => 'Issue created successfully',
            'data' => $request->all(),
        ]);
    }

    /**
     * Display the specified issue.
     */
    public function show($id)
    {
        return response()->json([
            'message' => "Showing issue with ID: {$id}",
            'data' => [],
        ]);
    }

    /**
     * Update the specified issue.
     */
    public function update(Request $request, $id)
    {
        return response()->json([
            'message' => "Issue with ID: {$id} updated successfully",
            'data' => $request->all(),
        ]);
    }

    /**
     * Remove the specified issue.
     */
    public function destroy($id)
    {
        return response()->json([
            'message' => "Issue with ID: {$id} deleted successfully",
        ]);
    }
}
