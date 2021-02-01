<?php

declare(strict_types=1);

namespace Orion\Testing;

use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Facades\DB;

trait InteractsWithJsonFields
{
    protected function castFieldsToJson(array $fields): array
    {
        return collect($fields)->map(function ($value) {
            if (is_array($value) || $value instanceof Jsonable) {
                return $this->castFieldToJson($value);
            }

            return $value;
        })->toArray();
    }

    protected function castFieldToJson($value)
    {
        if ($value instanceof Jsonable) {
            $value = $value->toJson();
        } else {
            $value = json_encode($value);
        }

        if (config('database.default') === 'mysql') {
            $value = DB::raw("CAST('{$value}' AS JSON)");
        }
        if (config('database.default') === 'pgsql') {
            $value = DB::raw("'{$value}'::jsonb");
        }

        return $value;
    }
}