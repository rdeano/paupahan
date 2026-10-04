<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\Owner;
use App\Http\Controllers\OwnerProfileController;
use App\Http\Controllers\StreetLookupController;
use Illuminate\Support\Facades\Route;

// Public site
Route::get('/', [ListingController::class, 'index'])->name('home');
Route::get('/listings/{property}', [ListingController::class, 'show'])->name('listings.show');
Route::get('/owners/{owner}', [OwnerProfileController::class, 'show'])->name('owners.show');

// Owner workspace
Route::middleware('auth')->prefix('owner')->name('owner.')->group(function () {
    Route::get('/', [Owner\PropertyController::class, 'index'])->name('dashboard');

    Route::get('/properties/create', [Owner\PropertyController::class, 'create'])->name('properties.create');
    Route::post('/properties', [Owner\PropertyController::class, 'store'])->name('properties.store');
    Route::get('/properties/{property}/edit', [Owner\PropertyController::class, 'edit'])->name('properties.edit');
    Route::put('/properties/{property}', [Owner\PropertyController::class, 'update'])->name('properties.update');
    Route::patch('/properties/{property}/status', [Owner\PropertyController::class, 'updateStatus'])->name('properties.status');
    Route::delete('/properties/{property}', [Owner\PropertyController::class, 'destroy'])->name('properties.destroy');

    Route::post('/properties/{property}/photos', [Owner\PropertyPhotoController::class, 'store'])->name('photos.store');
    Route::patch('/properties/{property}/photos/order', [Owner\PropertyPhotoController::class, 'reorder'])->name('photos.reorder');
    Route::patch('/properties/{property}/photos/{photo}/cover', [Owner\PropertyPhotoController::class, 'cover'])->name('photos.cover');
    Route::delete('/properties/{property}/photos/{photo}', [Owner\PropertyPhotoController::class, 'destroy'])->name('photos.destroy');

    Route::get('/profile', [Owner\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [Owner\ProfileController::class, 'update'])->name('profile.update');

    Route::get('/barangays/{barangay}/streets', StreetLookupController::class)->name('streets.lookup');
});

// Platform admin
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    Route::get('/owners', [Admin\OwnerController::class, 'index'])->name('owners.index');
    Route::patch('/owners/{owner}/status', [Admin\OwnerController::class, 'updateStatus'])->name('owners.status');

    Route::get('/properties', [Admin\PropertyController::class, 'index'])->name('properties.index');
    Route::patch('/properties/{property}/unpublish', [Admin\PropertyController::class, 'unpublish'])->name('properties.unpublish');
    Route::delete('/properties/{property}', [Admin\PropertyController::class, 'destroy'])->name('properties.destroy');

    Route::get('/streets', [Admin\StreetController::class, 'index'])->name('streets.index');
    Route::patch('/streets/{street}/approve', [Admin\StreetController::class, 'approve'])->name('streets.approve');
    Route::delete('/streets/{street}', [Admin\StreetController::class, 'destroy'])->name('streets.destroy');
});
