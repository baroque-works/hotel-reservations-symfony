<?php

namespace App\Tests\Unit\Infrastructure\Repository;

use App\Infrastructure\Http\ApiClient;
use App\Infrastructure\Repository\CsvReservationRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CsvReservationRepositoryTest extends TestCase
{
    private ApiClient|MockObject $apiClient;
    private CsvReservationRepository $repository;

    protected function setUp(): void
    {
        $this->apiClient = $this->createMock(ApiClient::class);
        $this->repository = new CsvReservationRepository($this->apiClient);
    }

    public function testFindByPage(): void
    {
        // Arrange
        $csvData = <<<CSV
Localizador;Huésped;fecha de entrada;fecha de salida;Hotel;Precio;Posibles Acciones
34637;Nombre 1;2018-10-04;2018-10-05;Hotel 4;112.49;Cobrar Devolver
34694;Nombre 2;2018-06-15;2018-06-17;Hotel 1;427.77;Cobrar Devolver
CSV;

        $this->apiClient->expects($this->once())
            ->method('fetchCsvData')
            ->willReturn($csvData);

        // Act
        $reservations = $this->repository->findByPage(1, 1);

        // Assert
        $this->assertCount(1, $reservations);
        $this->assertEquals('34637', $reservations[0]->getLocator());
        $this->assertEquals('Nombre 1', $reservations[0]->getGuest());
        $this->assertEquals('Hotel 4', $reservations[0]->getHotel());
        $this->assertEquals(112.49, $reservations[0]->getPrice());
    }

    public function testFindBySearchTerm(): void
    {
        // Arrange
        $csvData = <<<CSV
Localizador;Huésped;fecha de entrada;fecha de salida;Hotel;Precio;Posibles Acciones
34637;Nombre 1;2018-10-04;2018-10-05;Hotel 4;112.49;Cobrar Devolver
34694;Nombre 2;2018-06-15;2018-06-17;Hotel 1;427.77;Cobrar Devolver
CSV;

        $this->apiClient->expects($this->once())
            ->method('fetchCsvData')
            ->willReturn($csvData);

        // Act
        $reservations = $this->repository->findBySearchTerm('Nombre 1');

        // Assert
        $this->assertCount(1, $reservations);
        $this->assertEquals('34637', $reservations[0]->getLocator());
        $this->assertEquals('Nombre 1', $reservations[0]->getGuest());
    }

    public function testGetTotalReservations(): void
    {
        // Arrange
        $csvData = <<<CSV
Localizador;Huésped;fecha de entrada;fecha de salida;Hotel;Precio;Posibles Acciones
34637;Nombre 1;2018-10-04;2018-10-05;Hotel 4;112.49;Cobrar Devolver
34694;Nombre 2;2018-06-15;2018-06-17;Hotel 1;427.77;Cobrar Devolver
CSV;

        $this->apiClient->expects($this->once())
            ->method('fetchCsvData')
            ->willReturn($csvData);

        // Act
        $total = $this->repository->getTotalReservations();

        // Assert
        $this->assertEquals(2, $total);
    }

    public function testFindAll(): void
    {
        // Arrange
        $csvData = <<<CSV
Localizador;Huésped;fecha de entrada;fecha de salida;Hotel;Precio;Posibles Acciones
34637;Nombre 1;2018-10-04;2018-10-05;Hotel 4;112.49;Cobrar Devolver
34694;Nombre 2;2018-06-15;2018-06-17;Hotel 1;427.77;Cobrar Devolver
CSV;

        $this->apiClient->expects($this->once())
            ->method('fetchCsvData')
            ->willReturn($csvData);

        // Act
        $reservations = $this->repository->findAll();

        // Assert
        $this->assertCount(2, $reservations);
        $this->assertEquals('34637', $reservations[0]->getLocator());
        $this->assertEquals('Nombre 1', $reservations[0]->getGuest());
        $this->assertEquals('34694', $reservations[1]->getLocator());
        $this->assertEquals('Nombre 2', $reservations[1]->getGuest());
    }

    public function testFindByPageWithEmptyCsv(): void
    {
        // Arrange
        $csvData = <<<CSV
Localizador;Huésped;fecha de entrada;fecha de salida;Hotel;Precio;Posibles Acciones
CSV;

        $this->apiClient->expects($this->once())
            ->method('fetchCsvData')
            ->willReturn($csvData);

        // Act
        $reservations = $this->repository->findByPage(1, 10);

        // Assert
        $this->assertEmpty($reservations);
    }

    public function testFindBySearchTermWithInvalidData(): void
    {
        // Arrange
        $csvData = <<<CSV
Localizador;Huésped;fecha de entrada;fecha de salida;Hotel;Precio;Posibles Acciones
34637;Nombre 1;invalid-date;2018-10-05;Hotel 4;112.49;Cobrar Devolver
34694;Nombre 2;2018-06-15;2018-06-17;Hotel 1;427.77;Cobrar Devolver
CSV;

        $this->apiClient->expects($this->once())
            ->method('fetchCsvData')
            ->willReturn($csvData);

        // Act
        $reservations = $this->repository->findBySearchTerm('Nombre 2');

        // Assert
        $this->assertCount(1, $reservations);
        $this->assertEquals('34694', $reservations[0]->getLocator());
        $this->assertEquals('Nombre 2', $reservations[0]->getGuest());
    }

    public function testFindAllWithApiError(): void
    {
        // Arrange
        $this->apiClient->expects($this->once())
            ->method('fetchCsvData')
            ->willThrowException(new \RuntimeException('API error'));

        // Act
        $reservations = $this->repository->findAll();

        // Assert
        $this->assertEmpty($reservations);
    }
}
