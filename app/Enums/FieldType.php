<?php

namespace App\Enums;

enum FieldType: string
{
    case checkbox = 'CheckboxField';
    case checkbox_list = 'CheckboxListField';
    case date_picker = 'DatePickerField';
    case email = 'EmailField';
    case input = 'InputField';
    case number = 'NumberField';
    case combobox = 'ObjectComboboxField';
    case password = 'PasswordField';
    case phone = 'PhoneField';
    case radio = 'RadioField';
    case select = 'SelectField';
    case textarea = 'TextareaField';
}
