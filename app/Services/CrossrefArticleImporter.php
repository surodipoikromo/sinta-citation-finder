<?php
namespace App\Services;

use App\Models\Article;
use App\Models\Journal;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CrossrefArticleImporter
{
    public function syncJournal(Journal $journal, int $limit = 100, ?int $fromYear = null): array
    {
        $issn = $journal->eissn ?: $journal->issn;
        if (!$issn) return ['fetched'=>0,'inserted'=>0,'updated'=>0,'skipped'=>1];
        $limit = max(1, min($limit, 1000));
        $stats = ['fetched'=>0,'inserted'=>0,'updated'=>0,'skipped'=>0];
        $remaining = $limit; $cursor = '*';

        while ($remaining > 0) {
            $rows = min(100, $remaining);
            $query = ['cursor'=>$cursor, 'rows'=>$rows, 'select'=>'DOI,title,author,published-print,published-online,created,URL,abstract,subject,type,container-title'];
            if ($fromYear) $query['filter'] = 'from-pub-date:'.$fromYear.'-01-01,type:journal-article';
            else $query['filter'] = 'type:journal-article';
            if (config('corpus.crossref.mailto')) $query['mailto'] = config('corpus.crossref.mailto');

            $url = rtrim(config('corpus.crossref.base_url'), '/').'/journals/'.rawurlencode(str_replace('-', '', $issn)).'/works';
            $response = Http::timeout(config('corpus.crossref.timeout'))->retry(2, 700)
                ->withHeaders(['User-Agent'=>config('corpus.crossref.user_agent')])->get($url, $query);
            if (!$response->successful()) throw new RuntimeException("Crossref HTTP {$response->status()} untuk {$journal->name} ({$issn}).");
            $message = $response->json('message', []);
            $items = $message['items'] ?? [];
            if (!$items) break;
            foreach ($items as $item) { $stats['fetched']++; $this->store($journal, $item, $stats); }
            $remaining -= count($items);
            if (count($items) < $rows) break;
            $next = $message['next-cursor'] ?? null; if (!$next || $next === $cursor) break; $cursor = $next;
            usleep(config('corpus.crossref.delay_ms') * 1000);
        }
        $journal->update(['synced_at'=>now()]);
        return $stats;
    }

    private function store(Journal $journal, array $item, array &$stats): void
    {
        $title = trim(strip_tags(html_entity_decode($item['title'][0] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        if ($title === '') { $stats['skipped']++; return; }
        $doi = trim((string)($item['DOI'] ?? '')) ?: null;
        $externalId = $doi ? mb_strtolower($doi) : sha1($journal->id.'|'.mb_strtolower($title));
        $abstract = $this->cleanAbstract($item['abstract'] ?? '');
        $authors = $this->authors($item['author'] ?? []);
        $year = $this->year($item);
        $keywords = !empty($item['subject']) ? implode('; ', array_filter($item['subject'])) : null;
        $payload = [
            'authors'=>$authors ?: 'Tidak tersedia', 'title'=>$title, 'year'=>$year, 'doi'=>$doi,
            'url'=>$item['URL'] ?? ($doi ? 'https://doi.org/'.$doi : null), 'abstract'=>$abstract,
            'keywords'=>$keywords, 'source'=>'crossref', 'external_id'=>$externalId, 'fetched_at'=>now(),
        ];
        $existing = Article::where('external_id', $externalId)->first();
        if (!$existing && $doi) $existing = Article::whereRaw('LOWER(doi) = ?', [mb_strtolower($doi)])->first();
        if (!$existing) $existing = Article::where('journal_id',$journal->id)->where('title',$title)->first();
        if ($existing) { $existing->update($payload); $stats['updated']++; }
        else { $journal->articles()->create($payload); $stats['inserted']++; }
    }

    private function cleanAbstract(string $value): string
    {
        if (!$value) return '';
        $value = preg_replace('/<jats:[^>]+>|<\/jats:[^>]+>/i', ' ', $value) ?? $value;
        return trim(preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'))) ?? '');
    }
    private function authors(array $authors): string
    {
        $names=[]; foreach ($authors as $a) { $name=trim(($a['given']??'').' '.($a['family']??'')); if ($name) $names[]=$name; }
        return implode(', ', $names);
    }
    private function year(array $item): ?int
    {
        foreach (['published-print','published-online','created'] as $key) {
            $parts=$item[$key]['date-parts'][0]??null; $year=(int)($parts[0]??0); if ($year>=1900 && $year<=(int)date('Y')+1) return $year;
        }
        return null;
    }
}
