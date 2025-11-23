<?php

namespace Syndicate\Inspector\Contracts;

use Illuminate\Database\Eloquent\Model;

interface Inspection
{
    /**
     * @return class-string<Check>[]
     */
    public function checks(): array;

    public function url(): string;

    public function shouldInspect(): bool;

    public function httpOptions(): array;

    public function model(): ?Model;
}
