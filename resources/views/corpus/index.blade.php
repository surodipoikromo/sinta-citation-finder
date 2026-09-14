@extends('layouts.app')
@section('title', 'Korpus — SINTA Citation Finder')
@section('content')
<div class="d-flex justify-content-between align-items-start mb-4">
    <div><h1 class="h3 mb-1">Korpus artikel</h1><p class="text-secondary mb-0">Bangun corpus otomatis dari jurnal SINTA + Crossref, atau impor CSV milik sendiri.</p></div>
    <a href="{{ route('corpus.template') }}" class="btn btn-outline-secondary">Unduh Template CSV</a>
</div>
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-secondary small">Artikel</div><div class="display-6">{{ number_format($articleCount) }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-secondary small">Jurnal</div><div class="display-6">{{ number_format($journalCount) }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-secondary small">Jurnal dari SINTA</div><div class="display-6">{{ number_format($sintaJournalCount) }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-secondary small">Artikel Crossref</div><div class="display-6">{{ number_format($crossrefArticleCount) }}</div></div></div></div>
</div>
<div class="card mb-4 border-primary-subtle">
    <div class="card-body p-4">
        <h2 class="h5">Bangun corpus otomatis</h2>
        <p class="text-secondary small mb-3">Jalankan dari terminal. Tahap pertama mengambil daftar jurnal, ISSN, dan level SINTA. Tahap kedua mengambil metadata artikel melalui Crossref berdasarkan ISSN.</p>
        <div class="mb-2"><code>php artisan corpus:bootstrap --pages=3 --journals=20 --per-journal=100</code></div>
        <p class="small mb-2">Atau jalankan per tahap:</p>
        <div class="mb-1"><code>php artisan sinta:discover --pages=3</code></div>
        <div><code>php artisan corpus:sync --journals=20 --per-journal=100</code></div>
        <div class="alert alert-warning small mt-3 mb-0">Crawler diberi jeda antar-request dan hanya ditujukan untuk data jurnal publik. Struktur SINTA dapat berubah; jika parser gagal, CSV importer tetap dapat digunakan.</div>
    </div>
</div>
<div class="card mb-4"><div class="card-body p-4"><h2 class="h5">Impor CSV</h2><p class="small text-secondary">Kolom wajib: <code>authors,title,journal,sinta_level,abstract</code>. Kolom opsional: <code>year,url,doi,keywords,subject_area,issn,eissn,journal_url</code>.</p><form method="post" action="{{ route('corpus.import') }}" enctype="multipart/form-data" class="row g-2">@csrf<div class="col-md-9"><input type="file" class="form-control" name="csv" accept=".csv,text/csv" required></div><div class="col-md-3"><button class="btn btn-success w-100">Impor Korpus</button></div></form></div></div>
<div class="card"><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th class="ps-3">Artikel</th><th>Jurnal</th><th>SINTA</th><th>Tahun</th><th>Sumber</th></tr></thead><tbody>@forelse($articles as $a)<tr><td class="ps-3"><div class="fw-semibold">{{ $a->title }}</div><div class="small text-secondary">{{ $a->authors }}</div></td><td>{{ $a->journal->name }}</td><td><span class="badge text-bg-success">S{{ $a->journal->sinta_level }}</span></td><td>{{ $a->year ?: '—' }}</td><td><span class="badge text-bg-light border">{{ $a->source ?: 'csv/demo' }}</span></td></tr>@empty<tr><td colspan="5" class="text-center text-secondary py-5">Korpus masih kosong.</td></tr>@endforelse</tbody></table></div>@if($articles->hasPages())<div class="p-3">{{ $articles->links() }}</div>@endif</div></div>
@if($articleCount > 0)<form action="{{ route('corpus.clear') }}" method="post" class="mt-3" onsubmit="return confirm('Kosongkan seluruh korpus?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Kosongkan Korpus</button></form>@endif
@endsection
