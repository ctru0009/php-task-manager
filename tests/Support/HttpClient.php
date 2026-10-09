<?php

declare(strict_types=1);

/**
 * Minimal cookie-aware HTTP client for the test suite. Redirects are not
 * followed: tests assert on the 302 and its Location header themselves.
 */
final class HttpClient
{
    private string $baseUrl;

    /** @var array<string, string> */
    private array $cookies = [];

    public function __construct(?string $baseUrl = null)
    {
        $this->baseUrl = $baseUrl ?? TestServer::baseUrl();
    }

    public function get(string $path, array $headers = []): HttpResponse
    {
        return $this->request('GET', $path, [], $headers);
    }

    public function post(string $path, array $fields = [], array $headers = []): HttpResponse
    {
        return $this->request('POST', $path, $fields, $headers);
    }

    public function cookie(string $name): ?string
    {
        return $this->cookies[$name] ?? null;
    }

    /** @return array<string, string> */
    public function cookies(): array
    {
        return $this->cookies;
    }

    public function clearCookies(): void
    {
        $this->cookies = [];
    }

    private function request(string $method, string $path, array $fields, array $headers): HttpResponse
    {
        $url = str_starts_with($path, 'http') ? $path : $this->baseUrl . $path;

        $requestHeaders = $headers;

        if ($this->cookies !== []) {
            $pairs = [];

            foreach ($this->cookies as $name => $value) {
                $pairs[] = $name . '=' . $value;
            }

            $requestHeaders[] = 'Cookie: ' . implode('; ', $pairs);
        }

        $body = null;

        if ($method === 'POST') {
            $requestHeaders[] = 'Content-Type: application/x-www-form-urlencoded';
            $body = http_build_query($fields);
        }

        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $requestHeaders),
                'content' => $body,
                'ignore_errors' => true,
                'follow_location' => 0,
                'max_redirects' => 0,
                'timeout' => 10,
            ],
        ]);

        $rawBody = @file_get_contents($url, false, $context);

        if ($rawBody === false) {
            throw new RuntimeException(sprintf('Request failed: %s %s', $method, $url));
        }

        $status = 0;
        $responseHeaders = [];

        foreach ($http_response_header ?? [] as $line) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $matches) === 1) {
                $status = (int) $matches[1];
                $responseHeaders = [];
                continue;
            }

            $parts = explode(':', $line, 2);

            if (count($parts) === 2) {
                $responseHeaders[strtolower(trim($parts[0]))][] = trim($parts[1]);
            }
        }

        foreach ($responseHeaders['set-cookie'] ?? [] as $cookie) {
            if (preg_match('/^([^=]+)=([^;]*)/', $cookie, $matches) === 1) {
                $this->cookies[trim($matches[1])] = $matches[2];
            }
        }

        return new HttpResponse($status, $responseHeaders, $rawBody);
    }
}
