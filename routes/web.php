<?php
use App\Http\Controllers\CorpusController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;
Route::get('/', [SearchController::class, 'index'])->name('search.index');
Route::post('/cari', [SearchController::class, 'search'])->name('search.run');
Route::get('/korpus', [CorpusController::class, 'index'])->name('corpus.index');
Route::post('/korpus/impor', [CorpusController::class, 'import'])->name('corpus.import');
Route::delete('/korpus', [CorpusController::class, 'clear'])->name('corpus.clear');
Route::get('/korpus/template.csv', [CorpusController::class, 'sampleCsv'])->name('corpus.template');
