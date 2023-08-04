<?php

namespace Hexlet\Code;

class FakeClient
{
    public function get(string $url): static
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
}