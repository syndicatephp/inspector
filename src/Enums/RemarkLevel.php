<?php

namespace Syndicate\Inspector\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum RemarkLevel: string implements HasLabel, HasColor, HasIcon
{
    case SUCCESS = 'success';
    case INFO = 'info';
    case NOTICE = 'notice';
    case WARNING = 'warning';
    case ERROR = 'error';
    case FATAL = 'fatal';

    public function getLabel(): string
    {
        return str($this->value)->headline()->toString();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::SUCCESS => 'success',
            self::INFO => 'info',
            self::NOTICE, self::WARNING => 'warning',
            self::ERROR, self::FATAL => 'danger',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::SUCCESS => 'The check passed successfully with no issues found.',
            self::INFO => 'Purely informational data about the response; not an issue.',
            self::NOTICE => 'A minor deviation from best practices. Can be reviewed when convenient and may be tolerated long-term.',
            self::WARNING => 'A significant issue that negatively impacts SEO, accessibility, or user experience. Should be addressed.',
            self::ERROR => 'A high-priority problem where functionality is broken or SEO is actively harmed. Requires urgent attention.',
            self::FATAL => 'A critical, site-breaking emergency (e.g., server error, site down). Requires immediate action.',
        };
    }

    public function getSeverity(): int
    {
        return match ($this) {
            self::SUCCESS => 0,
            self::INFO => 10,
            self::NOTICE => 20,
            self::WARNING => 30,
            self::ERROR => 40,
            self::FATAL => 50,
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::SUCCESS => 'heroicon-o-check-circle',
            self::INFO => 'heroicon-o-information-circle',
            self::NOTICE, self::WARNING => 'heroicon-o-exclamation-circle',
            self::ERROR, self::FATAL => 'heroicon-o-x-circle',
        };
    }
}
