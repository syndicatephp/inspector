<?php

namespace Syndicate\Inspector\Inspections;

use Illuminate\Database\Eloquent\Model;
use Syndicate\Inspector\Checklists;
use Syndicate\Inspector\Contracts\Inspection;

class UrlInspection implements Inspection
{
    public function __construct(protected string $url)
    {
    }

    public function checks(): array
    {
        return [
            ...Checklists\BaselineChecklist::checks(),
            ...Checklists\SeoChecklist::checks(),
            ...Checklists\PerformanceChecklist::checks(),
            ...Checklists\ContentChecklist::checks(),
        ];
    }

    public function url(): string
    {
        return $this->url;
    }

    public function model(): ?Model
    {
        return null;
    }

    public function shouldInspect(): bool
    {
        return true;
    }

    public function httpOptions(): array
    {
        return [];
    }
}
