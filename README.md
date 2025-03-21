# Hotel Reservations Symfony

A Symfony 7.2 application for managing hotel reservations by fetching data in CSV format from an external API (`http://tech-test.wamdev.net/`), displaying the reservations with pagination and free-text search, and allowing users to download the data as JSON.

## Project Overview

This project was developed as part of a technical test to demonstrate proficiency in PHP and Symfony. The application fulfills the following requirements:

- Fetches a list of hotel reservations in CSV format from an external API (`http://tech-test.wamdev.net/`).
- Displays the reservations in a paginated table with columns: `Localizador`, `Huésped`, `Fecha Entrada`, `Fecha Salida`, `Hotel`, `Precio`, `Acciones`, and `Estado`.
- Allows searching reservations by a free-text term that matches any field (e.g., locator, guest, hotel).
- Provides an option to download the reservations as a JSON file (`reservations.json`).
- Includes validation for reservation data (e.g., check-in date must be before check-out date).

The project follows best practices, including a hexagonal architecture, PSR-12 coding standards, and comprehensive test coverage.

## Requirements

- **PHP**: >= 8.2 (developed with PHP 8.4 for modern features like typed properties).
- **Composer**: For dependency management.
- **Symfony CLI**: For running the local development server.
- **Dependencies** (installed via Composer):
  - `symfony/framework-bundle`: Core Symfony framework.
  - `symfony/http-client`: For making HTTP requests to the API.
  - `symfony/twig-bundle`: For rendering templates.
  - `symfony/validator`: For validating reservation data.
  - `symfony/monolog-bundle`: For logging.
  - `symfony/debug-bundle`: For debugging (dev environment).
  - `symfony/profiler-pack`: For profiling (dev environment).
  - `phpunit/phpunit`: For running tests (dev environment).

## Installation

1. **Clone the repository**:

   ```bash
   git clone https://github.com/tu-usuario/hotel-reservations-symfony.git
   cd hotel-reservations-symfony
   ```

2. **Install dependencies**:

   ```bash
   composer install
   ```

3. **Configure environment variables**:
   Copy .env to .env.local.php and set the real values for your environment:

   ```php
   <?php

   return [
       'APP_ENV' => 'dev',
       'APP_DEBUG' => true,
       'APP_SECRET' => 'your_random_secure_value', // Generate with `openssl rand -hex 32`
       'API_BASE_URL' => 'https://tech-test.wamdev.net/',
       'API_USERNAME' => 'guest',
       'API_PASSWORD' => 'wamguest',
   ];
   ```

   Note: APP_ENV must be defined in .env.local.php or .env.local, as the application requires it to start.

4. **Start the server**:

   ```bash
   symfony server:start
   ```

5. **Access the application**:
   Open your browser and go to http://localhost:8000.

## Features

### List Reservations:

- Displays a paginated table of reservations fetched from the API.
- Columns: Localizador, Huésped, Fecha Entrada, Fecha Salida, Hotel, Precio, Acciones, Estado.
- Supports pagination with 10 reservations per page.
- Includes a search form for free-text search across all fields.

### Search Reservations:

- Allows searching by any field (e.g., Nombre 1, Hotel 4, 34637).
- Returns matching reservations in a paginated table.

### Download as JSON:

- Provides a /download-json endpoint to download all reservations as a JSON file (reservations.json).
- The JSON includes fields: locator, guest, checkInDate, checkOutDate, hotel, price, possibleActions.

### Validation:

- Validates reservation data (e.g., check-in date must be before check-out date).
- Displays validation errors in the table under the Estado column.

## Project Structure

The project follows a hexagonal architecture (ports and adapters) to ensure separation of concerns and testability:

### src/Domain/:

- **Model/Reservation.php**: Defines the Reservation entity with properties (locator, guest, checkInDate, checkOutDate, hotel, price, possibleActions) and validation rules (e.g., check-in date must be before check-out date).
- **Repository/ReservationRepositoryInterface.php**: Defines the interface for accessing reservations (methods: findByPage, getTotalReservations, findBySearchTerm, findAll).

### src/Application/:

- **Service/ReservationService.php**: Contains the business logic for fetching, searching, and validating reservations. Methods include getPaginatedReservations, searchReservations, and validateReservation.

### src/Infrastructure/:

- **Http/ApiClient.php**: Handles HTTP requests to the API, including basic authentication and CSV fetching.
- **Repository/CsvReservationRepository.php**: Implements ReservationRepositoryInterface to parse CSV data into Reservation objects.

### src/Controller/:

- **ReservationController.php**: Handles HTTP requests and renders the UI. Endpoints:
  - `/`: Displays the paginated list of reservations with search functionality.
  - `/download-json`: Downloads reservations as a JSON file.

### templates/:

- **reservation/list.html.twig**: Twig template for rendering the reservation table with pagination and search form.

### public/:

- **css/styles.css**: Styles for the UI.
- **img/logo.png**: Logo image displayed in the UI.

### tests/:

- **Unit/**: Unit tests for ReservationService, ApiClient, Reservation, and CsvReservationRepository.
- **Functional/**: Functional tests for ReservationController.

## Running Tests

The project includes 15 unit and functional tests with 56 assertions, covering happy paths and edge cases (e.g., empty CSV, invalid data, API errors). The test suite runs in ~0.4 seconds.

### Run all tests:

```bash
php bin/phpunit
```

### Run specific tests:

Unit tests for CsvReservationRepository:

```bash
php bin/phpunit tests/Unit/Infrastructure/Repository/CsvReservationRepositoryTest.php
```

Functional tests for ReservationController:

```bash
php bin/phpunit tests/Functional/Controller/ReservationControllerTest.php
```

## Environment Variables

The following environment variables are required:

- **APP_ENV**: The application environment (dev, prod, test). Default: dev.
- **APP_DEBUG**: Enable debug mode (true or false). Default: true.
- **APP_SECRET**: A random, secure value for Symfony's internal security (generate with openssl rand -hex 32).
- **API_BASE_URL**: The base URL of the API (e.g., https://tech-test.wamdev.net/).
- **API_USERNAME**: The username for API authentication (e.g., guest).
- **API_PASSWORD**: The password for API authentication (e.g., wamguest).

These variables should be defined in .env.local.php or .env.local. Do not store sensitive values in .env.

## Development Notes

- **Symfony Version**: 7.2, chosen for its modern features and long-term support.
- **PHP Version**: 8.4, ensuring compatibility with the requirement of PHP 7.2 or higher.
- **Architecture**: Hexagonal (ports and adapters) to separate business logic from infrastructure, improving maintainability and testability.
- **Coding Standards**: Follows PSR-12 for consistent code formatting.
- **Error Handling**:
  - ApiClient handles HTTP errors and authentication failures, logging errors via Monolog.
  - CsvReservationRepository handles invalid CSV data (e.g., malformed dates, incomplete lines) and API errors, returning an empty array in case of failure.
- **Validation**: Uses Symfony Validator to enforce rules on Reservation (e.g., check-in date must be before check-out date).
- **Assets**: Static assets (CSS, images) are served from the public/ directory.
- **Authentication**: Uses basic authentication to interact with the API (guest:wamguest).

## Known Issues

- The application assumes the API (http://tech-test.wamdev.net/) is always available. In a production environment, additional error handling (e.g., retries, fallback data) would be needed.
- The UI is basic and could be improved with modern asset management (e.g., Webpack Encore).

## Future Improvements

- Configure Webpack Encore for modern asset management (CSS, JS).
- Add integration tests to verify real API interactions.
- Optimize for production by disabling the profiler, configuring caching, and securing environment variables.
- Dockerize the app.
