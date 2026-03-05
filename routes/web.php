<?php

use App\Http\Controllers\ContactController;
use Illuminate\Support\Facades\Route;

// Vitrine
Route::get('/', fn () => view('vitrine.index'))->name('home');
Route::get('/fonctionnalites', fn () => view('vitrine.fonctionnalites'))->name('fonctionnalites');
Route::get('/tarifs', fn () => view('vitrine.tarifs'))->name('tarifs');
Route::get('/contact', fn () => view('vitrine.contact'))->name('contact');
Route::post('/contact', [ContactController::class, 'send'])->name('contact.send');

// SPA catch-all (login, dashboard, etc.)
Route::get('/{any}', fn () => file_get_contents(base_path('frontend/dist/index.html')))
    ->where('any', '^(?!api|sanctum|build).*$')
    ->name('spa');
