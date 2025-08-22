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
 * Event fired when a new issue is created
 * This event is broadcast to all connected clients for real-time updates
 */
class IssueCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The issue that was created
     */
    public Issue $issue;

    /**
     * Create a new event instance.
     * 
     * @param Issue $issue The newly created issue
     */
    public function __construct(Issue $issue)
    {
        $this->issue = $issue;
    }

    /**
     * Get the channels the event should broadcast on.
     * We broadcast on a public channel so all users can see new issues
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
        return 'issue.created';
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
            'message' => 'A new issue has been created',
            'type' => 'created'
        ];
    }

    /**
     * Determine if this event should broadcast.
     * You can add conditions here to control when the event broadcasts
     * 
     * @return bool
     */
    public function shouldBroadcast(): bool
    {
        // Always broadcast new issues
        // In a real app, you might check user permissions or other conditions
        return true;
    }
}