<?php

namespace App\Services\Filters\Enums;

enum Component: string
{
    case text = 'text';
    case checkbox_group = 'checkbox_group';
    case combobox = 'combobox';
}
