<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BlogController extends Controller
{
    public function index(Request $request): View
    {
        $query = BlogPost::published()
            ->with(['author', 'tags', 'category']);

        if ($search = $request->input('search')) {
            $query->whereTranslationLike('title', "%{$search}%")
                ->orWhereTranslationLike('excerpt', "%{$search}%");
        }

        if ($categorySlug = $request->input('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $categorySlug));
        }

        if ($tagSlug = $request->input('tag')) {
            $query->whereHas('tags', fn ($q) => $q->where('slug', $tagSlug));
        }

        $posts = $query->orderBy('published_at', 'desc')->paginate(12)->withQueryString();

        $categories = BlogCategory::where('is_active', true)->orderBy('order_column')->get();

        return view('blog.index', compact('posts', 'categories'));
    }

    /**
     * Display a single blog post by translated slug
     */
    public function show(string $slug): View|Response
    {
        $locale = app()->getLocale();

        // Find post by translated slug in current locale
        $post = BlogPost::published()
            ->whereTranslation('slug', $slug)
            ->with(['author', 'tags', 'likes', 'category'])
            ->first();

        if (! $post) {
            abort(404);
        }

        // Increment view count
        $post->increment('views_count');

        $categories = BlogCategory::where('is_active', true)->orderBy('order_column')->get();

        $latestPosts = BlogPost::published()
            ->where('id', '!=', $post->id)
            ->with(['category'])
            ->orderByDesc('published_at')
            ->limit(5)
            ->get();

        return view('blog.show', compact('post', 'categories', 'latestPosts'));
    }
}
