<?php

namespace Volistx\FrameworkKernel\Console\Commands;

use Illuminate\Console\Command;
use Volistx\FrameworkKernel\Repositories\AccessTokenRepository;

class AccessKeyDeleteCommand extends Command
{
    private AccessTokenRepository $accessTokenRepository;

    public function __construct(AccessTokenRepository $accessTokenRepository)
    {
        parent::__construct();
        $this->accessTokenRepository = $accessTokenRepository;
    }

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

        $accessToken = $this->accessTokenRepository->AuthAccessToken($token);

        if (!$accessToken) {
            $this->error('The specified access key is invalid.');

            return;
        }

        $this->accessTokenRepository->Delete($accessToken->id);

        $this->info('Your access key is deleted: '.$token);
    }
}
