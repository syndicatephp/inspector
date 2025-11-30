<?php

namespace Syndicate\Inspector\Checks;

use DOMElement;
use Syndicate\Inspector\DTOs\CheckResult;
use Syndicate\Inspector\Enums\RemarkLevel;
use Throwable;

class TwitterCardCheck extends BaseCheck
{
    protected string $checklist = 'SEO';
    // --- Configuration Properties ---
    protected RemarkLevel $missingRequiredLevel = RemarkLevel::WARNING;
    protected RemarkLevel $validationLevel = RemarkLevel::WARNING;
    // -----------------------------

    /** The properties that are considered essential for a basic Twitter Card. */
    protected array $requiredProperties = ['twitter:card', 'twitter:title', 'twitter:description', 'twitter:image'];

    protected function applyCheck(): CheckResult
    {
        $findings = [];
        try {
            $crawler = $this->context->crawler();
            $presentProperties = [];

            // 1. Find all Twitter Card tags and validate them individually.
            $presentTwitterTags = $crawler->filter('head > meta[name^="twitter:"]');

            /** @var DOMElement $node */
            foreach ($presentTwitterTags as $node) {
                $property = $node->getAttribute('name');
                $content = trim($node->getAttribute('content'));

                // Track presence for duplicate and missing checks.
                if (isset($presentProperties[$property])) {
                    $findings[] = $this->finding($this->validationLevel, "Multiple Twitter Card tags found for property '$property'.", ['issue_type' => 'multiple', 'property' => $property]);
                }
                $presentProperties[$property] = $content;

                if (empty($content)) {
                    $findings[] = $this->finding($this->validationLevel, "Twitter Card property '$property' has empty content.", ['issue_type' => 'empty_content', 'property' => $property]);
                }
            }

            // 2. Check for MISSING required properties.
            foreach ($this->requiredProperties as $property) {
                if (!isset($presentProperties[$property])) {
                    $findings[] = $this->finding($this->missingRequiredLevel, "Required Twitter Card property '$property' is missing.", ['issue_type' => 'missing_required', 'property' => $property]);
                }
            }

            // 3. Special validation for 'twitter:card' content.
            if (isset($presentProperties['twitter:card']) && !in_array($presentProperties['twitter:card'], ['summary', 'summary_large_image', 'app', 'player'])) {
                $findings[] = $this->finding($this->validationLevel, "Twitter Card property 'twitter:card' has an invalid value '{$presentProperties['twitter:card']}'.", ['issue_type' => 'invalid_card_type', 'content' => $presentProperties['twitter:card']]);
            }

        } catch (Throwable $e) {
            return $this->result([
                $this->finding(RemarkLevel::ERROR, "Error during Twitter Card check: " . $e->getMessage())
            ]);
        }

        return $findings ? $this->result($findings) : $this->success('All required Twitter Card tags are present and valid.');
    }
}
