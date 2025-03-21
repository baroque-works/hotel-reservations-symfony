<?php

namespace App\Domain\Model;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class Reservation
{
    #[Assert\NotBlank(message: "El localizador no puede estar vacío")]
    private string $locator;

    #[Assert\NotBlank(message: "El nombre del huésped no puede estar vacío")]
    private string $guest;

    #[Assert\NotNull]
    private \DateTime $checkInDate;

    #[Assert\NotNull]
    private \DateTime $checkOutDate;

    #[Assert\NotBlank(message: "El nombre del hotel no puede estar vacío")]
    private string $hotel;

    private ?float $price;

    #[Assert\NotBlank(message: "Las acciones posibles no pueden estar vacías")]
    private string $possibleActions;

    public function __construct(
        string $locator,
        string $guest,
        \DateTime $checkInDate,
        \DateTime $checkOutDate,
        string $hotel,
        ?float $price,
        string $possibleActions
    ) {
        $this->locator = $locator;
        $this->guest = $guest;
        $this->checkInDate = $checkInDate;
        $this->checkOutDate = $checkOutDate;
        $this->hotel = $hotel;
        $this->price = $price;
        $this->possibleActions = $possibleActions;
    }

    // Getters
    public function getLocator(): string
    {
        return $this->locator;
    }

    public function getGuest(): string
    {
        return $this->guest;
    }

    public function getCheckInDate(): \DateTime
    {
        return clone $this->checkInDate;
    }

    public function getCheckOutDate(): \DateTime
    {
        return clone $this->checkOutDate;
    }

    public function getHotel(): string
    {
        return $this->hotel;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function getPossibleActions(): string
    {
        return $this->possibleActions;
    }

    // Métodos de serialización
    public function toArray(): array
    {
        return [
            'locator' => $this->locator,
            'guest' => $this->guest,
            'check_in_date' => $this->checkInDate->format('Y-m-d'),
            'check_out_date' => $this->checkOutDate->format('Y-m-d'),
            'hotel' => $this->hotel,
            'price' => $this->price,
            'possible_actions' => $this->possibleActions,
        ];
    }

    public function matchesSearchTerm(string $searchTerm): bool
    {
        if (empty($searchTerm)) {
            return true;
        }

        $searchTerm = strtolower($searchTerm);

        return str_contains(strtolower($this->locator), $searchTerm) ||
               str_contains(strtolower($this->guest), $searchTerm) ||
               str_contains(strtolower($this->checkInDate->format('Y-m-d')), $searchTerm) ||
               str_contains(strtolower($this->checkOutDate->format('Y-m-d')), $searchTerm) ||
               str_contains(strtolower($this->hotel), $searchTerm) ||
               ($this->price !== null && str_contains((string)$this->price, $searchTerm)) ||
               str_contains(strtolower($this->possibleActions), $searchTerm);
    }

    // Validaciones personalizadas
    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        if ($this->checkOutDate < $this->checkInDate) {
            $context->buildViolation('La fecha de salida debe ser igual o posterior a la fecha de entrada')
                ->atPath('checkOutDate')
                ->addViolation();
        }

        if ($this->price !== null && $this->price < 0) {
            $context->buildViolation('El precio no puede ser negativo')
                ->atPath('price')
                ->addViolation();
        }

        if ($this->price === null && str_contains(strtolower($this->possibleActions), 'charge')) {
            $context->buildViolation('El precio es obligatorio para reservas cobrables')
                ->atPath('price')
                ->addViolation();
        }

        if ($this->price === null) {
            $context->buildViolation('Falta el precio')
                ->atPath('price')
                ->addViolation();
        }
    }
}
