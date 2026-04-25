<?php

namespace App\Exceptions;

class DiscogsRateLimitException extends \RuntimeException
{
    public function __construct(string $endpoint = '', public int $retryAfter = 60)
    {
        parent::__construct("Discogs rate limit hit ({$endpoint})");
    }
}
