<?php

namespace App\Notifications\Traits;

trait FormatsSubject
{
    public function formatSubject(string $subject): string
    {
        return '['.config('app.name').'] '.$subject;
    }
}
