<?php

namespace Hexlet\Code;

class FakeClient
{
    public function request(string $method, string $url, ?array $params): static
    {
        return $this;
    }

    public function get(string $url): static
    {
        return $this;
    }

    public function post(string $url, ?array $params = []): static
    {
        return $this;
    }

    public function put(string $url, ?array $params = []): static
    {
        return $this;
    }

    public function patch(string $url, ?array $params = []): static
    {
        return $this;
    }

    public function delete(string $url): static
    {
        return $this;
    }

    public function getBody(): static
    {
        return $this;
    }

    public function getContents(): string
    {
        return '';
    }

    public function getStatusCode(): int
    {
        return 200;
    }
}