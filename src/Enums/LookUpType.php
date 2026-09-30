<?php

declare(strict_types=1);

namespace ChrisReedIO\MDStaff\Enums;

enum LookUpType: string
{
    case Country = 'Country';
    case Degree = 'Degree';
    case Department = 'Department';
    case Language = 'Language';
    case Location = 'Location';
    case ResidencyTitle = 'ReferenceStatus';
    case Specialty = 'Specialty';
}
