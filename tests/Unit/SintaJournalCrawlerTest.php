<?php
namespace Tests\Unit;
use App\Services\SintaJournalCrawler;
use Tests\TestCase;
class SintaJournalCrawlerTest extends TestCase
{
    public function test_parser_reads_public_journal_card(): void
    {
        $html = <<<'HTML'
        <html><body><div class="journal-card">
          <h3><a href="/journals/profile/123">Jurnal Contoh Sistem Informasi</a></h3>
          <a href="https://journal.example.id">Website</a>
          <div>P-ISSN : 12345678 | E-ISSN : 87654321 Subject Area : Engineering, Social</div>
          <div>S2 Accredited Garuda Indexed</div>
        </div></body></html>
        HTML;
        $rows = app(SintaJournalCrawler::class)->parse($html, 'https://sinta.example/journals');
        $this->assertCount(1, $rows);
        $this->assertSame('Jurnal Contoh Sistem Informasi', $rows[0]['name']);
        $this->assertSame(2, $rows[0]['sinta_level']);
        $this->assertSame('1234-5678', $rows[0]['issn']);
        $this->assertSame('8765-4321', $rows[0]['eissn']);
    }
}
