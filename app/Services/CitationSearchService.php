<?php

namespace App\Services;

use App\Models\Article;
use Illuminate\Support\Collection;

class CitationSearchService
{
    /** @var string[] */
    private array $stopwords = [
        'yang','dan','atau','dengan','dari','untuk','pada','dalam','adalah','ini','itu','ke','di','sebagai','oleh','terhadap','dapat','bisa','lebih','juga','antara','suatu','sebuah','karena','bahwa','akan','telah','sudah','menjadi','memiliki','mempunyai','serta','namun','tetapi','pada','para','secara','melalui','berdasarkan',
        'the','and','or','with','from','for','on','in','is','are','of','to','as','by','this','that','can','could','may','more','also','between','a','an','has','have','had','been','be','using','based','via','through'
    ];

    /**
     * Kata relasional/metodologis yang sering tidak membantu membedakan topik.
     * Selain dibuang dari token, kata/frasa ini dipakai sebagai pemisah konsep.
     *
     * @var string[]
     */
    private array $relationMarkers = [
        'berpengaruh terhadap','berpengaruh pada','berpengaruh','mempengaruhi','memengaruhi','dipengaruhi oleh',
        'berhubungan dengan','hubungan antara','berkorelasi dengan','korelasi antara','meningkatkan','menurunkan',
        'berdampak pada','berdampak terhadap','pengaruh','hubungan','korelasi','terhadap',
        'affects','affect','influences','influence','influenced by','associated with','association between',
        'related to','relationship between','correlates with','correlation between','impacts','impact on',
        'increases','increase','decreases','decrease','effect of','effects of'
    ];

    /** @var string[] */
    private array $domainStopwords = [
        'berpengaruh','pengaruh','hubungan','berhubungan','korelasi','berkorelasi','signifikan','signifikansi',
        'penelitian','studi','hasil','analisis','variabel','model','metode','data','responden','sampel',
        'affect','affects','influence','influences','effect','effects','relationship','association','associated',
        'correlation','correlates','significant','significance','research','study','results','analysis','variable','model','method','data','respondents','sample'
    ];

