<?php

namespace Syndicate\Inspector\Checks;

use LogicException;
use ReflectionClass;
use ReflectionProperty;
use Syndicate\Inspector\Contracts\Check;
use Syndicate\Inspector\DTOs\CheckResult;
use Syndicate\Inspector\DTOs\Finding;
use Syndicate\Inspector\DTOs\InspectionContext;
use Syndicate\Inspector\Enums\RemarkLevel;

abstract class BaseCheck implements Check
{
    protected ?InspectionContext $context = null;

    final public function apply(InspectionContext $context): CheckResult
    {
        $this->context = $context;

        try {
            return $this->applyCheck();
        } finally {
            $this->context = null;
        }
    }

    abstract protected function applyCheck(): CheckResult;

    protected function success(string $message, ?array $details = null): CheckResult
    {
        return $this->result([
            $this->finding(RemarkLevel::SUCCESS, $message, $details)
        ]);
    }

    protected function result(array $findings): CheckResult
    {
        return CheckResult::from($findings);
    }

    protected function finding(
        RemarkLevel $level,
        string      $message,
        ?array      $details = null
    ): Finding
    {
        if ($this->context === null) {
            throw new LogicException('Cannot create a finding outside the lifecycle of a check run.');
        }

        return new Finding(
            level: $level,
            message: $message,
            checkClass: static::class,
            url: $this->context->inspection->url(),
            config: $this->getConfig(),
            details: $details
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function getConfig(): array
    {
        $config = [];
        $reflection = new ReflectionClass($this);

        $properties = $reflection->getProperties(ReflectionProperty::IS_PROTECTED);

        foreach ($properties as $property) {
            if ($property->isStatic() || !$property->isInitialized($this) || $property->getName() === 'context') {
                continue;
            }
            $config[$property->getName()] = $property->getValue($this);
        }

        return $config;
    }

    protected function info(string $message, ?array $details = null): CheckResult
    {
        return $this->result([
            $this->finding(RemarkLevel::INFO, $message, $details)
        ]);
    }

    protected function notice(string $message, ?array $details = null): CheckResult
    {
        return $this->result([
            $this->finding(RemarkLevel::NOTICE, $message, $details)
        ]);
    }

    protected function warning(string $message, ?array $details = null): CheckResult
    {
        return $this->result([
            $this->finding(RemarkLevel::WARNING, $message, $details)
        ]);
    }

    protected function error(string $message, ?array $details = null): CheckResult
    {
        return $this->result([
            $this->finding(RemarkLevel::ERROR, $message, $details)
        ]);
    }

    protected function fatal(string $message, ?array $details = null): CheckResult
    {
        return $this->result([
            $this->finding(RemarkLevel::FATAL, $message, $details)
        ]);
    }
}
