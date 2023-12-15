<?php

namespace App\Forms\Contracts;

use App\Fields\FormFieldCollection;

interface AppForm
{
    public function fields(): FormFieldCollection;
}