    public function search(string $query, ?int $sinta = null, ?int $yearFrom = null, int $limit = 10): array
    {
        $builder = Article::query()->with('journal');
        if ($sinta) {
            $builder->whereHas('journal', fn ($q) => $q->where('sinta_level', $sinta));
        }
        if ($yearFrom) {
            $builder->where('year', '>=', $yearFrom);
        }

        /** @var Collection<int,Article> $articles */
        $articles = $builder->get();
        $profile = $this->queryProfile($query);
        $queryTerms = $profile['terms'];
        $concepts = $profile['concepts'];

        if ($articles->isEmpty() || $queryTerms === []) {
            return [
                'results' => [],
                'terms' => $queryTerms,
                'concepts' => $concepts,
                'documents' => $articles->count(),
                'mode' => 'none',
            ];
        }

        $documentFrequency = array_fill_keys($queryTerms, 0);
        $fields = [];

        foreach ($articles as $article) {
            $fields[$article->id] = $this->articleFields($article);
            $unique = array_unique(array_merge(
                $fields[$article->id]['title_tokens'],
                $fields[$article->id]['keyword_tokens'],
                $fields[$article->id]['abstract_tokens'],
            ));

            foreach ($queryTerms as $term) {
                if (in_array($term, $unique, true)) {
                    $documentFrequency[$term]++;
                }
            }
        }

        $n = max(1, $articles->count());
        $idf = [];
        foreach ($queryTerms as $term) {
            $idf[$term] = log(1 + (($n - ($documentFrequency[$term] ?? 0) + 0.5) / (($documentFrequency[$term] ?? 0) + 0.5)));
        }

        $strict = [];
        $fallback = [];

        foreach ($articles as $article) {
            $field = $fields[$article->id];
            $matched = $this->matchedTerms($queryTerms, $field);
            $matchedCount = count($matched);
            if ($matchedCount === 0) {
                continue;
            }

            $titleScore = $this->idfCoverage($queryTerms, $field['title_tokens'], $idf);
            $keywordScore = $this->idfCoverage($queryTerms, $field['keyword_tokens'], $idf);
            $abstractScore = $this->idfCoverage($queryTerms, $field['abstract_tokens'], $idf);
            $lexicalScore = ($titleScore * 0.48) + ($keywordScore * 0.34) + ($abstractScore * 0.18);

            $phrase = $this->phraseScore($concepts, $field);
            $conceptCoverage = $this->conceptCoverage($concepts, $field);
            $termCoverage = $matchedCount / max(1, count($queryTerms));

            $score = ($lexicalScore * 0.52)
                + ($phrase['score'] * 0.26)
                + ($conceptCoverage * 0.14)
                + ($termCoverage * 0.08);

            if ($termCoverage >= 0.999) {
                $score += 0.04;
            }

            $score = min(1.0, max(0.0, $score));

            $row = [
                'article' => $article,
                'score' => round($score * 100, 1),
                'matched_terms' => $matched,
                'matched_concepts' => $phrase['matched'],
                'concept_coverage' => round($conceptCoverage * 100, 1),
                'excerpt' => $this->bestExcerpt($article->abstract ?? '', $queryTerms, $concepts),
                'match_quality' => 'partial',
            ];

            // STRICT: minimal dua istilah untuk query >=4 term, dan ada frasa/konsep
            // atau coverage term yang cukup tinggi. Ini menjaga hasil utama tetap relevan.
            $minimumMatches = count($queryTerms) >= 4 ? 2 : 1;
            $isStrict = $matchedCount >= $minimumMatches
                && ($conceptCoverage >= 0.5 || !empty($phrase['matched']) || $termCoverage >= 0.60)
                && $score >= 0.055;

            if ($isStrict) {
                $row['match_quality'] = $conceptCoverage >= 0.999 ? 'strong' : 'good';
                $strict[] = $row;
                continue;
            }

            // FALLBACK: tetap tampilkan kandidat terbaik ketika corpus tidak memiliki
            // kecocokan konsep penuh. Syaratnya minimal ada sinyal lexical yang masuk akal.
            $fallbackScore = ($lexicalScore * 0.70) + ($termCoverage * 0.22) + ($phrase['score'] * 0.08);
            if ($matchedCount >= 2 || $fallbackScore >= 0.16) {
                $row['score'] = round(min(1.0, $fallbackScore) * 100, 1);
                $fallback[] = $row;
            }
        }

        usort($strict, fn ($a, $b) => $b['score'] <=> $a['score']);
        usort($fallback, fn ($a, $b) => $b['score'] <=> $a['score']);

        $mode = 'strict';
        $ranked = $strict;

        if ($ranked === []) {
            $mode = $fallback === [] ? 'none' : 'fallback';
            $ranked = $fallback;
        }

        return [
            'results' => array_slice($ranked, 0, $limit),
            'terms' => $queryTerms,
            'concepts' => $concepts,
            'documents' => $articles->count(),
            'mode' => $mode,
        ];
    }

