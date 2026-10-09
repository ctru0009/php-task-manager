<?php

declare(strict_types=1);

final class HttpResponse
{
    /**
     * @param array<string, list<string>> $headers lower-case names
     */
    public function __construct(
        public readonly int $status,
        public readonly array $headers,
        public readonly string $body,
    ) {
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)][0] ?? null;
    }

    /** @return list<string> */
    public function headerAll(string $name): array
    {
        return $this->headers[strtolower($name)] ?? [];
    }

    public function contains(string $needle): bool
    {
        return str_contains($this->body, $needle);
    }

    public function location(): string
    {
        $location = $this->header('Location');

        if ($location === null) {
            throw new RuntimeException(sprintf('Response has status %d and no Location header.', $this->status));
        }

        return $location;
    }

    public function csrf(): string
    {
        if (preg_match('/name="csrf_token"\s+value="([^"]+)"/', $this->body, $matches) !== 1) {
            throw new RuntimeException('No csrf_token field found in the response body.');
        }

        return html_entity_decode($matches[1], ENT_QUOTES);
    }
}
