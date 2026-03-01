<div class="relative" x-data="{ open: false }" @click.away="open = false">
<style>
/* ─── Cart Button ───────────────────────────────── */
.cart-btn {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    border-radius: 12px;
    border: 1.5px solid #e8e8ef;
    background: #fff;
    color: #2e2e30;
    cursor: pointer;
    transition: all 0.2s ease;
    padding: 0;
}
.cart-btn:hover {
    border-color: #279ff9;
    color: #279ff9;
    background: #f0f7ff;
    box-shadow: 0 2px 8px rgba(39,159,249,0.12);
}
.cart-btn svg {
    width: 20px;
    height: 20px;
}
.cart-btn__badge {
    position: absolute;
    top: -6px;
    inset-inline-end: -6px;
    min-width: 20px;
    height: 20px;
    padding: 0 5px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #279ff9;
    color: #fff;
    font-size: 0.7rem;
    font-weight: 700;
    border-radius: 100px;
    border: 2px solid #fff;
    line-height: 1;
    box-shadow: 0 2px 6px rgba(39,159,249,0.3);
    animation: cart-badge-pop 0.3s ease;
}
@keyframes cart-badge-pop {
    0% { transform: scale(0); }
    60% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

/* Cart Dropdown */
.cart-dd {
    position: fixed;
    top: 70px;
    inset-inline-end: 16px;
    width: 340px;
    max-width: calc(100vw - 32px);
    z-index: 9998;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    border: 1px solid #eee;
    overflow: hidden;
    font-family: 'Instrument Sans', 'Almarai', ui-sans-serif, system-ui, sans-serif;
}
@media (min-width: 768px) {
    .cart-dd {
        position: absolute;
        top: 100%;
        margin-top: 8px;
        inset-inline-end: 0;
        width: 360px;
        max-width: 360px;
    }
}
.cart-dd__inner { padding: 16px; }
.cart-dd__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 12px;
    white-space: nowrap;
}
.cart-dd__title { font-size: 0.95rem; font-weight: 700; color: #2e2e30; }
.cart-dd__clear {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    color: #ff707a;
    background: none;
    border: none;
    cursor: pointer;
    white-space: nowrap;
    transition: opacity 0.2s;
}
.cart-dd__clear:hover { opacity: 0.7; }
.cart-dd__empty { text-align: center; color: #808089; padding: 20px 0; font-size: 0.875rem; }
.cart-dd__items { max-height: 260px; overflow-y: auto; }
.cart-dd__item {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    padding-bottom: 10px;
    margin-bottom: 10px;
    border-bottom: 1px solid #f3f3f3;
}
.cart-dd__item:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
.cart-dd__img {
    width: 56px;
    height: 56px;
    border-radius: 8px;
    object-fit: cover;
    flex-shrink: 0;
    background: #f5f5fa;
}
.cart-dd__detail { flex: 1; min-width: 0; }
.cart-dd__row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 6px;
}
.cart-dd__name {
    font-size: 0.8rem;
    font-weight: 600;
    color: #2e2e30;
    line-height: 1.3;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.cart-dd__remove {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    border-radius: 4px;
    border: none;
    background: rgba(255,112,122,0.1);
    color: #ff707a;
    cursor: pointer;
    padding: 0;
    transition: all 0.2s;
}
.cart-dd__remove:hover { background: #ff707a; color: #fff; }
.cart-dd__meta { font-size: 0.7rem; color: #808089; margin-top: 2px; }
.cart-dd__bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 4px;
}
.cart-dd__price { font-size: 0.8rem; font-weight: 700; color: #279ff9; }
.cart-dd__qty {
    display: inline-flex;
    align-items: center;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    overflow: hidden;
}
.cart-dd__qty-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    font-size: 13px;
    font-weight: 600;
    color: #666;
    background: #f5f5fa;
    border: none;
    cursor: pointer;
    transition: all 0.15s;
    padding: 0;
}
.cart-dd__qty-btn:hover { background: #279ff9; color: #fff; }
.cart-dd__qty-val { width: 24px; text-align: center; font-size: 0.75rem; font-weight: 600; color: #2e2e30; }
.cart-dd__footer { border-top: 1px solid #f0f0f0; padding-top: 12px; margin-top: 12px; }
.cart-dd__total {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
    font-size: 0.875rem;
    font-weight: 600;
    color: #2e2e30;
}
.cart-dd__total-val { font-size: 1.1rem; font-weight: 700; color: #279ff9; }
.cart-dd__checkout {
    display: block;
    width: 100%;
    padding: 10px;
    text-align: center;
    background: #279ff9;
    color: #fff !important;
    font-weight: 700;
    font-size: 0.875rem;
    border-radius: 8px;
    transition: background 0.2s;
    text-decoration: none;
}
.cart-dd__checkout:hover { background: #1e8de0; }
</style>

    {{-- Cart Button --}}
    <button
        @click="open = !open"
        class="cart-btn"
        aria-label="{{ __('Cart') }}"
    >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
        </svg>
        @if($this->count > 0)
            <span class="cart-btn__badge">{{ $this->count }}</span>
        @endif
    </button>

    {{-- Cart Dropdown --}}
    <div
        x-show="open"
        x-transition
        class="cart-dd"
        style="display: none;"
    >
        <div class="cart-dd__inner">
            {{-- Header with Clear All --}}
            <div class="cart-dd__header">
                <h3 class="cart-dd__title">{{ __('Your Cart') }} ({{ $this->count }})</h3>
                @if(!empty($cart))
                    <button wire:click="clearCart" class="cart-dd__clear">
                        <svg style="width:14px;height:14px" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                        </svg>
                        {{ __('Clear All') }}
                    </button>
                @endif
            </div>

            @if(empty($cart))
                <p class="cart-dd__empty">{{ __('Your cart is empty') }}</p>
            @else
                <div class="cart-dd__items">
                    @foreach($cart as $key => $item)
                        <div class="cart-dd__item">
                            <img
                                src="{{ $item['image'] }}"
                                alt="{{ $item['name'] }}"
                                class="cart-dd__img"
                                onerror="this.src='{{ asset('assets/images/plan-1.png') }}'"
                            >
                            <div class="cart-dd__detail">
                                <div class="cart-dd__row">
                                    <h4 class="cart-dd__name">{{ $item['name'] }}</h4>
                                    <button wire:click="removeFromCart('{{ $key }}')" class="cart-dd__remove" title="{{ __('Remove') }}">
                                        <svg style="width:12px;height:12px" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                @if(!empty($item['options']))
                                    <div class="cart-dd__meta">
                                        @if(!empty($item['options']['mealType']))
                                            <span>{{ __(ucfirst($item['options']['mealType'])) }}</span>
                                        @endif
                                        @if(!empty($item['options']['calories']))
                                            <span style="margin:0 4px;color:#ccc">|</span>
                                            <span>{{ $item['options']['calories'] }} {{ __('kcal') }}</span>
                                        @endif
                                    </div>
                                @endif
                                <div class="cart-dd__bottom">
                                    <span class="cart-dd__price">{{ __('SAR') }} {{ number_format($item['price'], 0) }}</span>
                                    <div class="cart-dd__qty">
                                        <button wire:click="updateQuantity('{{ $key }}', {{ $item['quantity'] - 1 }})" class="cart-dd__qty-btn">-</button>
                                        <span class="cart-dd__qty-val">{{ $item['quantity'] }}</span>
                                        <button wire:click="updateQuantity('{{ $key }}', {{ $item['quantity'] + 1 }})" class="cart-dd__qty-btn">+</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="cart-dd__footer">
                    <div class="cart-dd__total">
                        <span>{{ __('Total') }}:</span>
                        <span class="cart-dd__total-val">{{ __('SAR') }} {{ number_format($this->total, 0) }}</span>
                    </div>
                    <a href="{{ route('checkout.index') }}" class="cart-dd__checkout">
                        {{ __('Checkout') }}
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
