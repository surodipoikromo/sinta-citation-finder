<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SINTA Citation Finder')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body{background:#f7f8fa}.brand-dot{width:10px;height:10px;border-radius:50%;display:inline-block;background:#198754;margin-right:.45rem}.score{font-size:1.25rem;font-weight:700}.excerpt{border-left:4px solid #dee2e6;padding-left:1rem}.match{font-size:.82rem}.card{border:0;box-shadow:0 .125rem .6rem rgba(0,0,0,.06)}.navbar{box-shadow:0 1px 8px rgba(0,0,0,.05)}
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg bg-white mb-4">
  <div class="container">
    <a class="navbar-brand fw-semibold" href="{{ route('search.index') }}"><span class="brand-dot"></span>SINTA Citation Finder</a>
    <div class="navbar-nav ms-auto flex-row gap-3">
      <a class="nav-link" href="{{ route('search.index') }}">Pencarian</a>
      <a class="nav-link" href="{{ route('corpus.index') }}">Korpus</a>
    </div>
  </div>
</nav>
<main class="container pb-5">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><strong>Ada yang perlu diperbaiki:</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @yield('content')
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
