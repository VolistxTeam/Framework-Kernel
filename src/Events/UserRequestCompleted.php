<?php

namespace Volistx\FrameworkKernel\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

class UserRequestCompleted
{
    use  InteractsWithSockets, SerializesModels;

    public string $url;
    public string $method;
    public string $ip;
    public string $user_agent;
    public string $subscription_id;

   public function __construct(string $url, string $method, string $ip, string $user_agent, string $subscription_id)
   {
       $this->url = $url;
       $this->method = $method;
       $this->ip =$ip;
       $this->user_agent = $user_agent;
       $this->subscription_id = $subscription_id;
   }
}
