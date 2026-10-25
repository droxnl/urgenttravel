<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PageResource;
use Illuminate\Http\JsonResponse;

class SiteController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'settings' => PageResource::publicSettings(),
        ]);
    }
}
