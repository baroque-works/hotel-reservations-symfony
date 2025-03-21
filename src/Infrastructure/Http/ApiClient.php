<?php

namespace App\Infrastructure\Http;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ApiClient
{
    private HttpClientInterface $httpClient;
    private string $baseUrl;
    private string $username;
    private string $password;

    public function __construct(
        string $baseUrl,
        ?string $username,
        ?string $password,
        private LoggerInterface $logger,
        ?HttpClientInterface $httpClient = null
    ) {
        $this->logger->debug('ApiClient constructed with baseUrl: {baseUrl}', ['baseUrl' => $baseUrl]);

        if (empty($baseUrl)) {
            $this->logger->error('API base URL is empty');
            throw new \InvalidArgumentException('API base URL cannot be empty');
        }

        if (!preg_match('/^https?:\/\//', $baseUrl)) {
            $this->logger->warning('API base URL is missing scheme, defaulting to http://', ['baseUrl' => $baseUrl]);
            $baseUrl = 'http://' . ltrim($baseUrl, '/');
        }

        $this->baseUrl = rtrim($baseUrl, '/');
        $this->username = $username ?? '';
        $this->password = $password ?? '';

        $this->httpClient = $httpClient ?? HttpClient::create([
            'timeout' => 30.0,
            'headers' => [
                'User-Agent' => 'Symfony Hotel Reservations App',
            ],
            'verify_peer' => false,
            'verify_host' => false,
        ]);

        $this->logger->info('ApiClient initialized with base URL: {baseUrl}', ['baseUrl' => $this->baseUrl]);

        if ($this->username && $this->password) {
            $this->authenticateWithFullFlow();
        }
    }

    public function fetchCsvData(): string
    {
        try {
            $url = $this->baseUrl . '/';
            $this->logger->debug('Fetching CSV data from: {url}', ['url' => $url]);
            $response = $this->httpClient->request('GET', $url, [
                'auth_basic' => [$this->username, $this->password],
            ]);
            $this->logger->debug('Fetched CSV data successfully');
            return $response->getContent();
        } catch (\Throwable $e) {
            $this->logger->error('Error fetching CSV data: {message}', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
            throw new \RuntimeException('Error fetching CSV data: ' . $e->getMessage(), 0, $e);
        }
    }

    private function authenticateWithFullFlow(): void
    {
        try {
            $url = $this->baseUrl . '/';
            $this->logger->debug('Fetching login page from: {url}', ['url' => $url]);
            $this->logger->debug('URL components: {components}', ['components' => parse_url($url)]);
            $loginPageResponse = $this->httpClient->request('GET', $url, [
                'auth_basic' => [$this->username, $this->password],
            ]);
            $loginPageHtml = $loginPageResponse->getContent();
            $this->logger->debug('Login page fetched successfully');

            $formParams = $this->extractFormFields($loginPageHtml);
            $this->logger->debug('Extracted form params: {params}', ['params' => $formParams]);

            $formParams['Username'] = $this->username;
            $formParams['Password'] = $this->password;
            $this->logger->debug('Form params with credentials: {params}', ['params' => $formParams]);

            $this->logger->debug('Sending login form to: {url}', ['url' => $url]);
            $response = $this->httpClient->request('POST', $url, [
                'body' => $formParams,
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Referer' => $this->baseUrl,
                ],
                'auth_basic' => [$this->username, $this->password],
            ]);

            if ($response->getStatusCode() !== 200) {
                $this->logger->error('Login failed with status code: {status}', ['status' => $response->getStatusCode()]);
                throw new \RuntimeException('Login failed with status code: ' . $response->getStatusCode());
            }

            $this->logger->info('Successfully authenticated with API');
        } catch (\Throwable $e) {
            $this->logger->error('Authentication failed: {message}', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
            throw new \RuntimeException('Authentication failed: ' . $e->getMessage(), 0, $e);
        }
    }

    private function extractFormFields(string $html): array
    {
        $formParams = [];
        $pattern = '/<input[^>]*name=["\']([^"\']*)["\'][^>]*value=["\']([^"\']*)["\'][^>]*>/i';
        if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $formParams[$match[1]] = $match[2];
            }
        }
        return $formParams;
    }
}
