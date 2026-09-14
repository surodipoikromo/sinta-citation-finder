<?php
namespace App\Http\Controllers;
use App\Http\Requests\ImportCorpusRequest;
use App\Models\Article;
use App\Models\Journal;
use App\Services\CorpusImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;
class CorpusController extends Controller
{
    public function index(): View
    {
        $articles = Article::with('journal')->latest()->paginate(20);
        return view('corpus.index', [
            'articles' => $articles,
            'journalCount' => Journal::count(),
            'articleCount' => Article::count(),
            'sintaJournalCount' => Journal::where('source', 'sinta')->count(),
            'crossrefArticleCount' => Article::where('source', 'crossref')->count(),
        ]);
    }
    public function import(ImportCorpusRequest $request, CorpusImportService $service): RedirectResponse
    {
        try {
            $result = DB::transaction(fn() => $service->import($request->file('csv')->getRealPath()));
            return back()->with('success', "Impor selesai: {$result['inserted']} baru, {$result['updated']} diperbarui, {$result['skipped']} dilewati.");
        } catch (RuntimeException $e) {
            return back()->withErrors(['csv' => $e->getMessage()]);
        }
    }
    public function clear(): RedirectResponse
    {
        DB::transaction(function () { Article::query()->delete(); Journal::query()->delete(); });
        return back()->with('success', 'Corpus berhasil dikosongkan.');
    }
    public function sampleCsv(): Response
    {
        $csv = implode(',', ['authors','title','journal','sinta_level','year','url','doi','abstract','keywords','subject_area','issn','eissn','journal_url'])."\n";
        $csv .= '"Nama Penulis","Judul Artikel","Nama Jurnal",2,2025,"https://example.org/article","10.0000/example","Abstrak artikel ditulis di sini.","kata kunci; sistem informasi","Engineering","","","https://example.org"'."\n";
        return response($csv, 200, ['Content-Type' => 'text/csv; charset=UTF-8','Content-Disposition' => 'attachment; filename="template_corpus_sinta.csv"']);
    }
}
