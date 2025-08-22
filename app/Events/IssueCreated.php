<?php

namespace App\Events;

use App\Models\Issue;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class IssueCreated implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $issue;

    public function __construct(Issue $issue)
    {
        $this->issue = $issue;
    }

    public function broadcastOn()
    {
        return ['issues-channel'];
    }

    public function broadcastAs()
    {
        return 'issue.created';
    }
}
