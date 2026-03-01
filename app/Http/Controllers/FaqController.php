<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\FaqSectionHeader;
use Illuminate\Http\Request;

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
