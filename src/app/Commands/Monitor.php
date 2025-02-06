<?php

namespace App\Commands;

use App\Repositories\CoasterRepository;
use App\Services\CoasterStatusGenerator;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Clue\React\Redis\Factory as RedisFactory;
use CodeIgniter\CLI\Commands;
use Psr\Log\LoggerInterface;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;

class Monitor extends BaseCommand
{
    protected $group = 'Monitoring';
    protected $name = 'monitor:coasters';
    protected $description = 'Monitor roller coaster system in real-time using CodeIgniter.';

    private CoasterStatusGenerator $statusGenerator;
    private LoopInterface $loop;
    private RedisFactory $redisFactory;

    public function __construct(
        LoggerInterface $logger,
        Commands $commands,
    ) {
        parent::__construct($logger, $commands);
        $this->statusGenerator = new CoasterStatusGenerator();
        $this->loop = $loop ?? Loop::get();
        $this->redisFactory = new RedisFactory($this->loop);
    }

    public function run(array $params)
    {
        CLI::write('Starting roller coaster monitoring...', 'green');

        $redis = $this->createRedisClient();

        $this->loop->addPeriodicTimer(5, function () use ($redis) {
            $this->checkCoasters($redis);
        });

        $this->loop->run();
    }

    private function createRedisClient()
    {
        $redisConfig = config('Redis');
        $redisUri = "{$redisConfig->host}:{$redisConfig->port}";
        return $this->redisFactory->createLazyClient($redisUri);
    }

    private function checkCoasters($redis)
    {
        $redis->keys(CoasterRepository::COASTERS_KEY . '*')->then(function (array $keys) use ($redis) {
            foreach ($keys as $coasterKey) {
                $this->processCoasterKey($redis, $coasterKey);
            }
        });
    }

    private function processCoasterKey($redis, string $coasterKey)
    {
        $redis->hGet($coasterKey, 'data')->then(function ($data) use ($redis, $coasterKey) {
            $coasterData = json_decode($data, true);
            $redis->hGetAll(CoasterRepository::CARTS_KEY . $coasterKey)->then(function ($carts) use ($coasterData) {
                $processedCarts = $this->decodeCarts($carts);
                CLI::write($this->statusGenerator->getStatus($coasterData, $processedCarts));
            });
        });
    }

    private function decodeCarts(array $carts): array
    {
        $processedCarts = [];
        for ($i = 0; $i < count($carts); $i += 2) {
            $cartId = $carts[$i];
            $cartData = json_decode($carts[$i + 1], true);
            $processedCarts[$cartId] = $cartData;
        }
        return $processedCarts;
    }
}
