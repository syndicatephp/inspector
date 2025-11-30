<?php

namespace Syndicate\Inspector\Checks;

use Syndicate\Inspector\Contracts\Checks\ValidatesSchema;
use Syndicate\Inspector\DTOs\CheckResult;
use Syndicate\Inspector\Enums\RemarkLevel;
use Throwable;

class SchemaCheck extends BaseCheck
{
    protected string $checklist = 'SEO';
    // --- Configuration Properties ---
    protected RemarkLevel $missingLevel = RemarkLevel::ERROR;
    protected RemarkLevel $invalidJsonLevel = RemarkLevel::ERROR;
    protected RemarkLevel $validationErrorLevel = RemarkLevel::ERROR;

    // -----------------------------

    /**
     * Performs basic checks on schema.org JSON-LD scripts and optionally
     * uses a configured external service for deep validation.
     */
    protected function applyCheck(): CheckResult
    {
        $findings = [];
        try {
            $crawler = $this->context->crawler();
            $schemaNode = $crawler->filter('script[type="application/ld+json"]');

            // --- Stage 1: Basic Presence and Content Checks (Always runs) ---
            if ($schemaNode->count() === 0) {
                return $this->result([
                    $this->finding(
                        $this->missingLevel,
                        'No Schema.org script tag (script[type="application/ld+json"]) was found on the page.',
                        ['issue_type' => 'missing']
                    )
                ]);
            }

            $jsonContent = trim($schemaNode->first()->text());
            if (empty($jsonContent)) {
                return $this->result([
                    $this->finding(
                        $this->invalidJsonLevel,
                        'A Schema.org script tag was found, but its content is empty.',
                        ['issue_type' => 'empty_content']
                    )
                ]);
            }

            // Try to decode the JSON to ensure it's not syntactically broken.
            json_decode($jsonContent);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->result([
                    $this->finding(
                        $this->invalidJsonLevel,
                        'The content of the Schema.org script tag is not valid JSON.',
                        ['issue_type' => 'invalid_json', 'error' => json_last_error_msg()]
                    )
                ]);
            }

            // --- Stage 2: Deep Validation (Runs only if a validator is configured) ---
            if (!app()->bound(ValidatesSchema::class)) {
                return $this->success('Schema (JSON-LD) is present and contains valid JSON.');
            }

            $validator = resolve(ValidatesSchema::class);

            // A validator exists, so let's use it.
            $validationErrors = $validator->getValidationErrors($jsonContent, $this->context);

            foreach ($validationErrors as $error) {
                // We expect the validator to return an array of ['message' => string, 'details' => array]
                $errorMessage = $error['message'] ?? 'Schema validation failed with an unspecified error.';
                $errorDetails = $error['details'] ?? [];
                $errorDetails['issue_type'] = 'external_validation_error';

                $findings[] = $this->finding(
                    $this->validationErrorLevel,
                    $errorMessage,
                    $errorDetails
                );
            }

            return $findings ? $this->result($findings) : $this->success('Schema (JSON-LD) passed external validation.');

        } catch (Throwable $e) {
            return $this->result([
                $this->finding(RemarkLevel::ERROR, "Error during Schema.org check: " . $e->getMessage())
            ]);
        }
    }
}
