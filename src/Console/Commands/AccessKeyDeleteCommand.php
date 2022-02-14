<?php

namespace Volistx\FrameworkKernel\Console\Commands;

use Illuminate\Console\Command;
use Volistx\FrameworkKernel\Repositories\AccessTokenRepository;

class AccessKeyDeleteCommand extends Command
{
    protected $signature = 'access-key:delete {--key=}';

    protected $description = 'Delete an access key';

    /**
     * @return void
     */
    public function handle()
    {
        $token = $this->option('key');

        if (empty($token)) {
            $this->error('Please specify your access key to delete.');

            return;
        }

        $repo = new AccessTokenRepository();
        $accessToken = $repo->AuthAccessToken($token);

        if (!$accessToken) {
            $this->error('The specified access key is invalid.');

            return;
        }

        $accessToken->delete();

        $this->info('Your access key is deleted: '.$token);
    }
}
