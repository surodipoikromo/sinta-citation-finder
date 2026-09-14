<?php
namespace App\Services;

use App\Models\Article;
use App\Models\Journal;
use RuntimeException;

class CorpusImportService
{
    private array $required = ['authors','title','journal','sinta_level','abstract'];

    public function import(string $path): array
    {
        $handle = fopen($path, 'r');
        if (!$handle) throw new RuntimeException('File CSV tidak dapat dibuka.');
        $header = fgetcsv($handle);
        if (!$header) throw new RuntimeException('Header CSV tidak ditemukan.');
        $header = array_map(fn($v) => trim(mb_strtolower((string)$v)), $header);
        foreach ($this->required as $required) if (!in_array($required, $header, true)) throw new RuntimeException("Kolom wajib '{$required}' tidak ditemukan.");

        $inserted = $updated = $skipped = 0;
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== count($header)) { $skipped++; continue; }
            $data = array_combine($header, $row);
            if (!$data || trim($data['title'] ?? '') === '' || trim($data['abstract'] ?? '') === '') { $skipped++; continue; }
            $level = (int)($data['sinta_level'] ?? 0);
            if ($level < 1 || $level > 6) { $skipped++; continue; }

            $journal = Journal::updateOrCreate(
                ['name' => trim($data['journal']), 'sinta_level' => $level],
                ['issn' => $this->null($data['issn'] ?? null), 'eissn' => $this->null($data['eissn'] ?? null), 'subject_area' => $this->null($data['subject_area'] ?? null), 'website_url' => $this->null($data['journal_url'] ?? null)]
            );
            $existing = Article::where('journal_id', $journal->id)->where('title', trim($data['title']))->first();
            $payload = [
                'authors' => trim($data['authors']),
                'year' => $this->year($data['year'] ?? null),
                'doi' => $this->null($data['doi'] ?? null),
                'url' => $this->null($data['url'] ?? null),
                'abstract' => trim($data['abstract']),
                'keywords' => $this->null($data['keywords'] ?? null),
            ];
            if ($existing) { $existing->update($payload); $updated++; }
            else { $journal->articles()->create(array_merge($payload, ['title' => trim($data['title'])])); $inserted++; }
        }
        fclose($handle);
        return compact('inserted','updated','skipped');
    }

    private function null(?string $value): ?string { $value = trim((string)$value); return $value === '' ? null : $value; }
    private function year(?string $value): ?int { $year = (int)$value; return $year >= 1900 && $year <= (int)date('Y') ? $year : null; }
}
