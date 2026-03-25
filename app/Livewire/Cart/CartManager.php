<?php

declare(strict_types=1);

namespace App\Livewire\Cart;

use Livewire\Attributes\On;
use Livewire\Component;

class CartManager extends Component
{
    public array $cart = [];

    public function mount(): void
    {
        $this->cart = session()->get('cart', []);
    }

    #[On('cart-updated')]
    public function updateCart(): void
    {
        $this->cart = session()->get('cart', []);
    }

    #[On('add-to-cart')]
    public function handleAddToCart(
        int $planId = 0,
        string $name = '',
        float $price = 0,
        string $image = '',
        array $options = []
    ): void {
        if ($planId === 0) {
            return;
        }

        $this->addToCart($planId, $name, $price, $image, $options);
    }

    #[On('decrement-cart-item')]
    public function handleDecrement(int $planId = 0): void
    {
        if ($planId === 0) {
            return;
        }

        $key  = 'plan_' . $planId;
        $cart = session()->get('cart', []);

        if (!isset($cart[$key])) {
            return;
        }

        if ($cart[$key]['quantity'] <= 1) {
            unset($cart[$key]);
        } else {
            $cart[$key]['quantity']--;
        }

        session()->put('cart', $cart);
        $this->cart = $cart;
        $this->dispatch('cart-updated');
    }

    public function addToCart(int $planId, string $name, float $price, string $image, array $options = []): void
    {
        $cart = session()->get('cart', []);

        $key = 'plan_' . $planId;

        if (isset($cart[$key])) {
            $cart[$key]['quantity']++;
        } else {
            $cart[$key] = [
                'id' => $planId,
                'name' => $name,
                'price' => $price,
                'image' => $image,
                'quantity' => 1,
                'options' => $options,
            ];
        }

        session()->put('cart', $cart);
        $this->cart = $cart;

        $this->dispatch('cart-updated');
        $this->dispatch('notify', message: __('Added to cart!'), type: 'success');
    }

    public function removeFromCart(string $key): void
    {
        $cart = session()->get('cart', []);
        unset($cart[$key]);
        session()->put('cart', $cart);
        $this->cart = $cart;

        $this->dispatch('cart-updated');
    }

    public function updateQuantity(string $key, int $quantity): void
    {
        if ($quantity < 1) {
            $this->removeFromCart($key);
            return;
        }

        $cart = session()->get('cart', []);
        if (isset($cart[$key])) {
            $cart[$key]['quantity'] = $quantity;
            session()->put('cart', $cart);
            $this->cart = $cart;
        }

        $this->dispatch('cart-updated');
    }

    public function clearCart(): void
    {
        session()->forget('cart');
        $this->cart = [];
        $this->dispatch('cart-updated');
    }

    public function getTotalProperty(): float
    {
        return array_sum(array_map(fn($item) => $item['price'] * $item['quantity'], $this->cart));
    }

    public function getCountProperty(): int
    {
        return array_sum(array_column($this->cart, 'quantity'));
    }

    public function render()
    {
        return view('livewire.cart.cart-manager');
    }
}