    /**
     * @return array{terms: string[], concepts: string[]}
     */
    public function queryProfile(string $query): array
    {
        $normalized = $this->normalize($query);
        $segments = [$normalized];

        // Pecah klaim pada kata relasional, sehingga:
        // "kualitas sistem berpengaruh terhadap kepuasan pengguna"
        // -> ["kualitas sistem", "kepuasan pengguna"]
        $markers = $this->relationMarkers;
        usort($markers, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        foreach ($markers as $marker) {
            $next = [];
            foreach ($segments as $segment) {
                foreach (preg_split('/\b'.preg_quote($marker, '/').'\b/u', $segment) ?: [$segment] as $piece) {
                    $piece = trim($piece);
                    if ($piece !== '') {
                        $next[] = $piece;
                    }
                }
            }
            $segments = $next ?: $segments;
        }

        $terms = $this->tokens($query);
        $concepts = [];

        foreach ($segments as $segment) {
            $segmentTerms = $this->tokens($segment);
            if (count($segmentTerms) >= 2) {
                // Frasa penuh untuk segmen pendek (2–4 kata) adalah konsep terkuat.
                if (count($segmentTerms) <= 4) {
                    $concepts[] = implode(' ', $segmentTerms);
                }

                // Tambahkan bigram agar frasa panjang tetap punya sinyal lokal.
                for ($i = 0; $i < count($segmentTerms) - 1; $i++) {
                    $concepts[] = $segmentTerms[$i].' '.$segmentTerms[$i + 1];
                }

                // Trigram memberi bonus lebih kuat pada konsep spesifik.
                for ($i = 0; $i < count($segmentTerms) - 2; $i++) {
                    $concepts[] = $segmentTerms[$i].' '.$segmentTerms[$i + 1].' '.$segmentTerms[$i + 2];
                }
            }
        }

        return [
            'terms' => array_values(array_unique($terms)),
            'concepts' => array_values(array_unique(array_filter($concepts))),
        ];
    }

    public function tokens(string $text): array
    {
        $text = $this->normalize($text);
        $parts = preg_split('/\s+/u', trim($text)) ?: [];
        $parts = array_filter($parts, function ($token) {
            return mb_strlen($token) >= 3
                && !in_array($token, $this->stopwords, true)
                && !in_array($token, $this->domainStopwords, true)
                && !in_array($token, $this->singleWordRelationMarkers(), true);
        });

        return array_values(array_unique($parts));
    }

    private function normalize(?string $text): string
    {
        $text = mb_strtolower(strip_tags((string) $text), 'UTF-8');
        $text = preg_replace('/[^\p{L}\p{N}\s-]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        return trim($text);
    }

    /** @return string[] */
    private function singleWordRelationMarkers(): array
    {
        static $single = null;
        if ($single !== null) {
            return $single;
        }

        $single = array_values(array_unique(array_filter(
            $this->relationMarkers,
            fn ($marker) => !str_contains($marker, ' ')
        )));
        return $single;
    }

    /** @return array<string,mixed> */
    private function articleFields(Article $article): array
    {
        return [
            'title_text' => $this->normalize($article->title),
            'keyword_text' => $this->normalize($article->keywords ?? ''),
            'abstract_text' => $this->normalize($article->abstract ?? ''),
            'title_tokens' => $this->tokens($article->title),
            'keyword_tokens' => $this->tokens($article->keywords ?? ''),
            'abstract_tokens' => $this->tokens($article->abstract ?? ''),
        ];
    }

    private function idfCoverage(array $queryTerms, array $fieldTokens, array $idf): float
    {
        if ($fieldTokens === []) {
            return 0.0;
        }

        $fieldCounts = array_count_values($fieldTokens);
        $matched = 0.0;
        $possible = 0.0;

        foreach ($queryTerms as $term) {
            $weight = max(0.05, $idf[$term] ?? 1.0);
            $possible += $weight;
            if (($fieldCounts[$term] ?? 0) > 0) {
                // Presence matters most; repeated occurrences add only a tiny amount.
                $matched += $weight * min(1.12, 1 + (0.04 * (($fieldCounts[$term] ?? 1) - 1)));
            }
        }

        return $possible > 0 ? min(1.0, $matched / $possible) : 0.0;
    }

    /**
     * @return array{score: float, matched: string[]}
     */
    private function phraseScore(array $concepts, array $field): array
    {
        if ($concepts === []) {
            return ['score' => 0.0, 'matched' => []];
        }

        $matched = [];
        $points = 0.0;
        $maxPoints = 0.0;

        foreach ($concepts as $concept) {
            $wordCount = count(preg_split('/\s+/u', $concept) ?: []);
            $specificity = $wordCount >= 3 ? 1.35 : 1.0;
            $maxPoints += 4.8 * $specificity;

            $conceptMatched = false;
            if ($this->containsPhrase($field['title_text'], $concept)) {
                $points += 4.8 * $specificity;
                $conceptMatched = true;
            } elseif ($this->containsPhrase($field['keyword_text'], $concept)) {
                $points += 4.2 * $specificity;
                $conceptMatched = true;
            } elseif ($this->containsPhrase($field['abstract_text'], $concept)) {
                $points += 2.7 * $specificity;
                $conceptMatched = true;
            }

            if ($conceptMatched) {
                $matched[] = $concept;
            }
        }

        return [
            'score' => $maxPoints > 0 ? min(1.0, $points / $maxPoints) : 0.0,
            'matched' => array_values(array_unique($matched)),
        ];
    }

    private function conceptCoverage(array $concepts, array $field): float
    {
        if ($concepts === []) {
            return 0.0;
        }

        // Fokus coverage pada konsep maksimal per kelompok. Karena queryProfile juga
        // membuat bigram/trigram, deduplikasi konsep yang saling terkandung agar tidak
        // menghukum artikel secara berlebihan.
        $primary = $this->primaryConcepts($concepts);
        if ($primary === []) {
            return 0.0;
        }

        $matched = 0;
        foreach ($primary as $concept) {
            if ($this->containsPhrase($field['title_text'], $concept)
                || $this->containsPhrase($field['keyword_text'], $concept)
                || $this->containsPhrase($field['abstract_text'], $concept)) {
                $matched++;
            }
        }

        return $matched / count($primary);
    }

    /** @return string[] */
    private function primaryConcepts(array $concepts): array
    {
        $sorted = array_values(array_unique($concepts));
        usort($sorted, fn ($a, $b) => count(preg_split('/\s+/u', $b) ?: []) <=> count(preg_split('/\s+/u', $a) ?: []));

        $primary = [];
        foreach ($sorted as $candidate) {
            $contained = false;
            foreach ($primary as $existing) {
                if ($candidate !== $existing && $this->containsPhrase($existing, $candidate)) {
                    $contained = true;
                    break;
                }
            }
            if (!$contained) {
                $primary[] = $candidate;
            }
        }

        return $primary;
    }

    private function containsPhrase(string $haystack, string $needle): bool
    {
        if ($haystack === '' || $needle === '') {
            return false;
        }
        return preg_match('/(?:^|\s)'.preg_quote($needle, '/').'(?:$|\s)/u', $haystack) === 1;
    }

    /** @return string[] */
    private function matchedTerms(array $queryTerms, array $field): array
    {
        $all = array_unique(array_merge($field['title_tokens'], $field['keyword_tokens'], $field['abstract_tokens']));
        return array_values(array_filter($queryTerms, fn ($term) => in_array($term, $all, true)));
    }

    private function bestExcerpt(string $abstract, array $terms, array $concepts): string
    {
        if (trim($abstract) === '') {
            return 'Abstrak tidak tersedia pada metadata sumber.';
        }

        $sentences = preg_split('/(?<=[.!?])\s+/u', trim($abstract)) ?: [$abstract];
        $best = '';
        $bestScore = -1.0;

        foreach ($sentences as $sentence) {
            $lower = $this->normalize($sentence);
            $score = 0.0;

            foreach ($terms as $term) {
                if ($this->containsPhrase($lower, $term)) {
                    $score += 1.0;
                }
            }
            foreach ($concepts as $concept) {
                if ($this->containsPhrase($lower, $concept)) {
                    $score += 3.0;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $sentence;
            }
        }

        if ($best === '') {
            $best = $abstract;
        }

        return mb_strlen($best) > 420 ? mb_substr($best, 0, 417).'…' : $best;
    }
}
