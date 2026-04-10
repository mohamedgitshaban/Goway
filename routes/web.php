<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::get('storage/public/{path}', function ($path) {
	$fullPath = storage_path('app/public/' . $path);

	if (! file_exists($fullPath)) {
		return response()->json(['status' => false, 'message' => 'File not found'], 404);
	}

	$mime = mime_content_type($fullPath) ?: 'application/octet-stream';
	return response()->file($fullPath, ['Content-Type' => $mime]);
})->where('path', '.*');