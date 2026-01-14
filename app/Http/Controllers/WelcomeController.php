<?php

namespace App\Http\Controllers;

use App\Models\Post;

class WelcomeController extends Controller
{
    /**
     * Display the welcome page with active posts.
     */
    public function index()
    {
        // Get random active posts with user and tags relationships
        $activePosts = Post::where('is_active', true)
            ->with(['user', 'tags'])
            ->inRandomOrder()->take(Post::count() * 0.1)
            ->limit(10)
            ->get();

        return view('welcome', compact('activePosts'));
    }
}
