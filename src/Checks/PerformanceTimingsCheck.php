<?php

namespace Syndicate\Inspector\Checks;

use Syndicate\Inspector\DTOs\CheckResult;
use Syndicate\Inspector\Enums\RemarkLevel;
use Throwable;

class PerformanceTimingsCheck extends BaseCheck
{
    protected string $checklist = 'Performance';
    // --- Configuration Properties ---
    protected int $dnsWarningMs = 100;
    protected int $tcpConnectionWarningMs = 100;
    protected int $tlsHandshakeWarningMs = 200;
    protected int $serverProcessingNoticeMs = 100;
    protected int $serverProcessingWarningMs = 200;
    protected int $serverProcessingErrorMs = 400;
    protected int $totalTimeWarningMs = 1000;
    protected int $totalTimeErrorMs = 2000;
    protected RemarkLevel $level = RemarkLevel::WARNING;

    // -----------------------------

    /**
     * Analyzes the different phases of the HTTP request for performance bottlenecks.
     */
    protected function applyCheck(): CheckResult
    {
        try {
            $stats = $this->context->response->transferStats;

            if (!$stats) {
                return $this->result([
                    $this->finding(
                        RemarkLevel::INFO,
                        'Skipped: Transfer stats were not collected. Use Http::withStats() to enable.'
                    )
                ]);
            }

            $handlerStats = $stats->getHandlerStats();
            $findings = [];

            // 1. DNS Lookup Time
            $dnsTimeMs = round(($handlerStats['namelookup_time'] ?? 0) * 1000);
            if ($dnsTimeMs > $this->dnsWarningMs) {
                $findings[] = $this->finding($this->level, "DNS lookup is slow ({$dnsTimeMs}ms).",
                    ['issue_type' => 'slow_dns', 'time_ms' => $dnsTimeMs, 'threshold_ms' => $this->dnsWarningMs]);
            }

            // 2. TCP Connection Time
            $tcpTimeMs = round((($handlerStats['connect_time'] ?? 0) - ($handlerStats['namelookup_time'] ?? 0)) * 1000);
            if ($tcpTimeMs > $this->tcpConnectionWarningMs) {
                $findings[] = $this->finding($this->level, "TCP connection is slow ({$tcpTimeMs}ms).", [
                    'issue_type' => 'slow_tcp', 'time_ms' => $tcpTimeMs, 'threshold_ms' => $this->tcpConnectionWarningMs
                ]);
            }

            // 3. TLS Handshake Time
            if (($handlerStats['scheme'] ?? '') === 'https' && isset($handlerStats['appconnect_time'])) {
                $tlsTimeMs = round((($handlerStats['appconnect_time']) - ($handlerStats['connect_time'] ?? 0)) * 1000);
                if ($tlsTimeMs > $this->tlsHandshakeWarningMs) {
                    $findings[] = $this->finding($this->level, "TLS handshake is slow ({$tlsTimeMs}ms).", [
                        'issue_type' => 'slow_tls', 'time_ms' => $tlsTimeMs,
                        'threshold_ms' => $this->tlsHandshakeWarningMs
                    ]);
                }
            }

            // 4. Server Processing Time (TTFB)
            $serverTimeMs = round((($handlerStats['starttransfer_time'] ?? 0) - ($handlerStats['pretransfer_time'] ?? 0)) * 1000);
            if ($serverTimeMs > $this->serverProcessingErrorMs) {
                $findings[] = $this->finding(RemarkLevel::ERROR,
                    "Server processing time is critically slow ({$serverTimeMs}ms).", [
                        'issue_type' => 'slow_server_critical', 'time_ms' => $serverTimeMs,
                        'threshold_ms' => $this->serverProcessingErrorMs
                    ]);
            } elseif ($serverTimeMs > $this->serverProcessingWarningMs) {
                $findings[] = $this->finding(RemarkLevel::WARNING, "Server processing time is slow ({$serverTimeMs}ms).",
                    [
                        'issue_type' => 'slow_server_warning', 'time_ms' => $serverTimeMs,
                        'threshold_ms' => $this->serverProcessingWarningMs
                    ]);
            } elseif ($serverTimeMs > $this->serverProcessingNoticeMs) {
                $findings[] = $this->finding(RemarkLevel::NOTICE, "Server processing time is slow ({$serverTimeMs}ms).",
                    [
                        'issue_type' => 'slow_server_notice', 'time_ms' => $serverTimeMs,
                        'threshold_ms' => $this->serverProcessingNoticeMs
                    ]);
            }

            // 5. Total Request Time
            $totalTimeMs = round(($handlerStats['total_time'] ?? 0) * 1000);
            if ($totalTimeMs > $this->totalTimeErrorMs) {
                $findings[] = $this->finding(RemarkLevel::ERROR,
                    "Total request time is critically slow ({$totalTimeMs}ms).", [
                        'issue_type' => 'slow_total_critical', 'time_ms' => $totalTimeMs,
                        'threshold_ms' => $this->totalTimeErrorMs
                    ]);
            } elseif ($totalTimeMs > $this->totalTimeWarningMs) {
                $findings[] = $this->finding($this->level, "Total request time is slow ({$totalTimeMs}ms).", [
                    'issue_type' => 'slow_total_warning', 'time_ms' => $totalTimeMs,
                    'threshold_ms' => $this->totalTimeWarningMs
                ]);
            }

            return $findings ? $this->result($findings) : $this->success("Performance timings are good (Server: {$serverTimeMs}ms, Total: {$totalTimeMs}ms).");

        } catch (Throwable $e) {
            return $this->result([
                $this->finding(RemarkLevel::ERROR, "Error during performance timings check: " . $e->getMessage())
            ]);
        }
    }
}
