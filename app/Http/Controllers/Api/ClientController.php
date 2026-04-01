<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends BaseUserController
{
    public function __construct()
    {
        $this->model = Client::class;
        $this->resource = ClientResource::class;
    }
}
