<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to create the issues table
 * This table stores all issue records with proper indexing for performance
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates the issues table with all necessary fields and indexes
     */
    public function up(): void
    {
        Schema::create('issues', function (Blueprint $table) {
            // Primary key - using big integer for scalability
            $table->id();
            
            // Core issue fields
            $table->string('title')->comment('Issue title - max 255 characters');
            $table->text('description')->comment('Detailed description of the issue');
            
            // Status enum - defines the workflow states
            $table->enum('status', ['open', 'in_progress', 'closed'])
                  ->default('open')
                  ->comment('Current status of the issue');
            
            // Priority enum - helps with issue triage
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])
                  ->default('medium')
                  ->comment('Priority level for handling the issue');
            
            // Laravel timestamps - created_at and updated_at
            $table->timestamps();
            
            // Indexes for performance optimization
            
            // Index on status - frequently used for filtering
            $table->index('status', 'idx_issues_status');
            
            // Index on priority - used for sorting and filtering  
            $table->index('priority', 'idx_issues_priority');
            
            // Index on created_at - used for chronological sorting
            $table->index('created_at', 'idx_issues_created_at');
            
            // Composite index for status + priority - common filter combination
            $table->index(['status', 'priority'], 'idx_issues_status_priority');
            
            // Full-text index for search functionality on title and description
            // Note: This works on MySQL 5.7+ and provides efficient text search
            $table->fullText(['title', 'description'], 'idx_issues_fulltext');
        });
    }

    /**
     * Reverse the migrations.
     * Drops the issues table
     */
    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};