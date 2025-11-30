<?php

namespace Syndicate\Inspector\DTOs;

use Syndicate\Inspector\Enums\RemarkLevel;

readonly class Finding
{
    public function __construct(
        public RemarkLevel $level,
        public string      $message,
        public ?array      $details = null
    )
    {
    }

    public static function success(
        string $message,
        ?array $details = null
    ): self
    {
        return new self(RemarkLevel::SUCCESS, $message, $details);
    }

    public static function info(
        string $message,
        ?array $details = null
    ): self
    {
        return new self(RemarkLevel::INFO, $message, $details);
    }

    public static function notice(
        string $message,
        ?array $details = null
    ): self
    {
        return new self(RemarkLevel::NOTICE, $message, $details);
    }

    public static function warning(
        string $message,
        ?array $details = null
    ): self
    {
        return new self(RemarkLevel::WARNING, $message, $details);
    }

    public static function error(
        string $message,
        ?array $details = null
    ): self
    {
        return new self(RemarkLevel::ERROR, $message, $details);
    }

    public static function fatal(
        string $message,
        ?array $details = null
    ): self
    {
        return new self(RemarkLevel::FATAL, $message, $details);
    }
}
