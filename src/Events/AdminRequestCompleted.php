<?php

namespace Volistx\FrameworkKernel\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

class AdminRequestCompleted
{
    use InteractsWithSockets;
    use SerializesModels;

    public array $inputs;

    /**
     * Create a new AdminRequestCompleted event instance.
     *
     * @param array $inputs The inputs for the admin request.
     */
    public function __construct(array $inputs)
    {
        $this->inputs = $inputs;
    }
}
