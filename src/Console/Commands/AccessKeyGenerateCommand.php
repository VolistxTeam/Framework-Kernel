<?php
namespace Volistx\FrameworkKernel\Console\Commands;

use Illuminate\Console\Command;
use Volistx\FrameworkKernel\Enums\AccessRule;
use Volistx\FrameworkKernel\Facades\Keys;
use Volistx\FrameworkKernel\Repositories\AccessTokenRepository;

class AccessKeyGenerateCommand extends Command
{
    private AccessTokenRepository $accessTokenRepository;

    /**
     * Create a new AccessKeyGenerateCommand instance.
     *
     * @param AccessTokenRepository $accessTokenRepository The access token repository.
     */
    public function __construct(AccessTokenRepository $accessTokenRepository)
    {
        parent::__construct();
        $this->accessTokenRepository = $accessTokenRepository;
    }

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'access-key:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an access key';

    /**
     * Handle the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        // Generate a random salted key
        $saltedKey = Keys::randomSaltedKey();

        // Create the access token
        $this->accessTokenRepository->Create([
            'key'             => $saltedKey['key'],
            'salt'            => $saltedKey['salt'],
            'permissions'     => ['*'],
            'ip_rule'         => AccessRule::NONE,
            'ip_range'        => [],
            'country_rule'    => AccessRule::NONE,
            'country_range'   => [],
        ]);

        // Display the created access key
        $this->info('Your access key is created: "' . $saltedKey['key'] . '"');
    }
}