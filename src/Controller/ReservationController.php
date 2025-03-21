<?php

namespace App\Controller;

use App\Application\Service\ReservationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

class ReservationController extends AbstractController
{
    #[Route('/debug-env', name: 'debug_env', methods: ['GET'])]
    public function debugEnv(): JsonResponse
    {
        return new JsonResponse([
            'app.api_base_url' => $this->getParameter('app.api_base_url'),
            'app.api_username' => $this->getParameter('app.api_username'),
            'app.api_password' => $this->getParameter('app.api_password'),
            'getenv_API_BASE_URL' => getenv('API_BASE_URL') ?: 'Not available',
            'getenv_API_USERNAME' => getenv('API_USERNAME') ?: 'Not available',
            'getenv_API_PASSWORD' => getenv('API_PASSWORD') ?: 'Not available',
        ]);
    }

    #[Route('/', name: 'reservation_list', methods: ['GET'])]
    public function list(Request $request, ReservationService $reservationService): Response
    {
        $searchTerm = $request->query->get('search', '');
        $page = max(1, $request->query->getInt('page', 1));
        $itemsPerPage = 15;

        $result = !empty($searchTerm)
            ? $reservationService->searchReservations($searchTerm, $page, $itemsPerPage)
            : $reservationService->getPaginatedReservations($page, $itemsPerPage);

        $reservations = $result['reservations'];
        $totalReservations = $result['total'];
        $totalPages = max(1, (int) ceil($totalReservations / $itemsPerPage));

        if ($page > $totalPages) {
            $result = !empty($searchTerm)
                ? $reservationService->searchReservations($searchTerm, $totalPages, $itemsPerPage)
                : $reservationService->getPaginatedReservations($totalPages, $itemsPerPage);
            $reservations = $result['reservations'];
            $page = $totalPages;
        }

        $reservationsWithErrors = [];
        foreach ($reservations as $reservation) {
            $reservationsWithErrors[] = [
                'reservation' => $reservation,
                'errors' => $reservationService->validateReservation($reservation),
            ];
        }

        return $this->render('reservation/list.html.twig', [
            'reservationsWithErrors' => $reservationsWithErrors,
            'totalReservations' => $totalReservations,
            'totalPages' => $totalPages,
            'page' => $page,
            'searchTerm' => $searchTerm,
            'request' => $request->query->all(),
        ]);
    }

    #[Route('/download-json', name: 'reservation_download_json', methods: ['GET'])]
    public function downloadJson(Request $request, ReservationService $reservationService): StreamedResponse
    {
        $searchTerm = $request->query->get('search', '');
        $result = $searchTerm
            ? $reservationService->searchReservations($searchTerm, 1, PHP_INT_MAX)
            : $reservationService->getPaginatedReservations(1, PHP_INT_MAX);

        $reservations = $result['reservations'] ?? [];

        return new StreamedResponse(
            function () use ($reservations) {
                echo '[';
                $first = true;
                foreach ($reservations as $reservation) {
                    if (!$first) {
                        echo ',';
                    }
                    $first = false;

                    $reservationData = [
                        'locator' => $reservation->getLocator(),
                        'guest' => $reservation->getGuest(),
                        'checkInDate' => $reservation->getCheckInDate()->format('Y-m-d'),
                        'checkOutDate' => $reservation->getCheckOutDate()->format('Y-m-d'),
                        'hotel' => $reservation->getHotel(),
                        'price' => $reservation->getPrice(),
                        'possibleActions' => $reservation->getPossibleActions(),
                    ];

                    echo json_encode($reservationData, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
                }
                echo ']';
            },
            200,
            [
                'Content-Type' => 'application/json',
                'Content-Disposition' => 'attachment; filename="reservations.json"',
            ]
        );
    }
}
