<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class JobsListingController extends Controller
{
    /**
     * Default SEO keywords for pages using this controller.
     *
     * @var list<string>
     */
    protected array $defaultKeywords = ['Jobs', 'Search', 'JobRat', 'Job Listings', 'Career', 'Employment', 'Hiring'];

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

        // Provide the paginated collection items to the view composer as 'activePosts'
        $activePosts = $jobs->getCollection();

        return view('jobs.index', ['jobs' => $jobs, 'perPage' => $perPage, 'selectedTags' => $selectedTags, 'allTags' => $allTags, 'activePosts' => $activePosts, 'defaultKeywords' => $this->defaultKeywords]);
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

        // Provide the single post as 'activePosts' for the composer to generate keywords
        $activePosts = collect([$post]);

        return view('jobs.show', ['post' => $post, 'activePosts' => $activePosts, 'defaultKeywords' => $this->defaultKeywords]);
    }
}
