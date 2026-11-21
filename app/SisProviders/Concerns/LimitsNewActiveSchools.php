<?php

namespace App\SisProviders\Concerns;

trait LimitsNewActiveSchools
{
    /**
     * In the cloud only `school_limit` schools may be active. New schools fill the open
     * slots in school number order and the rest come in inactive. The upsert never
     * updates `active`, so existing schools keep what the district chose.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    protected function limitNewActiveSchools(array $rows): array
    {
        $limit = $this->tenant->school_limit;

        if (! config('app.cloud') || $limit === null) {
            return $rows;
        }

        $existing = $this->tenant->schools()->pluck('sis_id');
        $openSlots = max(0, $limit - $this->tenant->schools()->where('active', true)->count());

        return array_map(function (array $row) use ($existing, &$openSlots) {
            $isNew = ! $existing->contains($row['sis_id']);
            $row['active'] = ! $isNew || $openSlots-- > 0;

            return $row;
        }, $rows);
    }
}
