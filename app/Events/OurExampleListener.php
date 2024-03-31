<?php

namespace App\Events;

use App\Events\OurExampleEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class OurExampleListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event/incoming parameter
     * @param \App\Events\OurExampleEvent $event
     * return void
     */
    public function handle(OurExampleEvent $event): void

    {

        Log::debug("The user {$event->username} performed {$event->action}");
    }
}
