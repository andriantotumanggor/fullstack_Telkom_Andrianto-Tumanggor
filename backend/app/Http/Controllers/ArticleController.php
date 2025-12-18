<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ArticleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Article::all());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'required|string|max:255',
            'date' => 'required|date',
        ]);

        $article = Article::create($request->all());
        return response()->json($article, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $article = Article::findOrFail($id);
        return response()->json($article);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $article = Article::findOrFail($id);

        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'category' => 'sometimes|required|string|max:255',
            'date' => 'sometimes|required|date',
        ]);

        $article->update($request->all());
        return response()->json($article);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $article = Article::findOrFail($id);
        $article->delete();
        return response()->json(['message' => 'Article deleted successfully']);
    }

    /**
     * Sync articles from NewsAPI.
     */
    public function sync()
    {
        $apiKey = config('services.newsapi.key');
        if (!$apiKey) {
            return response()->json(['error' => 'NewsAPI key not configured'], 500);
        }

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::get('https://newsapi.org/v2/top-headlines', [
            'country' => 'us',
            'apiKey' => $apiKey,
        ]);

        if ($response->failed()) {
            return response()->json(['error' => 'Failed to fetch from NewsAPI'], 500);
        }

        try {
            $data = $response->json();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid JSON response from NewsAPI'], 500);
        }

        if (!isset($data['articles']) || !is_array($data['articles'])) {
            return response()->json(['error' => 'Invalid response from NewsAPI'], 500);
        }

        $articles = $data['articles'];
        $syncedCount = 0;

        foreach ($articles as $articleData) {
            // Check for duplicates based on title
            $existing = Article::where('title', $articleData['title'])->first();
            if (!$existing) {
                Article::create([
                    'title' => $articleData['title'],
                    'content' => $articleData['description'] ?? '',
                    'category' => $articleData['source']['name'] ?? 'General',
                    'date' => now()->toDateString(),
                ]);
                $syncedCount++;
            }
        }

        return response()->json(['synced_count' => $syncedCount]);
    }
}
