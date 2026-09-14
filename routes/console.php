<?php
use Illuminate\Support\Facades\Artisan;
Artisan::command('citation:stats', function () {
    $this->info('Jurnal: '.\App\Models\Journal::count());
    $this->info('Artikel: '.\App\Models\Article::count());
})->purpose('Menampilkan statistik corpus pencarian.');
