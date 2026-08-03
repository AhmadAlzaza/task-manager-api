<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Jobs\SendWelcomeEmailJob;

class SendWelcomeEmailListener
{
    public function handle(UserRegistered $event): void
    {
        SendWelcomeEmailJob::dispatch($event->user);
    }
}
