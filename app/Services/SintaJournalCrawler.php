<?php
namespace App\Services;

use App\Models\Journal;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SintaJournalCrawler
{
    public function crawl(int $pages = 1, ?int $onlyLevel = null, ?callable $progress = null): array
    {
        $pages = max(1, min($pages, 200));
        $stats = ['pages' => 0, 'found' => 0, 'inserted' => 0, 'updated' => 0, 'skipped' => 0];

        for ($page = 1; $page <= $pages; $page++) {
            $url = rtrim(config('corpus.sinta.base_url'), '/').'/journals/index/?page='.$page;
            $response = Http::timeout(config('corpus.sinta.timeout'))
                ->retry(2, 800)
                ->withHeaders(['User-Agent' => config('corpus.crossref.user_agent')])
                ->get($url);
            if (!$response->successful()) {
                throw new RuntimeException("SINTA mengembalikan HTTP {$response->status()} pada halaman {$page}.");
            }
            $rows = $this->parse($response->body(), $url);
            $stats['pages']++;
            $stats['found'] += count($rows);

            foreach ($rows as $row) {
                if ($onlyLevel && $row['sinta_level'] !== $onlyLevel) { $stats['skipped']++; continue; }
                if (!$row['name'] || !$row['sinta_level'] || (!$row['issn'] && !$row['eissn'])) { $stats['skipped']++; continue; }
                $query = Journal::where('name', $row['name'])->where('sinta_level', $row['sinta_level']);
                $existing = $query->first();
                $payload = [
                    'issn' => $row['issn'], 'eissn' => $row['eissn'], 'subject_area' => $row['subject_area'],
                    'website_url' => $row['website_url'], 'source' => 'sinta', 'source_url' => $row['source_url'], 'synced_at' => now(),
                ];
                if ($existing) { $existing->update($payload); $stats['updated']++; }
                else { Journal::create(array_merge(['name' => $row['name'], 'sinta_level' => $row['sinta_level']], $payload)); $stats['inserted']++; }
            }
            if ($progress) $progress($page, count($rows), $stats);
            if ($page < $pages) usleep(config('corpus.sinta.delay_ms') * 1000);
        }
        return $stats;
    }

    /** @return array<int,array<string,mixed>> */
    public function parse(string $html, string $sourceUrl = ''): array
    {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        if (!@$dom->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR)) return [];
        $xpath = new DOMXPath($dom);

        // SINTA has changed class names over time. First locate compact containers that contain ISSN + Sx Accredited.
        $candidates = [];
        foreach ($xpath->query('//*[contains(normalize-space(.), "P-ISSN") and contains(normalize-space(.), "Accredited")]') as $node) {
            if (!$node instanceof DOMElement) continue;
            $text = $this->clean($node->textContent);
            if (strlen($text) < 80 || strlen($text) > 3500) continue;
            $parentText = $node->parentNode instanceof DOMElement ? $this->clean($node->parentNode->textContent) : '';
            if ($parentText && strlen($parentText) < 3500 && substr_count($parentText, 'P-ISSN') <= 2) $node = $node->parentNode;
            $hash = md5($this->clean($node->textContent));
            $candidates[$hash] = $node;
        }

        $results = [];
        foreach ($candidates as $node) {
            $text = $this->clean($node->textContent);
            if (!preg_match('/S([1-6])\s+Accredited/i', $text, $mLevel)) continue;
            preg_match('/P-ISSN\s*:\s*([0-9Xx\-]{4,12})/i', $text, $mP);
            preg_match('/E-ISSN\s*:\s*([0-9Xx\-]{4,12})/i', $text, $mE);
            preg_match('/Subject\s+Area\s*:\s*(.+?)(?=S[1-6]\s+Accredited|Scopus\s+Indexed|Garuda\s+Indexed|Impact|$)/i', $text, $mSub);

            $links = $xpath->query('.//a', $node);
            $name = null; $website = null; $profile = null;
            foreach ($links as $a) {
                if (!$a instanceof DOMElement) continue;
                $label = trim($this->clean($a->textContent));
                $href = trim($a->getAttribute('href'));
                if (!$href) continue;
                if (str_contains($href, '/journals/profile/') || str_contains($href, '/journals/detail/')) {
                    $profile = $this->absolute($href); if ($label && !$this->genericLink($label)) $name ??= $label;
                }
                if (preg_match('/website/i', $label)) $website = $href;
            }
            if (!$name) {
                // Prefer heading/title elements in the card, then first meaningful anchor.
                foreach ($xpath->query('.//*[self::h2 or self::h3 or self::h4 or self::h5 or contains(@class,"title")]', $node) as $titleNode) {
                    $candidate = trim($this->clean($titleNode->textContent));
                    if (mb_strlen($candidate) >= 4 && mb_strlen($candidate) <= 300) { $name = $candidate; break; }
                }
            }
            if (!$name) {
                foreach ($links as $a) {
                    $candidate = trim($this->clean($a->textContent));
                    if ($candidate && !$this->genericLink($candidate) && mb_strlen($candidate) > 4) { $name = $candidate; break; }
                }
            }
            if (!$name) continue;

            $key = mb_strtolower($name).'|'.$mLevel[1];
            $results[$key] = [
                'name' => $name,
                'sinta_level' => (int)$mLevel[1],
                'issn' => $this->normalizeIssn($mP[1] ?? null),
                'eissn' => $this->normalizeIssn($mE[1] ?? null),
                'subject_area' => isset($mSub[1]) ? trim($mSub[1], " ,") : null,
                'website_url' => $website,
                'source_url' => $profile ?: $sourceUrl,
            ];
        }
        return array_values($results);
    }

    private function genericLink(string $label): bool { return (bool) preg_match('/^(google scholar|website|editor url|view|detail|profile)$/i', trim($label)); }
    private function clean(string $text): string { return trim(preg_replace('/\s+/u', ' ', html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? ''); }
    private function normalizeIssn(?string $issn): ?string { if (!$issn) return null; $v = strtoupper(preg_replace('/[^0-9X]/i', '', $issn)); return strlen($v) === 8 ? substr($v,0,4).'-'.substr($v,4) : ($v ?: null); }
    private function absolute(string $url): string { return str_starts_with($url, 'http') ? $url : rtrim(config('corpus.sinta.base_url'), '/').'/'.ltrim($url, '/'); }
}
