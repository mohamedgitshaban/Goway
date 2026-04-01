<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\FromCollection;

class UsersExport implements FromCollection
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function collection()
    {
        return $this->model->all();
    }
}
