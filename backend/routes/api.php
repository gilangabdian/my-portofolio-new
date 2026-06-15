<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ExperienceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ArtworkController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\VisitorController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\BlogController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| Public Routes (Dapat diakses siapa saja/Frontend Portfolio)
|--------------------------------------------------------------------------
 */
Route::post('/admin/login', [AuthController::class, 'login']);

Route::post('/visitors', [VisitorController::class, 'store']);

Route::get('/profile', [ProfileController::class, 'index']);
Route::get('/projects', [ProjectController::class, 'index']);
Route::get('/skills', [SkillController::class, 'index']); // Asumsi kamu buat SkillController
Route::get('/experiences', [ExperienceController::class, 'index']); // Asumsi kamu buat ExperienceController
Route::get('/certificates', [CertificateController::class, 'index']); // Asumsi kamu buat CertificateController
Route::get('/contacts', [ContactController::class, 'index']); // Asumsi kamu buat ContactController
Route::get('/artworks', [ArtworkController::class, 'index']);
Route::get('/photos', [PhotoController::class, 'index']);
Route::get('/blogs', [BlogController::class, 'indexPublic']);
Route::get('/blogs/{slug}', [BlogController::class, 'showPublic']);

// Show Single (Detail untuk Edit) - TAMBAHKAN INI
Route::get('/projects/{id}', [ProjectController::class, 'show']);
Route::get('/skills/{id}', [SkillController::class, 'show']);
Route::get('/experiences/{id}', [ExperienceController::class, 'show']);
Route::get('/certificates/{id}', [CertificateController::class, 'show']);
Route::get('/contacts/{id}', [ContactController::class, 'show']);
Route::get('/artworks/{id}', [ArtworkController::class, 'show']);
Route::get('/photos/{id}', [PhotoController::class, 'show']);

/*
|--------------------------------------------------------------------------
| Protected Routes (Hanya Admin - Butuh Token Bearer)
|--------------------------------------------------------------------------
 */
Route::middleware('auth:sanctum')->group(function () {
    Route::delete('/admin/logout', [AuthController::class, 'logout']);

    Route::get('/admin/visitors', [VisitorController::class, 'index']);
    Route::delete('/admin/visitors/{visitor}', [VisitorController::class, 'destroy']);
    Route::delete('/admin/visitors', [VisitorController::class, 'clearAll']);
    Route::get('/admin/visitors/count', [VisitorController::class, 'count']);

    // Profile (Update only)
    Route::post('/profile', [ProfileController::class, 'update']); // Menggunakan POST untuk support file upload dengan mudah

    // Projects CRUD
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::put('/projects/{id}', [ProjectController::class, 'update']); // Note: Laravel kadang butuh _method: PUT di FormData untuk file
    Route::delete('/projects/{id}', [ProjectController::class, 'destroy']);

    // Skills CRUD
    Route::post('/skills', [SkillController::class, 'store']);
    Route::put('/skills/{id}', [SkillController::class, 'update']);
    Route::delete('/skills/{id}', [SkillController::class, 'destroy']);

    // Experiences CRUD
    Route::post('/experiences', [ExperienceController::class, 'store']);
    Route::put('/experiences/{id}', [ExperienceController::class, 'update']);
    Route::delete('/experiences/{id}', [ExperienceController::class, 'destroy']);

    // Certificates CRUD
    Route::post('/certificates', [CertificateController::class, 'store']);
    Route::put('/certificates/{id}', [CertificateController::class, 'update']);
    Route::delete('/certificates/{id}', [CertificateController::class, 'destroy']);

    // Contacts CRUD
    Route::post('/contacts', [ContactController::class, 'store']);
    Route::put('/contacts/{id}', [ContactController::class, 'update']);
    Route::delete('/contacts/{id}', [ContactController::class, 'destroy']);

    // Artworks CRUD
    Route::post('/artworks', [ArtworkController::class, 'store']);
    Route::put('/artworks/{id}', [ArtworkController::class, 'update']);
    Route::delete('/artworks/{id}', [ArtworkController::class, 'destroy']);

    // Photos CRUD
    Route::post('/photos', [PhotoController::class, 'store']);
    Route::post('/photos/{id}', [PhotoController::class, 'update']);
    Route::delete('/photos/{id}', [PhotoController::class, 'destroy']);

    // Blogs CRUD
    Route::get('/admin/blogs', [BlogController::class, 'indexAdmin']);
    Route::get('/admin/blogs/{id}', [BlogController::class, 'showAdmin']);
    Route::post('/blogs', [BlogController::class, 'store']);
    Route::post('/blogs/upload-image', [BlogController::class, 'uploadImage']);
    Route::put('/blogs/{id}', [BlogController::class, 'update']);
    Route::post('/blogs/{id}', [BlogController::class, 'update']); // Fallback multipart if needed
    Route::delete('/blogs/{id}', [BlogController::class, 'destroy']);
});
