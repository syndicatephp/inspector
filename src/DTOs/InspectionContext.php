<?php

namespace Syndicate\Inspector\DTOs;

use Illuminate\Http\Client\Response;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;
use Syndicate\Inspector\Contracts\Inspection;
use Throwable;

class InspectionContext
{
    private ?Crawler $crawler = null;

    public function __construct(
        public readonly Inspection $inspection,
        public readonly Response   $response,
    )
    {
    }

    public static function make(Inspection $inspection, Response $response): self
    {
        return new self($inspection, $response);
    }

    public function crawler(): Crawler
    {
        // 1. Return from the cache if already created.
        if ($this->crawler !== null) {
            return $this->crawler;
        }

        // 2. Attempt to create the Crawler.
        try {
            $html = $this->response->body();
            $contentType = strtolower($this->response->header('Content-Type'));

            if (!str_contains($contentType, 'text/html')) {
                throw new RuntimeException("Response is not HTML (Content-Type: $contentType).");
            }

            if (empty($html)) {
                throw new RuntimeException("Response body is empty.");
            }

            // Create, cache, and return on success.
            $this->crawler = new Crawler($html, $this->inspection->url());

        } catch (Throwable $e) {
            if ($e instanceof RuntimeException) {
                throw $e;
            }

            throw new RuntimeException("Could not parse HTML: " . $e->getMessage(), 0, $e);
        }

        return $this->crawler;
    }
}
