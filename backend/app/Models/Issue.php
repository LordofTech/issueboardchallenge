<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * Issue Model
 * 
 * Represents an issue in the system with title, description, status, and priority
 * Includes scopes for filtering and search functionality
 * 
 * @property int $id
 * @property string $title
 * @property string $description  
 * @property string $status
 * @property string $priority
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Issue extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * These fields can be filled using create() or fill() methods
     */
    protected $fillable = [
        'title',
        'description', 
        'status',
        'priority',
    ];

    /**
     * The attributes that should be cast to native types.
     * This ensures proper data types when accessing model attributes
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Valid status values for validation
     * These match the ENUM values in the database
     */
    public const STATUSES = ['open', 'in_progress', 'closed'];

    /**
     * Valid priority values for validation
     * These match the ENUM values in the database  
     */
    public const PRIORITIES = ['low', 'medium', 'high', 'critical'];

    /**
     * Scope to filter issues by status
     * 
     * Usage: Issue::byStatus('open')->get()
     * 
     * @param Builder $query
     * @param string $status
     * @return Builder
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter issues by priority
     * 
     * Usage: Issue::byPriority('high')->get()
     * 
     * @param Builder $query
     * @param string $priority
     * @return Builder
     */
    public function scopeByPriority(Builder $query, string $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope to search issues by title and description
     * Uses MySQL FULLTEXT search for performance
     * 
     * Usage: Issue::search('bug fix')->get()
     * 
     * @param Builder $query
     * @param string $term
     * @return Builder
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        // Use MySQL FULLTEXT search for better performance on large datasets
        // Fallback to LIKE search if FULLTEXT is not available
        return $query->whereRaw(
            "MATCH(title, description) AGAINST(? IN BOOLEAN MODE)",
            [$term . '*']
        )->orWhere('title', 'LIKE', "%{$term}%")
         ->orWhere('description', 'LIKE', "%{$term}%");
    }

    /**
     * Scope to order issues by latest first
     * 
     * Usage: Issue::latest()->get()  
     * 
     * @param Builder $query
     * @return Builder
     */
    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Get the priority level as a numeric value for sorting
     * Useful for ordering issues by priority importance
     * 
     * @return int
     */
    public function getPriorityLevelAttribute(): int
    {
        $levels = [
            'low' => 1,
            'medium' => 2, 
            'high' => 3,
            'critical' => 4,
        ];

        return $levels[$this->priority] ?? 2;
    }

    /**
     * Check if the issue is open
     * 
     * @return bool
     */
    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    /**
     * Check if the issue is in progress
     * 
     * @return bool  
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Check if the issue is closed
     * 
     * @return bool
     */
    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /**
     * Get the status color for UI display
     * Returns CSS color classes based on status
     * 
     * @return string
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'open' => 'text-red-600',
            'in_progress' => 'text-yellow-600', 
            'closed' => 'text-green-600',
            default => 'text-gray-600',
        };
    }

    /**
     * Get the priority color for UI display
     * Returns CSS color classes based on priority
     * 
     * @return string
     */
    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'low' => 'text-gray-600',
            'medium' => 'text-blue-600',
            'high' => 'text-orange-600', 
            'critical' => 'text-red-600',
            default => 'text-gray-600',
        };
    }
}