@extends('layouts.app')
@section('title', 'Cari Referensi — SINTA Citation Finder')
@section('content')
<div class="row justify-content-center">
  <div class="col-xl-10">
    <div class="mb-4">
      <h1 class="h3 mb-2">Cari kandidat referensi SINTA</h1>
      <p class="text-secondary mb-0">Masukkan satu kalimat akademik. Sistem akan merangking artikel berdasarkan kecocokan istilah, frasa, cakupan konsep, serta bobot judul, kata kunci, dan abstrak.</p>
    </div>
    <div class="card mb-4"><div class="card-body p-4">
      <form action="{{ route('search.run') }}" method="post">@csrf
        <label for="q" class="form-label fw-semibold">Kalimat yang ingin dicarikan referensinya</label>
        <textarea class="form-control form-control-lg" id="q" name="q" rows="3" maxlength="500" placeholder="Contoh: Kualitas sistem berpengaruh terhadap kepuasan pengguna.">{{ old('q', $query ?? '') }}</textarea>
        <div class="row g-3 mt-1">
          <div class="col-md-3"><label class="form-label">Peringkat SINTA</label><select name="sinta" class="form-select"><option value="">Semua SINTA</option>@for($i=1;$i<=6;$i++)<option value="{{ $i }}" @selected(($filters['sinta'] ?? '') == $i)>SINTA {{ $i }}</option>@endfor</select></div>
          <div class="col-md-3"><label class="form-label">Tahun mulai</label><input type="number" min="1900" max="{{ date('Y') }}" name="year_from" class="form-control" value="{{ $filters['year_from'] ?? '' }}" placeholder="mis. 2020"></div>
          <div class="col-md-3"><label class="form-label">Jumlah hasil</label><select name="limit" class="form-select">@foreach([5,10,20] as $n)<option value="{{ $n }}" @selected(($filters['limit'] ?? 10) == $n)>{{ $n }} hasil</option>@endforeach</select></div>
          <div class="col-md-3 d-flex align-items-end"><button class="btn btn-success w-100">Cari Referensi</button></div>
        </div>
      </form>
      <div class="small text-secondary mt-3">Korpus saat ini: <strong>{{ number_format($stats['articles']) }}</strong> artikel dari <strong>{{ number_format($stats['journals']) }}</strong> jurnal. <a href="{{ route('corpus.index') }}">Kelola korpus</a></div>
    </div></div>

    @if(is_array($results))
      <div class="d-flex justify-content-between align-items-center mb-3"><div><h2 class="h5 mb-1">Hasil pencarian</h2><div class="small text-secondary">Mencari pada {{ number_format($results['documents']) }} artikel. Istilah aktif: {{ implode(', ', $results['terms']) ?: '—' }}@if(!empty($results['concepts'])) · Konsep: {{ implode(' · ', $results['concepts']) }}@endif</div></div><span class="badge text-bg-light">{{ count($results['results']) }} hasil</span></div>
      @if(($results['mode'] ?? null) === 'fallback')
        <div class="alert alert-warning"><strong>Belum ditemukan kecocokan konsep yang kuat.</strong> Sistem menampilkan kandidat dengan kecocokan parsial terbaik. Gunakan hasil ini sebagai petunjuk pencarian dan verifikasi isi artikel sebelum mengutip.</div>
      @endif
      @forelse($results['results'] as $result)
        @php($a = $result['article'])
        <div class="card mb-3"><div class="card-body p-4">
          <div class="d-flex justify-content-between gap-3 align-items-start">
            <div>
              <div class="d-flex gap-2 flex-wrap mb-2"><span class="badge text-bg-success">SINTA {{ $a->journal->sinta_level }}</span>@if($a->year)<span class="badge text-bg-light">{{ $a->year }}</span>@endif @if($a->journal->subject_area)<span class="badge text-bg-light">{{ $a->journal->subject_area }}</span>@endif @if(($result['match_quality'] ?? '') === 'partial')<span class="badge text-bg-warning">Kecocokan parsial</span>@elseif(($result['match_quality'] ?? '') === 'strong')<span class="badge text-bg-primary">Kecocokan kuat</span>@endif</div>
              <h3 class="h5 mb-1">{{ $a->title }}</h3>
              <div class="text-secondary">{{ $a->authors }}</div><div class="small text-secondary mt-1">{{ $a->journal->name }}</div>
            </div>
            <div class="text-end text-nowrap"><div class="score text-success">{{ number_format($result['score'],1) }}%</div><div class="small text-secondary">relevansi</div></div>
          </div>
          <hr>
          <div class="small fw-semibold mb-2">Potongan teks relevan</div><div class="excerpt mb-3">{{ $result['excerpt'] }}</div>
          @if(!empty($result['matched_concepts']))<div class="mb-2"><span class="small text-secondary me-2">Konsep cocok:</span>@foreach($result['matched_concepts'] as $concept)<span class="badge rounded-pill text-bg-success-subtle border border-success-subtle text-success-emphasis me-1">{{ $concept }}</span>@endforeach</div>@endif
          @if($result['matched_terms'])<div class="mb-3"><span class="small text-secondary me-2">Istilah cocok:</span>@foreach($result['matched_terms'] as $term)<span class="badge rounded-pill text-bg-light border match me-1">{{ $term }}</span>@endforeach</div>@endif
          <div class="d-flex gap-2 flex-wrap">
            @if($a->url)<a class="btn btn-sm btn-outline-success" href="{{ $a->url }}" target="_blank" rel="noopener">Buka Artikel</a>@endif
            @if($a->doi)<a class="btn btn-sm btn-outline-secondary" href="https://doi.org/{{ preg_replace('#^https?://(dx\.)?doi\.org/#i','',$a->doi) }}" target="_blank" rel="noopener">DOI</a><span class="small text-secondary align-self-center">{{ $a->doi }}</span>@endif
          </div>
        </div></div>
      @empty
        <div class="alert alert-warning">Belum ada artikel yang cocok. Coba gunakan istilah yang lebih spesifik atau tambahkan data ke korpus.</div>
      @endforelse
      <div class="alert alert-light border small"><strong>Catatan akademik:</strong> skor relevansi adalah bantuan pencarian, bukan bukti bahwa artikel pasti mendukung klaim. Baca artikel asli sebelum mengutip dan jangan menganggap potongan abstrak sebagai kutipan langsung dari bagian hasil.</div>
    @endif
  </div>
</div>
@endsection
