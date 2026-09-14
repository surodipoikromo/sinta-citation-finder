<?php
namespace Tests\Feature;
use App\Models\Journal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class CitationSearchTest extends TestCase
{
    use RefreshDatabase;
    public function test_home_page_loads(): void { $this->get('/')->assertOk()->assertSee('Cari kandidat referensi SINTA'); }
    public function test_search_ranks_matching_article(): void
    {
        $journal = Journal::create(['name'=>'Test Journal','sinta_level'=>2]);
        $journal->articles()->create(['authors'=>'A. Author','title'=>'Kualitas Sistem dan Kepuasan Pengguna','year'=>2025,'abstract'=>'Kualitas sistem berhubungan dengan kepuasan pengguna pada sistem informasi.','keywords'=>'kualitas sistem; kepuasan pengguna']);
        $journal->articles()->create(['authors'=>'B. Author','title'=>'Topik Tidak Terkait','year'=>2025,'abstract'=>'Penelitian membahas pertanian dan kualitas tanah.','keywords'=>'pertanian']);
        $this->post('/cari', ['q'=>'kualitas sistem meningkatkan kepuasan pengguna','limit'=>10])
            ->assertOk()->assertSee('Kualitas Sistem dan Kepuasan Pengguna')->assertSee('relevansi');
    }
    public function test_search_can_filter_sinta_level(): void
    {
        $s2 = Journal::create(['name'=>'S2 Journal','sinta_level'=>2]);
        $s4 = Journal::create(['name'=>'S4 Journal','sinta_level'=>4]);
        foreach([$s2,$s4] as $j) $j->articles()->create(['authors'=>'A','title'=>'Kualitas Sistem','year'=>2025,'abstract'=>'Kualitas sistem dan kepuasan pengguna.','keywords'=>'kualitas sistem']);
        $this->post('/cari', ['q'=>'kualitas sistem dan kepuasan pengguna','sinta'=>2,'limit'=>10])->assertOk()->assertSee('S2 Journal')->assertDontSee('S4 Journal');
    }
}
