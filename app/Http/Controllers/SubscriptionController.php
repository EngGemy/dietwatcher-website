<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ExternalDataService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function __construct(
        private ExternalDataService $externalDataService
    ) {}

    /**
     * Show the subscription lookup page (user enters phone to find their subscriptions).
     */
    public function index(Request $request): View
    {
        $phone = $request->get('phone');
        $subscriptions = [];
        $meta = ['currentPage' => 1, 'lastPage' => 1];

        if ($phone) {
            $result = $this->externalDataService->getSubscriptions(
                page: (int) $request->get('page', 1),
                phone: $phone
            );
            $subscriptions = $result['data'];
            $meta = $result['meta'];
        }

        return view('pages.subscriptions', compact('subscriptions', 'meta', 'phone'));
    }

    /**
     * Show a single subscription detail.
     */
    public function show(int $id): View
    {
        $subscription = $this->externalDataService->getSubscription($id);

        if (!$subscription) {
            abort(404);
        }

        return view('pages.subscription-detail', compact('subscription'));
    }
}
