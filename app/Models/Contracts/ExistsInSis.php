<?php

namespace App\Models\Contracts;

interface ExistsInSis
{
    public function syncFromSis(): static;
}
