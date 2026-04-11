<div class="relative">

{{-- ═══════════════════════════════════════════════════════
     STYLES
═══════════════════════════════════════════════════════════ --}}
<style>
/* ─── Toolbar ─────────────────────────────────────── */
.meals-toolbar{display:flex;flex-direction:column;gap:1rem;background:#fff;border-radius:16px;padding:1.25rem 1.5rem;box-shadow:0 4px 24px rgba(0,0,0,.06);margin-top:-2rem;position:relative;z-index:10;margin-bottom:2rem}
@media(min-width:768px){.meals-toolbar{flex-direction:row;align-items:center}}

/* Search */
.meals-search{position:relative;flex:1;min-width:0}
.meals-search__icon{position:absolute;top:50%;inset-inline-start:14px;transform:translateY(-50%);width:18px;height:18px;color:#999;pointer-events:none}
.meals-search__input{width:100%;padding:.7rem .75rem .7rem 2.6rem;border:1.5px solid #e8e8ef;border-radius:10px;font-size:.9rem;color:#2e2e30;background:#f9f9fc;transition:all .2s;outline:none}
[dir="rtl"] .meals-search__input{padding:.7rem 2.6rem .7rem .75rem}
.meals-search__input::placeholder{color:#aaa}
.meals-search__input:focus{border-color:#279ff9;background:#fff;box-shadow:0 0 0 3px rgba(39,159,249,.1)}
.meals-search__clear{position:absolute;top:50%;inset-inline-end:10px;transform:translateY(-50%);width:22px;height:22px;border-radius:50%;background:#e8e8ef;color:#666;display:flex;align-items:center;justify-content:center;border:none;cursor:pointer;transition:all .15s;padding:0}
.meals-search__clear:hover{background:#ff707a;color:#fff}

/* Filter Tags */
.meals-tags{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap}
.meals-tag{display:inline-flex;align-items:center;gap:.35rem;padding:.4rem 1rem;border-radius:100px;font-size:.8rem;font-weight:600;cursor:pointer;transition:all .2s;border:1.5px solid #e0e0e8;background:#fff;color:#555;white-space:nowrap}
.meals-tag:hover{border-color:#279ff9;color:#279ff9}
.meals-tag--active{background:#279ff9;color:#fff;border-color:#279ff9;box-shadow:0 2px 8px rgba(39,159,249,.3)}
.meals-tag--active:hover{background:#1e8de0;border-color:#1e8de0;color:#fff}
.meals-tag__icon{width:16px;height:16px;border-radius:50%;object-fit:cover}

/* Results info */
.meals-info{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:.5rem}
.meals-info__count{font-size:.9rem;color:#808089}
.meals-info__count strong{color:#2e2e30;font-weight:700}

/* Grid */
.meals-grid{display:grid;align-items:stretch;gap:1.5rem;grid-template-columns:1fr}
@media(min-width:640px){.meals-grid{grid-template-columns:repeat(2,1fr)}}
@media(min-width:1024px){.meals-grid{grid-template-columns:repeat(3,1fr)}}
@media(min-width:1280px){.meals-grid{grid-template-columns:repeat(4,1fr)}}

/* ─── Card ─────────────────────────────────────────── */
.mcard{background:#fff;border-radius:16px;overflow:hidden;transition:all .3s cubic-bezier(.25,.46,.45,.94);border:1px solid transparent;position:relative;display:flex;flex-direction:column;height:100%}
.mcard:hover{transform:translateY(-4px);box-shadow:0 12px 32px rgba(0,0,0,.1);border-color:rgba(39,159,249,.15)}

.mcard__img-wrap{position:relative;display:block;aspect-ratio:4/3;overflow:hidden;background:#f0f0f5;cursor:pointer;flex-shrink:0;text-decoration:none;color:inherit}
.mcard__img{width:100%;height:100%;object-fit:cover;transition:transform .4s ease}
.mcard:hover .mcard__img{transform:scale(1.06)}
.mcard__name a{color:inherit;text-decoration:none}
.mcard__name a:hover{color:#279ff9}

.mcard__badge{position:absolute;top:10px;inset-inline-start:10px;display:inline-flex;align-items:center;gap:4px;padding:.22rem .6rem;background:rgba(255,255,255,.92);backdrop-filter:blur(6px);border-radius:100px;font-size:.68rem;font-weight:700;color:#555;pointer-events:none;text-transform:uppercase;letter-spacing:.02em}
.mcard__badge-icon{width:14px;height:14px;border-radius:50%;object-fit:cover}

.mcard__rating{position:absolute;top:10px;inset-inline-end:10px;display:inline-flex;align-items:center;gap:3px;padding:.2rem .5rem;background:rgba(0,0,0,.55);backdrop-filter:blur(6px);border-radius:100px;font-size:.7rem;font-weight:700;color:#fff;pointer-events:none}
.mcard__star{color:#FFC400}

.mcard__body{padding:.9rem 1rem 1rem;flex:1;display:flex;flex-direction:column;min-height:0}
.mcard__tag{display:inline-block;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#279ff9;margin-bottom:.3rem}
.mcard__name{font-size:.92rem;font-weight:700;color:#2e2e30;line-height:1.35;margin-bottom:.5rem;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;min-height:calc(1.35em * 3);flex-shrink:0}

.mcard__footer{display:flex;align-items:center;justify-content:space-between;gap:.5rem;margin-top:auto;flex-shrink:0;padding-top:.35rem}
.mcard__price-wrap{display:flex;align-items:baseline;gap:.3rem;flex-wrap:wrap}
.mcard__price{font-size:1.05rem;font-weight:800;color:#2e2e30}
.mcard__currency{font-size:.72rem;font-weight:600;color:#999}
.mcard__price-old{font-size:.78rem;color:#bbb;text-decoration:line-through;font-weight:500}
.mcard__offer-badge{font-size:.65rem;font-weight:700;background:#ff707a;color:#fff;padding:.1rem .4rem;border-radius:4px}

/* Add button (when qty = 0) */
.mcard__cart-btn{display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:10px;border:none;background:#279ff9;color:#fff;cursor:pointer;transition:all .2s;flex-shrink:0;padding:0}
.mcard__cart-btn:hover{background:#1e8de0;transform:scale(1.08);box-shadow:0 4px 12px rgba(39,159,249,.35)}
.mcard__cart-btn:active{transform:scale(.95)}
.mcard__cart-btn svg{width:17px;height:17px}

/* Qty stepper (when qty > 0) */
.mcard__qty{display:inline-flex;align-items:center;border-radius:10px;overflow:hidden;border:1.5px solid #279ff9;flex-shrink:0}
.mcard__qty-btn{display:flex;align-items:center;justify-content:center;width:30px;height:30px;background:#fff;border:none;cursor:pointer;transition:all .15s;padding:0;color:#279ff9;font-size:1rem;font-weight:700}
.mcard__qty-btn:hover{background:#279ff9;color:#fff}
.mcard__qty-btn svg{width:14px;height:14px}
.mcard__qty-val{width:28px;text-align:center;font-size:.8rem;font-weight:700;color:#279ff9;background:#fff;line-height:30px}

/* ─── Empty ─────────────────────────────────────────── */
.meals-empty{grid-column:1/-1;text-align:center;padding:4rem 1rem}
.meals-empty__icon{width:80px;height:80px;margin:0 auto 1.25rem;background:#e8e8ef;border-radius:50%;display:flex;align-items:center;justify-content:center}
.meals-empty__icon svg{width:36px;height:36px;color:#bbb}
.meals-empty__title{font-size:1.25rem;font-weight:700;color:#2e2e30;margin-bottom:.5rem}
.meals-empty__desc{color:#808089;font-size:.9rem}

/* ─── Pagination ─────────────────────────────────────── */
.meals-pager{display:flex;align-items:center;justify-content:center;gap:.4rem;margin-top:2.5rem}
.meals-pager__btn{display:inline-flex;align-items:center;justify-content:center;min-width:40px;height:40px;border-radius:10px;border:1.5px solid #e0e0e8;background:#fff;color:#555;font-size:.85rem;font-weight:600;cursor:pointer;transition:all .2s;padding:0 .5rem}
.meals-pager__btn:hover:not(:disabled){border-color:#279ff9;color:#279ff9}
.meals-pager__btn--active{background:#279ff9;color:#fff;border-color:#279ff9;box-shadow:0 2px 8px rgba(39,159,249,.25)}
.meals-pager__btn--active:hover{background:#1e8de0;color:#fff;border-color:#1e8de0}
.meals-pager__btn:disabled{opacity:.35;cursor:not-allowed}
.meals-pager__btn svg{width:16px;height:16px}

/* ─── Loading Overlay ────────────────────────────────── */
.meals-loading{position:absolute;inset:0;background:rgba(245,245,250,.6);backdrop-filter:blur(2px);display:flex;align-items:center;justify-content:center;z-index:5;border-radius:12px}
.meals-spinner{width:36px;height:36px;border:3px solid #e0e0e8;border-top-color:#279ff9;border-radius:50%;animation:meals-spin .7s linear infinite}
@keyframes meals-spin{to{transform:rotate(360deg)}}

/* ─── Card entry animation ───────────────────────────── */
@keyframes mcard-in{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.mcard{animation:mcard-in .4s ease both}
.mcard:nth-child(2){animation-delay:.05s}.mcard:nth-child(3){animation-delay:.1s}
.mcard:nth-child(4){animation-delay:.15s}.mcard:nth-child(5){animation-delay:.2s}
.mcard:nth-child(6){animation-delay:.25s}.mcard:nth-child(7){animation-delay:.3s}
.mcard:nth-child(8){animation-delay:.35s}

</style>

{{-- Loading Overlay --}}
<div wire:loading.delay class="meals-loading">
    <div class="meals-spinner"></div>
</div>

{{-- ─── Search & Filter Toolbar ─────────────────────────── --}}
<div class="meals-toolbar">
    <div class="meals-search">
        <svg class="meals-search__icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
        </svg>
        <input
            type="text"
            wire:model.live.debounce.300ms="search"
            class="meals-search__input"
            placeholder="{{ __('Search meals...') }}"
            autocomplete="off"
        />
        @if($search)
            <button wire:click="$set('search','')" class="meals-search__clear" title="{{ __('Clear') }}">
                <svg style="width:12px;height:12px" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                </svg>
            </button>
        @endif
    </div>

    @if(!empty($groups))
        <div class="meals-tags">
            <button type="button" wire:click="filterByGroup(null)"
                class="meals-tag {{ $selectedGroup === null ? 'meals-tag--active' : '' }}">
                {{ __('All') }}
            </button>
            @foreach($groups as $group)
                <button type="button"
                    wire:click="filterByGroup({{ $selectedGroup === $group['value'] ? 'null' : $group['value'] }})"
                    class="meals-tag {{ $selectedGroup === $group['value'] ? 'meals-tag--active' : '' }}">
                    @if(!empty($group['icon']))
                        <img src="{{ $group['icon'] }}" alt="" class="meals-tag__icon" />
                    @endif
                    {{ $group['name'] }}
                </button>
            @endforeach
        </div>
    @endif
</div>

{{-- Results count --}}
<div class="meals-info">
    <p class="meals-info__count">
        @if($search)
            {{ __('Results for') }} "<strong>{{ $search }}</strong>"
        @else
            <strong>{{ count($meals) }}</strong> {{ __('meals available') }}
        @endif
    </p>
</div>

{{-- ─── Meals Grid ───────────────────────────────────────── --}}
<div class="meals-grid" wire:key="meals-grid-{{ $currentPage }}-{{ $selectedGroup }}-{{ $search }}">
    @forelse($meals as $meal)
        @php
            $mealImg    = $meal['image_url'] ?? '';
            $mealImgUrl = str_starts_with($mealImg, 'http') ? $mealImg : ($mealImg ? asset($mealImg) : asset('assets/images/meal-' . ($loop->iteration % 3 === 0 ? 3 : $loop->iteration % 3) . '.png'));
            $fallback   = asset('assets/images/meal-' . ($loop->iteration % 3 === 0 ? 3 : $loop->iteration % 3) . '.png');
            $effectivePrice = ($meal['offer_price'] ?? 0) > 0 && $meal['offer_price'] < $meal['price'] ? $meal['offer_price'] : $meal['price'];
            $hasOffer   = ($meal['offer_price'] ?? 0) > 0 && $meal['offer_price'] < $meal['price'];
            $category   = $meal['categories'][0] ?? null;
            $cartQty    = $cartItems['meal_' . $meal['id']]['quantity'] ?? 0;
            $discount   = $hasOffer ? round((1 - $meal['offer_price'] / $meal['price']) * 100) : 0;
            $detailUrl = route('store.show', $meal['id']);
        @endphp
        <div class="mcard" wire:key="meal-{{ $meal['id'] }}">

            <a href="{{ $detailUrl }}" class="mcard__img-wrap">
                <img src="{{ $mealImgUrl }}" alt="{{ $meal['name'] }}" class="mcard__img"
                    loading="lazy" onerror="this.src='{{ $fallback }}'"/>

                @if($category)
                    <span class="mcard__badge">
                        @if(!empty($category['icon']))
                            <img src="{{ $category['icon'] }}" alt="" class="mcard__badge-icon"/>
                        @endif
                        {{ $category['name'] }}
                    </span>
                @endif

                @if(($meal['rate'] ?? 0) > 0)
                    <span class="mcard__rating">
                        <span class="mcard__star">&#9733;</span> {{ number_format($meal['rate'], 1) }}
                    </span>
                @endif
            </a>

            {{-- Card Body --}}
            <div class="mcard__body">
                @if(!empty($meal['tag_name']))
                    <span class="mcard__tag">{{ $meal['tag_name'] }}</span>
                @endif

                <h3 class="mcard__name">
                    <a href="{{ $detailUrl }}">{{ $meal['name'] }}</a>
                </h3>

                <div class="mcard__footer">
                    {{-- Price --}}
                    <div class="mcard__price-wrap">
                        <span class="mcard__currency">{{ __('SAR') }}</span>
                        <span class="mcard__price">{{ number_format($effectivePrice, 0) }}</span>
                        @if($hasOffer)
                            <span class="mcard__price-old">{{ number_format($meal['price'], 0) }}</span>
                            <span class="mcard__offer-badge">-{{ $discount }}%</span>
                        @endif
                    </div>

                    {{-- Qty stepper (already in cart) OR Add button --}}
                    @if($cartQty > 0)
                        <div class="mcard__qty">
                            <button type="button" class="mcard__qty-btn"
                                wire:click="$dispatch('decrement-cart-item', { mealId: {{ $meal['id'] }} })"
                                title="{{ $cartQty === 1 ? __('Remove') : __('Decrease') }}">
                                @if($cartQty === 1)
                                    {{-- Trash icon when removing last item --}}
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                    </svg>
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/>
                                    </svg>
                                @endif
                            </button>
                            <span class="mcard__qty-val">{{ $cartQty }}</span>
                            <button type="button" class="mcard__qty-btn"
                                wire:click="$dispatch('add-to-cart', { mealId: {{ $meal['id'] }}, name: '{{ addslashes($meal['name']) }}', price: {{ $effectivePrice }}, image: '{{ addslashes($mealImgUrl) }}' })"
                                title="{{ __('Increase') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                </svg>
                            </button>
                        </div>
                    @else
                        <button type="button" class="mcard__cart-btn"
                            wire:click="$dispatch('add-to-cart', { mealId: {{ $meal['id'] }}, name: '{{ addslashes($meal['name']) }}', price: {{ $effectivePrice }}, image: '{{ addslashes($mealImgUrl) }}' })"
                            title="{{ __('Add to Cart') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="meals-empty">
            <div class="meals-empty__icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
            </div>
            <h3 class="meals-empty__title">
                {{ $search ? __('No results for') . ' "' . $search . '"' : __('No meals found') }}
            </h3>
            <p class="meals-empty__desc">
                {{ $search ? __('Try a different search term or clear filters.') : __('Try adjusting your filters.') }}
            </p>
        </div>
    @endforelse
</div>

{{-- ─── Pagination ───────────────────────────────────────── --}}
@if($lastPage > 1)
    <div class="meals-pager">
        <button wire:click="prevPage" @disabled($currentPage <= 1) class="meals-pager__btn">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/>
            </svg>
        </button>
        @for($i = 1; $i <= $lastPage; $i++)
            <button wire:click="goToPage({{ $i }})"
                class="meals-pager__btn {{ $currentPage === $i ? 'meals-pager__btn--active' : '' }}">
                {{ $i }}
            </button>
        @endfor
        <button wire:click="nextPage" @disabled($currentPage >= $lastPage) class="meals-pager__btn">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
            </svg>
        </button>
    </div>
@endif

</div>
