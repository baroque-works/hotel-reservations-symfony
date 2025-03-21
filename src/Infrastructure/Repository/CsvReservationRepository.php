<?php

namespace App\Infrastructure\Repository;

use App\Domain\Model\Reservation;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Infrastructure\Http\ApiClient;

class CsvReservationRepository implements ReservationRepositoryInterface
{
    private array $reservations = [];

    public function __construct(private readonly ApiClient $apiClient)
    {
    }

    public function findByPage(int $page, int $limit = 10): array
    {
        $this->loadReservations();
        $offset = ($page - 1) * $limit;
        return array_slice($this->reservations, $offset, $limit);
    }

    public function getTotalReservations(): int
    {
        $this->loadReservations();
        return count($this->reservations);
    }

    public function findBySearchTerm(string $searchTerm): array
    {
        $this->loadReservations();
        return array_filter($this->reservations, fn(Reservation $reservation) => $reservation->matchesSearchTerm($searchTerm));
    }

    public function findAll(): array
    {
        $this->loadReservations();
        return $this->reservations;
    }

    private function loadReservations(): void
    {
        if (!empty($this->reservations)) {
            return;
        }

        try {
            $csvData = $this->apiClient->fetchCsvData();
        } catch (\Exception $e) {
            $this->reservations = [];
            return;
        }

        $lines = explode("\n", $csvData);
        $reservations = [];

        array_shift($lines);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $data = str_getcsv($line, ';', '"', '\\');
            if (count($data) < 7) {
                continue;
            }

            try {
                $checkInDate = new \DateTime($data[2]);
                $checkOutDate = new \DateTime($data[3]);
                $price = !empty($data[5]) ? (float) $data[5] : null;

                $reservation = new Reservation(
                    $data[0],
                    $data[1],
                    $checkInDate,
                    $checkOutDate,
                    $data[4],
                    $price,
                    $data[6]
                );

                $reservations[] = $reservation;
            } catch (\Exception $e) {
                continue;
            }
        }

        $this->reservations = $reservations;
    }
}
