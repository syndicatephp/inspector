<?php

namespace Syndicate\Inspector\DTOs;

use Syndicate\Inspector\Contracts\Check;
use Syndicate\Inspector\Enums\RemarkLevel;

readonly class Finding
{
    /**
     * @param class-string<Check> $checkClass
     */
    public function __construct(
        public RemarkLevel $level,
        public string      $message,
        public string      $checkClass,
        public string      $url,
        public ?array      $config = null,
        public ?array      $details = null
    )
    {
    }

    /**
     * @param class-string<Check> $checkClass
     */
    public static function success(
        string $message,
        string $checkClass,
        string $url,
        ?array $config = null,
        ?array $details = null
    ): self
    {
        return new self(RemarkLevel::SUCCESS, $message, $checkClass, $url, $config, $details);
    }

    /**
     * @param class-string<Check> $checkClass
     */
    public static function info(
        string $message,
        string $checkClass,
        string $url,
        ?array $config = null,
        ?array $details = null
    ): self
    {
        return new self(RemarkLevel::INFO, $message, $checkClass, $url, $config, $details);
    }

    /**
     * @param class-string<Check> $checkClass
     */
    public static function notice(
        string $message,
        string $checkClass,
        string $url,
        ?array $config = null,
        ?array $details = null
    ): self
    {
        return new self(RemarkLevel::NOTICE, $message, $checkClass, $url, $config, $details);
    }

    /**
     * @param class-string<Check> $checkClass
     */
    public static function warning(
        string $message,
        string $checkClass,
        string $url,
        ?array $config = null,
        ?array $details = null
    ): self
    {
        return new self(RemarkLevel::WARNING, $message, $checkClass, $url, $config, $details);
    }

    /**
     * @param class-string<Check> $checkClass
     */
    public static function error(
        string $message,
        string $checkClass,
        string $url,
        ?array $config = null,
        ?array $details = null
    ): self
    {
        return new self(RemarkLevel::ERROR, $message, $checkClass, $url, $config, $details);
    }

    /**
     * @param class-string<Check> $checkClass
     */
    public static function fatal(
        string $message,
        string $checkClass,
        string $url,
        ?array $config = null,
        ?array $details = null
    ): self
    {
        return new self(RemarkLevel::FATAL, $message, $checkClass, $url, $config, $details);
    }
}
