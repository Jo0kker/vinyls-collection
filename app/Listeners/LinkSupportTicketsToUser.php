<?php

namespace App\Listeners;

use App\Models\SupportTicket;
use Illuminate\Auth\Events\Registered;

class LinkSupportTicketsToUser
{
    public function handle(Registered $event): void
    {
        SupportTicket::linkTicketsToUser($event->user);
    }
}
