<?php

namespace App\Events;

use App\Models\Issue;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when an existing issue is updated
 * This event is broadcast to all connected clients for real-time updates
 */
class IssueUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The issue that was updated
     */
    public Issue $issue;

    /**
     * Array of fields that were changed
     */
    public array $changes;

    /**
     * Create a new event instance.
     * 
     * @param Issue $issue The updated issue
     * @param array $changes Array of changed fields
     */
    public function __construct(Issue $issue, array $changes = [])
    {
        $this->issue = $issue;
        $this->changes = $changes;
    }

    /**
     * Get the channels the event should broadcast on.
     * 
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            // Public channel - all users can listen to issue updates
            new Channel('issues'),
        ];
    }

    /**
     * The event's broadcast name.
     * This is what the frontend will listen for
     * 
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'issue.updated';
    }

    /**
     * Get the data to broadcast.
     * This data will be sent to all connected clients
     * 
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'issue' => [
                'id' => $this->issue->id,
                'title' => $this->issue->title,
                'description' => $this->issue->description,
                'status' => $this->issue->status,
                'priority' => $this->issue->priority,
                'created_at' => $this->issue->created_at->toISOString(),
                'updated_at' => $this->issue->updated_at->toISOString(),
            ],
            'changes' => $this->changes,
            'message' => 'An issue has been updated',
            'type' => 'updated'
        ];
    }

    /**
     * Determine if this event should broadcast.
     * 
     * @return bool
     */
    public function shouldBroadcast(): bool
    {
        // Only broadcast if there were actual changes
        return !empty($this->changes);
    }
}