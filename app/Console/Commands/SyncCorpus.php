<?php
namespace App\Console\Commands;
use App\Models\Journal;
use App\Services\CrossrefArticleImporter;
use Illuminate\Console\Command;
class SyncCorpus extends Command
{
    protected $signature = 'corpus:sync {--level= : Batasi jurnal pada level SINTA} {--journals=20 : Maksimum jurnal yang disinkronkan} {--per-journal=100 : Maksimum artikel per jurnal} {--from-year= : Tahun publikasi minimum} {--force : Sinkronkan ulang meskipun journal sudah pernah disinkronkan}';
    protected $description = 'Mengambil metadata artikel Crossref berdasarkan ISSN jurnal SINTA di database.';
    public function handle(CrossrefArticleImporter $importer): int
    {
        $query=Journal::query()->where(function($q){$q->whereNotNull('eissn')->orWhereNotNull('issn');});
        if ($this->option('level')) $query->where('sinta_level',(int)$this->option('level'));
        if (!$this->option('force')) $query->orderByRaw('synced_at IS NOT NULL')->orderBy('synced_at');
        $journals=$query->limit(max(1,min((int)$this->option('journals'),500)))->get();
        if ($journals->isEmpty()) { $this->warn('Belum ada jurnal. Jalankan php artisan sinta:discover --pages=1 terlebih dahulu.'); return self::SUCCESS; }
        $total=['fetched'=>0,'inserted'=>0,'updated'=>0,'skipped'=>0];
        foreach ($journals as $i=>$journal) {
            $this->line(sprintf('[%d/%d] %s (S%d)', $i+1,$journals->count(),$journal->name,$journal->sinta_level));
            try {
                $s=$importer->syncJournal($journal,(int)$this->option('per-journal'),$this->option('from-year')?(int)$this->option('from-year'):null);
                foreach($total as $k=>$v)$total[$k]+=$s[$k];
                $this->info("  fetched {$s['fetched']}, baru {$s['inserted']}, update {$s['updated']}, skip {$s['skipped']}");
            } catch(\Throwable $e){$this->error('  '.$e->getMessage()); $total['skipped']++;}
        }
        $this->table(['Fetched','Baru','Diperbarui','Dilewati'],[array_values($total)]);
        return self::SUCCESS;
    }
}
