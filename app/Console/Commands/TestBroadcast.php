<?php

namespace App\Console\Commands;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Console\Command;

class TestBroadcast extends Command
{
    protected $signature = 'broadcast:test {conversation_id?}';
    protected $description = 'Test broadcasting a message to a conversation';

    public function handle(): int
    {
        $conversationId = $this->argument('conversation_id');

        if (!$conversationId) {
            $conversation = Conversation::with('participants')->first();
            if (!$conversation) {
                $this->error('No conversation found. Create one first.');
                return 1;
            }
        } else {
            $conversation = Conversation::with('participants')->find($conversationId);
            if (!$conversation) {
                $this->error("Conversation {$conversationId} not found.");
                return 1;
            }
        }

        $this->info("Testing broadcast to conversation #{$conversation->id}");
        $this->info("Participants: " . $conversation->participants->pluck('name')->join(', '));
        $this->info("Channel: private-conversation.{$conversation->id}");

        // Create a test message
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $conversation->participants->first()->id,
            'content' => '[TEST] Message de test broadcast - ' . now()->format('H:i:s'),
        ]);

        $message->load(['sender', 'attachments']);

        $this->info("Broadcasting MessageSent event...");

        try {
            broadcast(new MessageSent($message));
            $this->info("Broadcast sent successfully!");
            $this->newLine();
            $this->info("Check your browser console for the event.");
        } catch (\Exception $e) {
            $this->error("Broadcast failed: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
