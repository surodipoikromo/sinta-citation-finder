<?php
return [
    'sinta' => [
        'base_url' => env('SINTA_BASE_URL', 'https://sinta.kemdiktisaintek.go.id'),
        'delay_ms' => (int) env('SINTA_CRAWL_DELAY_MS', 1200),
        'timeout' => (int) env('SINTA_HTTP_TIMEOUT', 20),
    ],
    'crossref' => [
        'base_url' => env('CROSSREF_BASE_URL', 'https://api.crossref.org'),
        'mailto' => env('CROSSREF_MAILTO'),
        'user_agent' => env('CROSSREF_USER_AGENT', 'SintaCitationFinder/1.1 (Laravel; academic portfolio project)'),
        'timeout' => (int) env('CROSSREF_HTTP_TIMEOUT', 25),
        'delay_ms' => (int) env('CROSSREF_DELAY_MS', 250),
    ],
];
