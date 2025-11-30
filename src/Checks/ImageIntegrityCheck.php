<?php

namespace Syndicate\Inspector\Checks;

use DOMElement;
use Syndicate\Inspector\DTOs\CheckResult;
use Syndicate\Inspector\Enums\RemarkLevel;
use Throwable;

class ImageIntegrityCheck extends BaseCheck
{
    protected string $checklist = 'Content';
    // --- Configuration Properties ---
    protected bool $excludeJsSrc = true;
    protected bool $flagEmptyAlt = true;
    protected RemarkLevel $emptyMissingSrcLevel = RemarkLevel::ERROR;
    protected RemarkLevel $missingAltLevel = RemarkLevel::WARNING;
    protected RemarkLevel $emptyAltLevel = RemarkLevel::WARNING;

    // -----------------------------

    /**
     * Check if <img> tags have appropriate alt attributes.
     */
    protected function applyCheck(): CheckResult
    {
        $findings = [];

        try {
            $selector = $this->excludeJsSrc ? 'img:not([\\:src])' : 'img';
            $images = $this->context->crawler()->filter($selector);

            if ($images->count() === 0) {
                return $this->success('No images found on the page to check.');
            }

            /** @var DOMElement $imageNode */
            foreach ($images as $imageNode) {
                // --- Rule 1: Check for a valid 'src' attribute ---
                $src = trim($imageNode->getAttribute('src'));
                if (!$imageNode->hasAttribute('src') || $src === '') {
                    $findings[] = $this->finding(
                        $this->emptyMissingSrcLevel,
                        'Image tag is missing the "src" attribute or it is empty.',
                        ['issue_type' => 'missing_src']
                    );
                    continue;
                }

                // --- Rule 2: Check for the 'alt' attribute's existence ---
                if (!$imageNode->hasAttribute('alt')) {
                    $findings[] = $this->finding(
                        $this->missingAltLevel,
                        'Image is missing the alt attribute.',
                        ['issue_type' => 'missing_alt', 'image_src' => $src]
                    );
                    continue;
                }

                // --- Rule 3: Check for an empty 'alt' attribute (if configured) ---
                // An empty alt (alt="") is valid for decorative images, so this is optional.
                $altText = trim($imageNode->getAttribute('alt'));
                if ($this->flagEmptyAlt && $altText === '') {
                    $findings[] = $this->finding(
                        $this->emptyAltLevel,
                        'Image has an empty alt attribute (alt=""). This may be intentional for decorative images.',
                        ['issue_type' => 'empty_alt', 'image_src' => $src]
                    );
                }
            }

        } catch (Throwable $e) {
            return $this->result([
                $this->finding(RemarkLevel::ERROR, "Error processing images: " . $e->getMessage())
            ]);
        }

        return $findings ? $this->result($findings) : $this->success('All images have valid src and appropriate alt attributes.');
    }
}
