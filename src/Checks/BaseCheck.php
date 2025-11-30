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
    protected string $checklist = 'Misc';
    protected string|null $nameOverride = null;

    public static function make(): self
    {
        return new static();
    }

    public function checklist(string $checklist): self
    {
        $this->checklist = $checklist;
        return $this;
    }

    public function getChecklist(): string
    {
        return $this->checklist;
    }

    public function name(string $name): self
    {
        $this->nameOverride = $name;
        return $this;
    }

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

    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
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

    public function getName(): string
    {
        return $this->nameOverride ?? class_basename($this);
    }

    protected function success(string $message, ?array $details = null): CheckResult
    {
        return $this->result([
            $this->finding(RemarkLevel::SUCCESS, $message, $details)
        ]);
    }

    protected function result(array $findings): CheckResult
    {
        return new CheckResult($this, collect($findings));
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
            details: $details
        );
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
