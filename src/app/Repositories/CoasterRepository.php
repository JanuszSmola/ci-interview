<?php

declare(strict_types=1);

namespace App\Repositories;

use Redis;

class CoasterRepository
{
    protected Redis $redis;

    public const string COASTER_COUNTER_KEY = 'coaster_counter';
    public const string COASTERS_KEY = 'coaster:';

    public const string CARTS_KEY = 'carts:';
    public const string COASTERS_CARTS_COUNTER_KEY = 'carts_counter:';

    public function __construct()
    {
        $this->redis = service('redis');
    }
    public function add(array $data): int
    {
        $id = $this->redis->incr(self::COASTER_COUNTER_KEY);
        $data['id'] = $id;
        $this->redis->hSet(self::COASTERS_KEY . $id, 'data', json_encode($data));

        return $id;
    }

    public function addCart(int $coasterId, array $data): int
    {
        $cartId = $this->redis->incr(self::COASTERS_CARTS_COUNTER_KEY . $coasterId);
        $data['cart_id'] = $cartId;
        $this->redis->hSet(self::CARTS_KEY . self::COASTERS_KEY . $coasterId, (string) $cartId, json_encode($data));

        return $cartId;
    }

    public function coasterExists(int $coasterId): bool
    {
        return !empty($this->redis->exists(self::COASTERS_KEY . $coasterId));
    }

    public function cartExists(int $coasterId, int $cartId): bool
    {
        return $this->redis->exists(self::CARTS_KEY . self::COASTERS_KEY . $coasterId . ':' . $cartId);
    }

    public function removeCart(int $coasterId, int $cartId): bool
    {
        return $this->redis->del(self::CARTS_KEY . self::COASTERS_KEY . $coasterId . ':' . $cartId);
    }

    public function getCoaster(int $coasterId): array
    {
        $coaster = $this->redis->hGet(self::COASTERS_KEY . $coasterId, 'data');

        return $coaster
            ? json_decode($this->redis->hGet(self::COASTERS_KEY . $coasterId, 'data'), true)
            : [];
    }

    public function updateCoaster(int $coasterId, array $data): void
    {
        $this->redis->hSet(self::COASTERS_KEY. $coasterId, 'data', json_encode($data));
    }
}