<?php

namespace Volistx\FrameworkKernel\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Volistx\FrameworkKernel\Helpers\SHA256Hasher;
use Volistx\FrameworkKernel\Models\AccessToken;

class AccessKeyGenerateCommand extends Command
{
    protected $signature = 'access-key:generate';

    protected $description = 'Create an access key';

    public function handle(): void
    {
        $key = Str::random(64);
        $salt = Str::random(16);

        AccessToken::query()->create([
            'key'             => substr($key, 0, 32),
            'secret'          => SHA256Hasher::make(substr($key, 32), ['salt' => $salt]),
            'secret_salt'     => $salt,
            'permissions'     => ['*'],
            'whitelist_range' => [],
        ]);

        $this->info('Your access key is created: '.$key);
    }
}
