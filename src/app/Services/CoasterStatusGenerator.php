<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Log\Logger;

class CoasterStatusGenerator
{
    private const BASE_STAFF = 1;
    private const STAFF_PER_CART = 2;
    private const ADDITIONAL_TRIP_TIME_SECONDS = 300;

    public function getStatus(array $coaster, array $carts): string
    {
        $cartCount = count($carts);
        $staffNeeded = $this->calculateStaffNeeded($cartCount);
        $workingHours = $this->calculateWorkingHours($coaster['godziny_od'], $coaster['godziny_do']);
        $dailyCapacity = $this->calculateDailyCapacity($carts, $workingHours, $coaster['dl_trasy']);
        $clientsPerDay = $coaster['liczba_klientow'];

        $statusData = $this->generateStatus($cartCount, $dailyCapacity, $clientsPerDay, $cartsNeeded, $staffNeeded, $coaster['liczba_personelu']);
        $status = $statusData['status'];
        $cartsNeeded = $statusData['cartsNeeded'];

        $summary = $this->generateSummary($coaster, $cartCount, $cartsNeeded, $staffNeeded, $clientsPerDay, $dailyCapacity, $status);

        if ($status !== 'OK') {
            $this->logIncorrectStatus($status);
        }

        return $summary;
    }

    protected function calculateStaffNeeded(int $cartCount): int
    {
        return self::BASE_STAFF + (self::STAFF_PER_CART * $cartCount);
    }

    protected function calculateWorkingHours(string $startTime, string $endTime): float
    {
        return (strtotime($endTime) - strtotime($startTime)) / 3600;
    }

    protected function calculateDailyCapacity(array $carts, float $workingHours, float $trackLength): int
    {
        $dailyCapacity = 0;

        foreach ($carts as $cart) {
            $seats = $cart['ilosc_miejsc'] ?? 0;
            $speed = $cart['predkosc_wagonu'] ?? 1;
            $tripTime = ($trackLength / $speed) + self::ADDITIONAL_TRIP_TIME_SECONDS;
            $tripsPerCart = floor(($workingHours * 3600) / $tripTime);
            $cartDailyCapacity = $seats * $tripsPerCart;

            $dailyCapacity += (int) $cartDailyCapacity;
        }

        return $dailyCapacity;
    }

    protected function generateStatus(int $cartCount, int $dailyCapacity, int $clientsPerDay, ?int &$cartsNeeded, int $staffNeeded, int $availableStaff): array
    {
        $status = [];

        if ($cartCount === 0) {
            $status[] = 'Brak jakichkolwiek wagonów.';
            $cartsNeeded = 0;
        } else {
            $averageCapacity = $dailyCapacity / $cartCount;
            $cartsNeeded = (int) ceil($clientsPerDay / $averageCapacity);
        }

        if ($cartCount < $cartsNeeded) {
            $status[] = sprintf('Brak %d wagonów.', $cartsNeeded - $cartCount);
        }

        if ($availableStaff < $staffNeeded) {
            $status[] = sprintf('Brakuje %d pracowników.', $staffNeeded - $availableStaff);
        }

        $textStatus = empty($status) ? 'OK' : implode(' ', $status);

        return [
            'status' => $textStatus,
            'cartsNeeded' => $cartsNeeded
        ];
    }

    protected function generateSummary(array $coaster, int $cartCount, int $cartsNeeded, int $staffNeeded, int $clientsPerDay, int $dailyCapacity, string $status): string
    {
        return sprintf(
            '[Kolejka: %s]' . PHP_EOL .
            'Godziny działania: %s - %s' . PHP_EOL .
            'Liczba wagonów: %d/%d' . PHP_EOL .
            'Dostępny personel: %d/%d' . PHP_EOL .
            'Klienci dziennie: %d' . PHP_EOL .
            'Przepustowość dzienna: %d' . PHP_EOL .
            'Status: %s' . PHP_EOL,
            $coaster['id'],
            $coaster['godziny_od'],
            $coaster['godziny_do'],
            $cartCount,
            $cartsNeeded,
            $coaster['liczba_personelu'],
            $staffNeeded,
            $clientsPerDay,
            $dailyCapacity,
            $status
        );
    }

    protected function logIncorrectStatus(string $status): void
    {
        /** @var Logger $logger */
        $logger = service('logger');
        $logger->alert($status);
    }
}
