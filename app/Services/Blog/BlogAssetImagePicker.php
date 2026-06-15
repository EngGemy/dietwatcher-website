<?php

declare(strict_types=1);

namespace App\Services\Blog;

use App\Models\BlogPost;
use Illuminate\Support\Collection;

class BlogAssetImagePicker
{
    /**
     * Curated food / wellness images from public/assets suitable for blog covers.
     *
     * @return list<string> Paths relative to public/ (e.g. assets/images/blog-1.png)
     */
    public static function pool(): array
    {
        $candidates = [
            'assets/images/blog-1.png',
            'assets/images/blog-2.png',
            'assets/images/blog-3.png',
            'assets/images/blog-4.png',
            'assets/images/meal-1.png',
            'assets/images/meal-2.png',
            'assets/images/meal-3.png',
            'assets/images/avocato-plante.png',
            'assets/images/meal-plan-1.png',
            'assets/images/meal-plan-2.png',
            'assets/images/meal-plan-3.png',
            'assets/images/meal-plan-1-ar.png',
            'assets/images/meal-plan-2-ar.png',
            'assets/images/meal-plan-3-ar.png',
            'assets/images/why-1.png',
            'assets/images/why-2.png',
            'assets/images/hero-pizza.png',
            'assets/images/hero-tomato.png',
            'assets/images/download-tomatos.png',
            'assets/images/plan-1.png',
            'assets/images/plan-2.png',
            'assets/images/plan-3.png',
        ];

        return array_values(array_filter($candidates, static fn (string $path): bool => file_exists(public_path($path))));
    }

    public function pick(?string $preferred = null): string
    {
        $pool = self::pool();
        if ($pool === []) {
            return 'assets/images/blog-1.png';
        }

        if ($preferred !== null && in_array($preferred, $pool, true)) {
            return $preferred;
        }

        $used = BlogPost::query()
            ->whereNotNull('cover_image_path')
            ->pluck('cover_image_path')
            ->filter()
            ->values();

        $available = collect($pool)->diff($used);

        if ($available->isEmpty()) {
            return $pool[array_rand($pool)];
        }

        return (string) $available->random();
    }

    /**
     * @return Collection<int, string>
     */
    public function pickMany(int $count): Collection
    {
        $pool = collect(self::pool())->shuffle()->values();
        $picked = collect();

        foreach ($pool as $path) {
            if ($picked->count() >= $count) {
                break;
            }
            $picked->push($path);
        }

        while ($picked->count() < $count && $pool->isNotEmpty()) {
            $picked->push($pool->random());
        }

        return $picked;
    }
}
