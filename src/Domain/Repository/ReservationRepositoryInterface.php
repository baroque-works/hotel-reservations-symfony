<?php

namespace App\Domain\Repository;

use App\Domain\Model\Reservation;

interface ReservationRepositoryInterface
{
    /**
     * @return Reservation[]
     */
    public function findAll(): array;

    /**
     * @return Reservation[]
     */
    public function findBySearchTerm(string $searchTerm): array;

    /**
     * @return Reservation[]
     */
    public function findByPage(int $page, int $limit = 10): array;

    public function getTotalReservations(): int;
}
