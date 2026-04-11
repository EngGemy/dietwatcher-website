<?php

declare(strict_types=1);

namespace App\Livewire\Cart;

use Livewire\Attributes\On;
use Livewire\Component;

class CartCount extends Component
{
    public int $count = 0;

    public function mount(): void
    {
        $this->updateCount();
    }

    #[On('cart-updated')]
    public function updateCount(): void
    {
        $cart = session()->get(CartManager::SESSION_MARKET, []);
        $mealRows = array_filter(
            $cart,
            static fn ($key) => is_string($key) && str_starts_with($key, 'meal_'),
            ARRAY_FILTER_USE_KEY
        );
        $this->count = (int) collect($mealRows)->sum('quantity');
    }

    public function render()
    {
        return view('livewire.cart.cart-count');
    }
}
