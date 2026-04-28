<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\FaqSectionHeader;
use App\Services\ExternalDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FaqController extends Controller
{
    /**
     * Display the FAQ page.
     */
    public function index(Request $request)
    {
        $categorySlug = $request->get('category');
        $search = $request->get('search');

        $faqHeader = FaqSectionHeader::where('is_active', true)->first();
        $service = app(ExternalDataService::class);
        $apiFaqs = collect($service->getCommonQuestions());

        // Prefer API FAQs if available, fallback to DB FAQs.
        if ($apiFaqs->isNotEmpty()) {
            $categories = $apiFaqs
                ->pluck('category')
                ->filter(fn ($c) => is_string($c) && trim($c) !== '')
                ->unique()
                ->values()
                ->map(fn (string $name) => (object) [
                    'slug' => Str::slug($name),
                    'name' => $name,
                    'icon' => null,
                ]);

            $faqs = $apiFaqs
                ->map(function (array $faq) {
                    $categoryName = (string) ($faq['category'] ?? '');

                    return (object) [
                        'id' => (int) ($faq['id'] ?? 0),
                        'question' => (string) ($faq['question'] ?? ''),
                        'answer' => (string) ($faq['answer_html'] ?? ''),
                        'category_slug' => Str::slug($categoryName),
                        'category_name' => $categoryName,
                    ];
                })
                ->filter(function (object $faq) use ($categorySlug, $search) {
                    if ($categorySlug && $faq->category_slug !== $categorySlug) {
                        return false;
                    }
                    if ($search) {
                        $haystack = Str::lower(trim($faq->question.' '.strip_tags($faq->answer)));

                        return str_contains($haystack, Str::lower(trim((string) $search)));
                    }

                    return true;
                })
                ->values();

            return view('pages.faqs', compact('faqHeader', 'categories', 'faqs', 'categorySlug', 'search'));
        }

        $categories = FaqCategory::where('is_active', true)
            ->orderBy('order_column')
            ->get();

        $faqsQuery = Faq::with('category')
            ->where('is_active', true)
            ->orderBy('order_column');

        if ($categorySlug) {
            $faqsQuery->whereHas('category', function ($q) use ($categorySlug) {
                $q->where('slug', $categorySlug);
            });
        }

        if ($search) {
            $locale = app()->getLocale();
            $faqsQuery->whereHas('translations', function ($q) use ($search, $locale) {
                $q->where('locale', $locale)
                    ->where(function ($sq) use ($search) {
                        $sq->where('question', 'like', "%{$search}%")
                            ->orWhere('answer', 'like', "%{$search}%");
                    });
            });
        }

        $faqs = $faqsQuery->get();

        return view('pages.faqs', compact('faqHeader', 'categories', 'faqs', 'categorySlug', 'search'));
    }
}
