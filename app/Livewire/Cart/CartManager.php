<?php

declare(strict_types=1);

namespace App\Livewire\Cart;

use Livewire\Attributes\On;
use Livewire\Component;

class CartManager extends Component
{
    public const SESSION_MARKET = 'cart';

    public const SESSION_SUBSCRIPTION = 'subscription_cart';

    public array $cart = [];

    public function mount(): void
    {
        $this->migrateLegacyMarketCartKeys();
        $this->cart = $this->marketCartItems();
    }

    /**
     * Legacy rows used plan_{mealId} for shop meals. Normalize to meal_{id} and drop stray subscription lines.
     */
    private function migrateLegacyMarketCartKeys(): void
    {
        $cart = session()->get(self::SESSION_MARKET, []);
        if ($cart === []) {
            return;
        }

        $out = [];
        foreach ($cart as $key => $item) {
            if (is_string($key) && str_starts_with($key, 'plan_')
                && ! empty($item['options']['duration_days'])) {
                session()->put(self::SESSION_SUBSCRIPTION, [$key => $item]);
                session()->forget(self::SESSION_MARKET);

                return;
            }
            if (is_string($key) && str_starts_with($key, 'plan_')
                && empty($item['options']['duration_days'] ?? null)) {
                $mealId = (int) substr($key, 5);
                $out['meal_'.$mealId] = $item;

                continue;
            }
            if (is_string($key) && str_starts_with($key, 'meal_')) {
                $out[$key] = $item;
            }
        }

        if ($out !== $cart) {
            session()->put(self::SESSION_MARKET, $out);
        }
    }

    /** @return array<string, mixed> */
    private function marketCartItems(): array
    {
        $cart = session()->get(self::SESSION_MARKET, []);

        return array_filter(
            $cart,
            static fn ($key) => is_string($key) && str_starts_with($key, 'meal_'),
            ARRAY_FILTER_USE_KEY
        );
    }

    #[On('cart-updated')]
    public function updateCart(): void
    {
        $this->cart = $this->marketCartItems();
    }

    #[On('add-to-cart')]
    public function handleAddToCart(
        int $planId = 0,
        int $mealId = 0,
        string $name = '',
        float $price = 0,
        string $image = '',
        array $options = []
    ): void {
        $id = $mealId > 0 ? $mealId : $planId;
        if ($id === 0) {
            return;
        }

        $this->addMarketMeal($id, $name, $price, $image, $options);
    }

    #[On('decrement-cart-item')]
    public function handleDecrement(int $planId = 0, int $mealId = 0): void
    {
        $id = $mealId > 0 ? $mealId : $planId;
        if ($id === 0) {
            return;
        }

        $key = 'meal_'.$id;
        $cart = session()->get(self::SESSION_MARKET, []);

        if (! isset($cart[$key])) {
            return;
        }

        if ($cart[$key]['quantity'] <= 1) {
            unset($cart[$key]);
        } else {
            $cart[$key]['quantity']--;
        }

        session()->put(self::SESSION_MARKET, $cart);
        $this->cart = $this->marketCartItems();
        $this->dispatch('cart-updated');
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function addMarketMeal(int $mealId, string $name, float $price, string $image, array $options = []): void
    {
        session()->forget(self::SESSION_SUBSCRIPTION);

        $cart = session()->get(self::SESSION_MARKET, []);
        foreach (array_keys($cart) as $k) {
            if (is_string($k) && str_starts_with($k, 'plan_')) {
                unset($cart[$k]);
            }
        }

        $key = 'meal_'.$mealId;

        if (isset($cart[$key])) {
            $cart[$key]['quantity']++;
        } else {
            $cart[$key] = [
                'id' => $mealId,
                'name' => $name,
                'price' => $price,
                'image' => $image,
                'quantity' => 1,
                'options' => array_merge($options, ['item_type' => 'meal']),
            ];
        }

        session()->put(self::SESSION_MARKET, $cart);
        $this->cart = $this->marketCartItems();

        $this->dispatch('cart-updated');
        $this->dispatch('notify', message: __('Added to cart!'), type: 'success');
    }

    public function removeFromCart(string $key): void
    {
        if (! str_starts_with($key, 'meal_')) {
            return;
        }

        $cart = session()->get(self::SESSION_MARKET, []);
        unset($cart[$key]);
        session()->put(self::SESSION_MARKET, $cart);
        $this->cart = $this->marketCartItems();

        $this->dispatch('cart-updated');
    }

    public function updateQuantity(string $key, int $quantity): void
    {
        if (! str_starts_with($key, 'meal_')) {
            return;
        }

        if ($quantity < 1) {
            $this->removeFromCart($key);

            return;
        }

        $cart = session()->get(self::SESSION_MARKET, []);
        if (isset($cart[$key])) {
            $cart[$key]['quantity'] = $quantity;
            session()->put(self::SESSION_MARKET, $cart);
            $this->cart = $this->marketCartItems();
        }

        $this->dispatch('cart-updated');
    }

    public function clearCart(): void
    {
        session()->forget(self::SESSION_MARKET);
        $this->cart = [];
        $this->dispatch('cart-updated');
    }

    public function getTotalProperty(): float
    {
        return array_sum(array_map(fn ($item) => $item['price'] * $item['quantity'], $this->cart));
    }

    public function getCountProperty(): int
    {
        return (int) array_sum(array_column($this->cart, 'quantity'));
    }

    public function render()
    {
        return view('livewire.cart.cart-manager');
    }
}
