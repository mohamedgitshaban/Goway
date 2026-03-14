<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DriverResource;
use App\Models\Driver;
use Illuminate\Http\Request;

class DriverController extends BaseUserController
{
    public function __construct()
    {
        $this->model = Driver::class;
        $this->resource = DriverResource::class;
    }
}
