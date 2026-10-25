<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    public function index(): JsonResponse
    {
        $directory = public_path('images/uploads');
        $files = collect(is_dir($directory) ? scandir($directory) : [])
            ->filter(fn (string $file) => $file !== '.' && $file !== '..')
            ->filter(fn (string $file) => preg_match('/\.(jpe?g|png|webp|gif|svg)$/i', $file))
            ->map(function (string $file) use ($directory) {
                $path = $directory.DIRECTORY_SEPARATOR.$file;
                $dimensions = @getimagesize($path);

                return [
                    'name' => $file,
                    'url' => '/images/uploads/'.rawurlencode($file),
                    'size' => filesize($path),
                    'width' => $dimensions[0] ?? null,
                    'height' => $dimensions[1] ?? null,
                    'modified' => filemtime($path),
                ];
            })
            ->sortByDesc('modified')
            ->values();

        return response()->json(['media' => $files]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,gif,svg'],
        ]);

        $file = $request->file('image');
        $base = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $name = ($base ?: 'image').'-'.Str::lower(Str::random(6)).'.'.$file->getClientOriginalExtension();
        $file->move(public_path('images/uploads'), $name);

        return response()->json([
            'url' => '/images/uploads/'.$name,
            'name' => $name,
        ], 201);
    }
}
