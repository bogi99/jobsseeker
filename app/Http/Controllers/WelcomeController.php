<?php

namespace App\Http\Controllers;

use App\Models\Post;

class WelcomeController extends Controller
{
    /**
     * Default SEO keywords for pages using this controller.
     *
     * @var list<string>
     */
    protected array $defaultKeywords = ['Jobs', 'PHP', 'Python', 'JavaScript', 'MySQL', 'PostgreSQL', 'coding'];

    /**
     * Display the welcome page with active posts.
     */
    public function index()
    {
        // Determine how many posts to pick: 10% of all posts, min 1, max 10
        $total = Post::count();
        $take = max(1, (int) floor($total * 0.1));
        $take = min(10, $take);

        // Get random active posts with user and tags relationships
        $activePosts = Post::where('is_active', true)
            ->with(['user', 'tags'])
            ->inRandomOrder()
            ->take($take)
            ->get();

        // The view composer will obtain default keywords via the controller property.

        return view('welcome', ['activePosts' => $activePosts, 'defaultKeywords' => $this->defaultKeywords]);
    }
}
