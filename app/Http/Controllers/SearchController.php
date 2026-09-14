<?php
namespace App\Http\Controllers;
use App\Http\Requests\SearchCitationRequest;
use App\Models\Article;
use App\Models\Journal;
use App\Services\CitationSearchService;
use Illuminate\View\View;
class SearchController extends Controller
{
    public function index(): View
    {
        return view('search', [
            'stats' => ['articles' => Article::count(), 'journals' => Journal::count()],
            'results' => null,
        ]);
    }
    public function search(SearchCitationRequest $request, CitationSearchService $service): View
    {
        $data = $request->validated();
        $search = $service->search(
            $data['q'],
            isset($data['sinta']) ? (int)$data['sinta'] : null,
            isset($data['year_from']) ? (int)$data['year_from'] : null,
            isset($data['limit']) ? (int)$data['limit'] : 10,
        );
        return view('search', [
            'stats' => ['articles' => Article::count(), 'journals' => Journal::count()],
            'results' => $search,
            'query' => $data['q'],
            'filters' => $data,
        ]);
    }
}
