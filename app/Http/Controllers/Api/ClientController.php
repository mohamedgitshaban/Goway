<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Models\Trip;
use Illuminate\Http\Request;

class ClientController extends BaseUserController
{
    public function __construct()
    {
        $this->model = Client::class;
        $this->resource = ClientResource::class;
        $this->with = array_merge(
            ['wallet', 'activeTrip'],
            array_map(fn ($relation) => "activeTrip.{$relation}", Trip::resourceRelations())
        );
    }
}
