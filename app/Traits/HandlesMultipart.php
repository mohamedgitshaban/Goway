<?php

namespace App\Traits;

use App\Services\MultipartParser;
use Illuminate\Http\Request;

trait HandlesMultipart
{
    protected function handleMultipart(Request $request)
    {
        
        MultipartParser::parse($request);
    }
}