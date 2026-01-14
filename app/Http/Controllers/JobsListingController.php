<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class JobsListingController extends Controller
{
    /**
     * Display a listing of active job posts.
     */
    public function index(Request $request)
    {
        // Get per-page value from request, default to 20, limit max to 100
        $perPage = $request->input('per_page', 20);
        $perPage = in_array($perPage, [10, 20, 50, 100]) ? $perPage : 20;

        // Get selected tags from request
        $selectedTags = $request->input('tags', []);
        $selectedTags = is_array($selectedTags) ? $selectedTags : [];

        // Start with active posts query
        $query = Post::where('is_active', true)->with(['user', 'tags']);

        // Filter by tags if any selected (AND logic - posts must have ALL selected tags)
        if (! empty($selectedTags)) {
            foreach ($selectedTags as $tagName) {
                $query->whereHas('tags', function ($tagQuery) use ($tagName) {
                    $tagQuery->where('name', $tagName);
                });
            }
        }

        // Get paginated results
        $jobs = $query->latest()->paginate($perPage);

        // Append parameters to pagination links
        $jobs->appends(['per_page' => $perPage, 'tags' => $selectedTags]);

        // Get all available tags for the filter
        $allTags = \App\Models\Tag::orderBy('name')->get();

        return view('jobs.index', compact('jobs', 'perPage', 'selectedTags', 'allTags'));
    }

    /**
     * Display the specified job post.
     */
    public function show(Post $post)
    {
        // Only show active posts
        if (! $post->is_active) {
            abort(404);
        }

        // Load relationships
        $post->load(['user', 'tags']);

        return view('jobs.show', compact('post'));
    }
}
