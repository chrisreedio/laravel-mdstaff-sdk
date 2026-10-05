<?php

declare(strict_types=1);

namespace ChrisReedIO\MDStaff\Queries;

use ChrisReedIO\MDStaff\Enums\QuerySource;
use DateTimeInterface;

/**
 * Field lists, filters, sorts, and settings for the provider-level query
 * sources, following the MDStaff Query API documentation.
 */
final class ProviderQueries
{
    /**
     * @return array<int, QuerySource>
     */
    public static function sources(): array
    {
        return [
            QuerySource::Demographic,
            QuerySource::Appointment,
            QuerySource::Address,
            QuerySource::Reference,
            QuerySource::BoardCertification,
            QuerySource::ProviderFile,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function fields(QuerySource $source): array
    {
        return match ($source) {
            QuerySource::Demographic => [
                'ProviderID', 'FirstName', 'LastName', 'FormalNameWithDegree',
                'FormattedNameWithDegree', 'AcceptNewPatient', 'NPI', 'GenderID.Code',
                'CellPhone', 'Email', 'LastUpdated',
                'LanguageID_1', 'LanguageID_1.Description', 'LanguageID_2', 'LanguageID_2.Description',
                'LanguageID_3', 'LanguageID_3.Description', 'LanguageID_4', 'LanguageID_4.Description',
                'LanguageID_5', 'LanguageID_5.Description',
                'SpecialtyID_1', 'SpecialtyID_1.Description', 'SpecialtyID_2', 'SpecialtyID_2.Description',
                'SpecialtyID_3', 'SpecialtyID_3.Description', 'SpecialtyID_4', 'SpecialtyID_4.Description',
            ],
            QuerySource::Appointment => [
                'AppointmentID', 'ProviderID', 'FacilityID', 'Archived', 'OnStaff', 'OnTheWeb',
                'IsPrimary', 'StatusID', 'StatusID.Description', 'PrimaryAddressID', 'SecondaryAddressID',
                'DepartmentID_1', 'DepartmentID_1.Description', 'DepartmentID_2', 'DepartmentID_2.Description',
                'DepartmentID_3', 'DepartmentID_3.Description', 'LastUpdated',
            ],
            QuerySource::Address => [
                'AddressID', 'ProviderID', 'AddressType', 'Location', 'Address', 'Address2', 'City',
                'State', 'Zip', 'Telephone', 'Fax', 'InUse', 'Publish', 'LastUpdated',
                'MondayHoursFrom', 'MondayHoursTo', 'TuesdayHoursFrom', 'TuesdayHoursTo',
                'WednesdayHoursFrom', 'WednesdayHoursTo', 'ThursdayHoursFrom', 'ThursdayHoursTo',
                'FridayHoursFrom', 'FridayHoursTo', 'SaturdayHoursFrom', 'SaturdayHoursTo',
                'SundayHoursFrom', 'SundayHoursTo',
            ],
            QuerySource::Reference => [
                'ReferenceID', 'ProviderID', 'ReferenceSourceID', 'ReferenceSourceID.Name',
                'ReferenceType', 'ReferenceStatusID', 'ReferenceStatusID.Description',
                'DegreeEarnedID', 'DegreeEarnedID.Description', 'InUse', 'StartDate', 'EndDate',
                'Subject', 'SourceCity', 'SourceState', 'SourceCountryID',
                'SourceCountryID.Description',
            ],
            QuerySource::BoardCertification => [
                'BoardCertificationID', 'ProviderID', 'SpecialtyBoardID', 'SpecialtyBoard.Name',
                'SpecialtyBoard.City', 'SpecialtyBoard.State', 'SpecialtyID', 'SpecialtyID.Description',
                'CertificationStatusID', 'CertificationStatusID.Description', 'InUse', 'IsPrimary',
                'InitialCertificationDate', 'ExpirationDate', 'Lifetime', 'LastUpdated',
            ],
            QuerySource::ProviderFile => [
                'Uid', 'ProviderID', 'FileDescription', 'FileTypeID', 'InUse', 'DateUploaded',
            ],
            default => [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    public static function filter(QuerySource $source, ?string $providerId = null): array
    {
        $filter = $providerId === null ? [] : ['ProviderID' => $providerId];

        if ($source === QuerySource::Address) {
            $filter['AddressType'] = ['Primary', 'Office', 'Rural', 'ASC', 'PSA', 'AltOffice'];
            $filter['InUse'] = true;
        }

        if ($source === QuerySource::Reference) {
            $filter['ReferenceType'] = [
                'Medical Education', 'Undergraduate', 'Graduate School', 'Internship', 'Residency', 'Fellowship',
            ];
            $filter['InUse'] = true;
        }

        if ($source === QuerySource::BoardCertification) {
            $filter['InUse'] = true;
        }

        if ($source === QuerySource::ProviderFile) {
            $filter['InUse'] = true;
            $filter['FileDescription'] = self::imageFileDescriptionFilters();
        }

        return $filter;
    }

    /**
     * @return array<int, array<string, 'asc'|'desc'>>
     */
    public static function sort(QuerySource $source): array
    {
        return $source === QuerySource::ProviderFile
            ? [['InUse' => 'desc'], ['DateUploaded' => 'desc']]
            : [];
    }

    /**
     * @return array<int, array{type: 'search', values: array{string}}>
     */
    public static function imageFileDescriptionFilters(): array
    {
        return array_map(
            static fn (string $extension): array => [
                'type' => 'search',
                'values' => ["%.{$extension}"],
            ],
            ['jpg', 'jpeg', 'png'],
        );
    }

    /**
     * @return array<string, bool>
     */
    public static function settings(): array
    {
        return [
            'IncludeArchivedProviders' => false,
            'IncludeApplicants' => false,
        ];
    }

    /**
     * An inclusive, date-only LastUpdated range for incremental pulls.
     *
     * @return array{LastUpdated: array{array{type: 'between', values: array{string, string}}}}
     */
    public static function lastUpdatedBetween(DateTimeInterface $from, DateTimeInterface $to): array
    {
        return [
            'LastUpdated' => [[
                'type' => 'between',
                'values' => [$from->format('m/d/Y'), $to->format('m/d/Y')],
            ]],
        ];
    }
}
