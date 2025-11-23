<?php

namespace Syndicate\Inspector\Listeners;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Syndicate\Inspector\Enums\RemarkLevel;
use Syndicate\Inspector\Events\BulkInspectionCompleted;
use Syndicate\Inspector\Notifications\SlackInspectionNotification;

class NotifyOfBulkInspectionCompleted
{
    public function handle(BulkInspectionCompleted $event): void
    {
        $modelInspectionReport = $event->modelInspectionReport;

        // Get minimum level threshold from config
        $minLevelConfig = Config::get('syndicate.inspector.notification_min_level');
        if (!$minLevelConfig) {
            return;
        }

        $minLevel = RemarkLevel::from($minLevelConfig);

        // Check if notification should be sent based on threshold
        if (!$this->shouldNotifyBasedOnLevel($modelInspectionReport, $minLevel)) {
            return;
        }

        // Get slack channel from config
        $channel = Config::get('syndicate.inspector.slack_channel');
        if (empty($channel)) {
            return;
        }

        Notification::route('slack', $channel)
            ->notify(new SlackInspectionNotification(
                $modelInspectionReport,
                $event->totalTimeSeconds,
                $event->averageTimePerInspection
            ));
    }

    private function shouldNotifyBasedOnLevel($modelInspectionReport, RemarkLevel $minLevel): bool
    {
        // Success level (lowest severity) always sends notification
        if ($minLevel === RemarkLevel::SUCCESS) {
            return true;
        }

        // Check if there are any remarks at or above the minimum level
        switch ($minLevel) {
            case RemarkLevel::INFO:
                return $modelInspectionReport->info > 0
                    || $modelInspectionReport->notice > 0
                    || $modelInspectionReport->warning > 0
                    || $modelInspectionReport->error > 0
                    || $modelInspectionReport->fatal > 0;
            case RemarkLevel::NOTICE:
                return $modelInspectionReport->notice > 0
                    || $modelInspectionReport->warning > 0
                    || $modelInspectionReport->error > 0
                    || $modelInspectionReport->fatal > 0;
            case RemarkLevel::WARNING:
                return $modelInspectionReport->warning > 0
                    || $modelInspectionReport->error > 0
                    || $modelInspectionReport->fatal > 0;
            case RemarkLevel::ERROR:
                return $modelInspectionReport->error > 0
                    || $modelInspectionReport->fatal > 0;
            case RemarkLevel::FATAL:
                return $modelInspectionReport->fatal > 0;
            default:
                return false;
        }
    }
}
