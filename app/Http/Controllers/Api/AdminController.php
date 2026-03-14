<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminResource;
use App\Models\Admin;
use Illuminate\Http\Request;

class AdminController extends BaseUserController
{
    public function __construct()
    {
        $this->model = Admin::class;
        $this->resource = AdminResource::class;
    }
}
