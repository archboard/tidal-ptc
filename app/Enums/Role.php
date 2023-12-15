<?php

namespace App\Enums;

enum Role: string
{
    case DISTRICT_ADMIN = 'district admin';
    case SCHOOL_ADMIN = 'school admin';
    case GUARDIAN = 'guardian';
    case TEACHER = 'teacher';
    case STAFF = 'staff';
}
