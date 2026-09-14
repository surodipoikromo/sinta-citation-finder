<?php
namespace App\Console\Commands;
use App\Services\SintaJournalCrawler;
use Illuminate\Console\Command;
class DiscoverSintaJournals extends Command
{
    protected $signature = 'sinta:discover {--pages=1 : Jumlah halaman daftar jurnal SINTA} {--level= : Hanya SINTA level 1-6}';
    protected $description = 'Mengambil daftar jurnal publik SINTA beserta ISSN dan level akreditasi.';
    public function handle(SintaJournalCrawler $crawler): int
    {
        $pages=(int)$this->option('pages'); $level=$this->option('level')!==null?(int)$this->option('level'):null;
        if ($level!==null && ($level<1||$level>6)) { $this->error('Level harus 1-6.'); return self::FAILURE; }
        $this->warn('Gunakan secara wajar. Command memberi jeda antarhalaman dan hanya mengambil data publik.');
        try {
            $stats=$crawler->crawl($pages,$level,function($page,$count){$this->line("Halaman {$page}: {$count} kandidat jurnal ditemukan.");});
            $this->table(['Halaman','Ditemukan','Baru','Diperbarui','Dilewati'], [[$stats['pages'],$stats['found'],$stats['inserted'],$stats['updated'],$stats['skipped']]]);
            return self::SUCCESS;
        } catch (\Throwable $e) { $this->error($e->getMessage()); return self::FAILURE; }
    }
}
