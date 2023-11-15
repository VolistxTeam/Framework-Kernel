<?php

namespace Volistx\FrameworkKernel\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

class UserRequestCompleted
{
    use InteractsWithSockets;
    use SerializesModels;

    public array $inputs;

    /**
     * Create a new UserRequestCompleted event instance.
     *
     * @param array $inputs The inputs for the user request.
     */
    public function __construct(array $inputs)
    {
        $this->inputs = $inputs;
    }
}
