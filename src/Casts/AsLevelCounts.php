<?php

namespace Syndicate\Inspector\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Syndicate\Inspector\DTOs\LevelCounts;

class AsLevelCounts implements CastsAttributes
{
    /**
     * Cast the stored JSON string into a LevelCounts DTO.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?LevelCounts
    {
        if ($value === null) {
            return LevelCounts::empty();
        }

        $data = json_decode($value, true);

        return LevelCounts::fromArray($data);
    }

    /**
     * Prepare the LevelCounts DTO for storage as a JSON string.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof LevelCounts) {
            throw new InvalidArgumentException('The given value is not a LevelCounts instance.');
        }

        return json_encode($value->toArray());
    }
}
