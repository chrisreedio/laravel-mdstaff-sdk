<?php

declare(strict_types=1);

namespace ChrisReedIO\MDStaff\Enums;

enum QuerySource: string
{
    case AdHoc = 'AdHocAPI';
    case Address = 'Address';
    case Appointment = 'Appointment';
    case BoardCertification = 'BoardCertification';
    case Demographic = 'Demographic';
    case Education = 'Education';
    case LookUp = 'LookUp';
    case ProviderFile = 'ProviderFile';
    case Reference = 'Reference';
    case ReferenceSource = 'ReferenceSource';

    /**
     * The field that uniquely identifies a record returned by this source.
     */
    public function primaryKey(): ?string
    {
        return match ($this) {
            self::Address => 'AddressID',
            self::Appointment => 'AppointmentID',
            self::BoardCertification => 'BoardCertificationID',
            self::Demographic => 'ProviderID',
            self::Education => 'ReferenceID',
            self::LookUp => 'LookUpID',
            self::ProviderFile => 'Uid',
            self::Reference => 'ReferenceID',
            self::ReferenceSource => 'ReferenceSourceID',
            self::AdHoc => null,
        };
    }
}
