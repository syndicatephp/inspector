<?php

namespace Syndicate\Inspector\Checks;

use DateTime;
use DOMElement;
use Syndicate\Inspector\DTOs\CheckResult;
use Syndicate\Inspector\Enums\RemarkLevel;
use Throwable;

class OpenGraphCheck extends BaseCheck
{
    // --- Configuration Properties ---
    protected RemarkLevel $missingRequiredLevel = RemarkLevel::ERROR;
    protected RemarkLevel $missingRecommendedLevel = RemarkLevel::WARNING;
    protected RemarkLevel $validationLevel = RemarkLevel::WARNING;
    protected array $requiredProperties = ['og:title', 'og:type', 'og:url'];
    protected array $recommendedProperties = ['og:description', 'og:locale', 'og:site_name', 'og:image:alt'];
    protected array $arrayableProperties = [
        'og:image', 'og:locale:alternate', 'og:audio', 'og:video',
        'music:album', 'music:musician',
        'video:actor', 'video:director', 'video:writer', 'video:tag',
        'article:author', 'article:tag',
        'book:author', 'book:tag',
    ];

    protected array $knownProperties = [
        'og:title' => 'non_empty_string', 'og:type' => 'non_empty_string', 'og:url' => 'absolute_url',
        'og:description' => 'non_empty_string', 'og:determiner' => 'enum:a,an,the,,auto', 'og:locale' => 'locale',
        'og:locale:alternate' => 'locale', 'og:site_name' => 'non_empty_string',
        'og:image' => 'absolute_url', 'og:image:url' => 'absolute_url', 'og:image:secure_url' => 'absolute_url',
        'og:image:type' => 'mime_type', 'og:image:width' => 'numeric', 'og:image:height' => 'numeric',
        'og:image:alt' => 'non_empty_string',
        'og:video' => 'absolute_url', 'og:video:secure_url' => 'absolute_url', 'og:video:type' => 'mime_type',
        'og:video:width' => 'numeric', 'og:video:height' => 'numeric',
        'og:audio' => 'absolute_url', 'og:audio:secure_url' => 'absolute_url', 'og:audio:type' => 'mime_type',
        'article:published_time' => 'datetime', 'article:modified_time' => 'datetime',
        'article:expiration_time' => 'datetime',
        'article:author' => 'absolute_url', 'article:section' => 'non_empty_string',
        'article:tag' => 'non_empty_string',
    ];

    // -----------------------------

    public static function checklist(): string
    {
        return 'SEO';
    }

    protected function applyCheck(): CheckResult
    {
        $findings = [];
        try {
            $crawler = $this->context->crawler();
            $presentProperties = [];

            // 1. First, find all OG tags and validate them individually.
            $presentOgTags = $crawler->filter('head > meta[property^="og:"], head > meta[property^="article:"], head > meta[property^="music:"], head > meta[property^="video:"], head > meta[property^="book:"], head > meta[property^="profile:"]');

            /** @var DOMElement $node */
            foreach ($presentOgTags as $node) {
                $property = $node->getAttribute('property');
                $content = trim($node->getAttribute('content'));

                // Track presence for duplicate and missing checks.
                $presentProperties[$property][] = $content;

                if (empty($content)) {
                    $findings[] = $this->finding($this->validationLevel,
                        "Open Graph property '$property' has empty content.",
                        ['issue_type' => 'empty_content', 'property' => $property]);
                    continue;
                }

                if ($ruleType = $this->knownProperties[$property] ?? null) {
                    $this->validateContent($findings, $property, $content, $ruleType);
                }
            }

            // 2. Check for duplicates, but only for non-arrayable properties.
            $this->checkForDuplicates($findings, $presentProperties);

            // 3. Check for missing required and recommended properties.
            $this->checkForMissing($findings, $presentProperties);

        } catch (Throwable $e) {
            return $this->result([
                $this->finding(RemarkLevel::ERROR, "Error during Open Graph check: " . $e->getMessage())
            ]);
        }

        return $findings ? $this->result($findings) : $this->success('Open Graph tags are well-formed and valid.');
    }

    /**
     * Main validation dispatcher.
     */
    private function validateContent(array &$findings, string $property, string $content, string $ruleType): void
    {
        match ($ruleType) {
            'non_empty_string' => null,
            'absolute_url' => $this->validateAbsoluteUrl($findings, $property, $content),
            'numeric' => $this->validateNumeric($findings, $property, $content),
            'mime_type' => $this->validateMimeType($findings, $property, $content),
            'datetime' => $this->validateDateTime($findings, $property, $content),
            default => null,
        };
    }

    private function validateAbsoluteUrl(array &$findings, string $property, string $content): void
    {
        if (!preg_match('~^https?://~', $content)) {
            $findings[] = $this->finding($this->validationLevel,
                "Open Graph property '$property' must be an absolute URL.",
                ['issue_type' => 'relative_url', 'property' => $property, 'content' => $content]);
        }
    }

    private function validateNumeric(array &$findings, string $property, string $content): void
    {
        if (!is_numeric($content)) {
            $findings[] = $this->finding($this->validationLevel,
                "Open Graph property '$property' must have a numeric value.",
                ['issue_type' => 'not_numeric', 'property' => $property, 'content' => $content]);
        }
    }

    private function validateMimeType(array &$findings, string $property, string $content): void
    {
        // Simple check for a two-part mime type like 'image/jpeg' or 'video/mp4'.
        if (!preg_match('~^[a-z]+/[a-z0-9\-+]+$~', $content)) {
            $findings[] = $this->finding($this->validationLevel,
                "Open Graph property '$property' has an invalid MIME type format.",
                ['issue_type' => 'invalid_mime_type', 'property' => $property, 'content' => $content]);
        }
    }

    private function validateDateTime(array &$findings, string $property, string $content): void
    {
        // Checks if the string can be parsed as a date according to ISO 8601, which OG recommends.
        try {
            new DateTime($content);
        } catch (Throwable) {
            $findings[] = $this->finding($this->validationLevel,
                "Open Graph property '$property' has an invalid datetime format.",
                ['issue_type' => 'invalid_datetime', 'property' => $property, 'content' => $content]);
        }
    }

    private function checkForDuplicates(array &$findings, array $presentProperties): void
    {
        foreach ($presentProperties as $property => $values) {
            if (count($values) > 1 && !in_array($property, $this->arrayableProperties, true)) {
                $findings[] = $this->finding($this->validationLevel,
                    "Multiple Open Graph tags found for non-arrayable property '$property'.",
                    ['issue_type' => 'multiple', 'property' => $property, 'count' => count($values)]);
            }
        }
    }

    private function checkForMissing(array &$findings, array $presentProperties): void
    {
        foreach ($this->requiredProperties as $property) {
            if (!isset($presentProperties[$property])) {
                $findings[] = $this->finding($this->missingRequiredLevel,
                    "Required Open Graph property '$property' is missing.",
                    ['issue_type' => 'missing_required', 'property' => $property]);
            }
        }

        if (!isset($presentProperties['og:image']) && !isset($presentProperties['og:image:url'])) {
            $findings[] = $this->finding($this->missingRequiredLevel,
                "Required Open Graph image ('og:image' or 'og:image:url') is missing.",
                ['issue_type' => 'missing_image', 'property' => 'og:image']);
        }

        foreach ($this->recommendedProperties as $property) {
            if (!isset($presentProperties[$property])) {
                $findings[] = $this->finding($this->missingRecommendedLevel,
                    "Recommended Open Graph property '$property' is missing.",
                    ['issue_type' => 'missing_recommended', 'property' => $property]);
            }
        }
    }
}
