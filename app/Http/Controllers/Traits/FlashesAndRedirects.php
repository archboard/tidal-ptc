<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Http\RedirectResponse;

trait FlashesAndRedirects
{
    protected function flashSession(string $message, string $level = 'success'): static
    {
        session()->flash($level, $message);

        return $this;
    }

    protected function flashAndBack(): RedirectResponse
    {
        $this->flashSession(__('Saved successfully.'));

        return back();
    }
}
