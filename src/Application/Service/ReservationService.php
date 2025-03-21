<?php

namespace App\Application\Service;

use App\Domain\Model\Reservation;
use App\Domain\Repository\ReservationRepositoryInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ReservationService
{
    public function __construct(
        private ReservationRepositoryInterface $repository,
        private ValidatorInterface $validator
    ) {
    }

    /**
     * @return array{reservations: Reservation[], total: int}
     */
    public function getPaginatedReservations(int $page = 1, int $limit = 20): array
    {
        $reservations = $this->repository->findByPage($page, $limit);
        return [
            'reservations' => $reservations,
            'total' => $this->repository->getTotalReservations(),
        ];
    }

    /**
     * @return array{reservations: Reservation[], total: int}
     */
    public function searchReservations(string $searchTerm, int $page = 1, int $limit = 20): array
    {
        $filteredReservations = $this->repository->findBySearchTerm($searchTerm);
        $offset = ($page - 1) * $limit;
        $paginatedReservations = array_slice(array_values($filteredReservations), $offset, $limit);

        return [
            'reservations' => $paginatedReservations,
            'total' => count($filteredReservations),
        ];
    }

    /**
     * Validate a reservation and returns errors if exists.
     *
     * @return array<string>
     */
    public function validateReservation(Reservation $reservation): array
    {
        $violations = $this->validator->validate($reservation);
        $errors = [];

        foreach ($violations as $violation) {
            $errors[] = $violation->getMessage();
        }

        return $errors;
    }
}
