<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
class BootstrapCorpus extends Command
{
    protected $signature = 'corpus:bootstrap {--pages=3} {--journals=20} {--per-journal=100} {--level=} {--from-year=}';
    protected $description = 'Shortcut: crawl jurnal SINTA lalu sinkronkan artikel dari Crossref.';
    public function handle(): int
    {
        $level=$this->option('level');
        $discover=['--pages'=>(int)$this->option('pages')]; if($level)$discover['--level']=(int)$level;
        $this->call('sinta:discover',$discover);
        $sync=['--journals'=>(int)$this->option('journals'),'--per-journal'=>(int)$this->option('per-journal')];
        if($level)$sync['--level']=(int)$level; if($this->option('from-year'))$sync['--from-year']=(int)$this->option('from-year');
        return $this->call('corpus:sync',$sync);
    }
}
