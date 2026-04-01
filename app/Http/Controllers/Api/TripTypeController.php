<?php

namespace App\Http\Controllers\Api;
use App\Http\Resources\TripTypeResource;

use App\Models\TripType;

class TripTypeController extends BaseController
{
      public function __construct()
    {
        $this->model = TripType::class;
        $this->resource = TripTypeResource::class;
    }
}
