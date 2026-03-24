<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DriverDocumentsExport implements FromCollection, WithHeadings
{
    protected $query;

    public function __construct($query)
    {
        // We pass the query builder from the controller
        $this->query = $query;
    }

    public function collection()
    {
        return $this->query->get()->map(function ($doc) {
            return [
                'id'            => $doc->id,
                'driver_name'   => $doc->driver?->name,
                'driver_phone'  => $doc->driver?->phone,
                'age'           => $doc->age,
                'trip_type'     => $doc->tripType?->name,
                'status'        => $doc->status,
                'reject_reason' => $doc->reject_reason,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'ID',
            'Driver Name',
            'Driver Phone',
            'Age',
            'Trip Type',
            'Status',
            'Reject Reason',
        ];
    }
}
