<?php

namespace Syndicate\Inspector\Checks;

use Syndicate\Inspector\DTOs\CheckResult;
use Syndicate\Inspector\Enums\RemarkLevel;

class StatusCodeCheck extends BaseCheck
{
    // --- Configuration Properties ---
    protected RemarkLevel $redirectLevel = RemarkLevel::WARNING;
    protected RemarkLevel $clientErrorLevel = RemarkLevel::ERROR;
    protected RemarkLevel $serverErrorLevel = RemarkLevel::FATAL;
    protected RemarkLevel $unexpectedStatusLevel = RemarkLevel::ERROR;

    // -----------------------------

    public static function checklist(): string
    {
        return 'Baseline';
    }

    /**
     * Checks the HTTP status code of the response.
     */
    protected function applyCheck(): CheckResult
    {
        $response = $this->context->response;
        $statusCode = $response->status();
        $details = ['status_code' => $statusCode];

        // 1. Successful (2xx) - Standard case
        if ($response->successful()) {
            return $this->success(
                "Status code ($statusCode) indicates success.",
                $details
            );
        }

        // 2. Redirect (3xx)
        if ($response->redirect()) {
            if ($this->redirectLevel === RemarkLevel::SUCCESS) {
                return $this->success(
                    "Page redirected ($statusCode) as expected.",
                    $details
                );
            }

            $details['redirect_location'] = $response->header('Location');

            return $this->result([
                $this->finding(
                    $this->redirectLevel,
                    "Page redirected ($statusCode).",
                    $details
                )
            ]);
        }

        // 3. Client Errors (4xx)
        if ($response->clientError()) {
            return $this->result([
                $this->finding(
                    $this->clientErrorLevel,
                    "Client error response ($statusCode).",
                    $details
                )
            ]);
        }

        // 4. Server Errors (5xx)
        if ($response->serverError()) {
            return $this->result([
                $this->finding(
                    $this->serverErrorLevel,
                    "Server error response ($statusCode).",
                    $details
                )
            ]);
        }

        // 5. Other non-2xx/3xx/4xx/5xx codes (Informational 1xx, etc.)
        return $this->result([
            $this->finding(
                $this->unexpectedStatusLevel,
                "Received unexpected status code: $statusCode.",
                $details
            )
        ]);
    }
}
