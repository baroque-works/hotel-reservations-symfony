<?php

namespace App\Tests\Unit\Application\Service;

use App\Application\Service\ReservationService;
use App\Domain\Model\Reservation;
use App\Domain\Repository\ReservationRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ReservationServiceTest extends TestCase
{
    private ReservationRepositoryInterface|MockObject $repository;
    private ValidatorInterface|MockObject $validator;
    private ReservationService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ReservationRepositoryInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->service = new ReservationService($this->repository, $this->validator);
    }

    public function testGetPaginatedReservations(): void
    {
        // Arrange
        $page = 2;
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $reservations = [
            $this->createMock(Reservation::class),
            $this->createMock(Reservation::class),
        ];
        $totalReservations = 25;

        $this->repository->expects($this->once())
            ->method('findByPage')
            ->with($page, $limit)
            ->willReturn($reservations);

        $this->repository->expects($this->once())
            ->method('getTotalReservations')
            ->willReturn($totalReservations);

        // Act
        $result = $this->service->getPaginatedReservations($page, $limit);

        // Assert
        $this->assertEquals([
            'reservations' => $reservations,
            'total' => $totalReservations,
        ], $result);
    }

    public function testSearchReservations(): void
    {
        // Arrange
        $searchTerm = 'test';
        $page = 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $reservation1 = $this->createMock(Reservation::class);
        $reservation1->method('matchesSearchTerm')
            ->with($searchTerm)
            ->willReturn(true);

        $reservation2 = $this->createMock(Reservation::class);
        $reservation2->method('matchesSearchTerm')
            ->with($searchTerm)
            ->willReturn(false);

        $allReservations = [$reservation1, $reservation2];
        $filteredReservations = [$reservation1];

        $this->repository->expects($this->once())
            ->method('findBySearchTerm')
            ->with($searchTerm)
            ->willReturn($filteredReservations);

        // Act
        $result = $this->service->searchReservations($searchTerm, $page, $limit);

        // Assert
        $this->assertEquals([
            'reservations' => $filteredReservations,
            'total' => 1,
        ], $result);
    }

    public function testValidateReservation(): void
    {
        // Arrange
        $reservation = $this->createMock(Reservation::class);
        $violations = new ConstraintViolationList([
            new \Symfony\Component\Validator\ConstraintViolation('Error 1', '', [], '', '', ''),
            new \Symfony\Component\Validator\ConstraintViolation('Error 2', '', [], '', '', ''),
        ]);

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($reservation)
            ->willReturn($violations);

        // Act
        $errors = $this->service->validateReservation($reservation);

        // Assert
        $this->assertEquals(['Error 1', 'Error 2'], $errors);
    }

    public function testGetPaginatedReservationsWithNoReservations(): void
    {
        // Arrange
        $page = 1;
        $limit = 10;

        $this->repository->expects($this->once())
            ->method('findByPage')
            ->with($page, $limit)
            ->willReturn([]);

        $this->repository->expects($this->once())
            ->method('getTotalReservations')
            ->willReturn(0);

        // Act
        $result = $this->service->getPaginatedReservations($page, $limit);

        // Assert
        $this->assertEquals([
            'reservations' => [],
            'total' => 0,
        ], $result);
    }

    public function testSearchReservationsWithNoMatches(): void
    {
        // Arrange
        $searchTerm = 'nonexistent';
        $page = 1;
        $limit = 10;

        $this->repository->expects($this->once())
            ->method('findBySearchTerm')
            ->with($searchTerm)
            ->willReturn([]);

        // Act
        $result = $this->service->searchReservations($searchTerm, $page, $limit);

        // Assert
        $this->assertEquals([
            'reservations' => [],
            'total' => 0,
        ], $result);
    }
}
