<?php

namespace App\Livewire\Cart;

use Livewire\Component;
use Livewire\Attributes\On;

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
        $cart = session()->get('cart', []);
        $this->count = collect($cart)->sum('quantity');
    }

    public function render()
    {
        return view('livewire.cart.cart-count');
    }
}
