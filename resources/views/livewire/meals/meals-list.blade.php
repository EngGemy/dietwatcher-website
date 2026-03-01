<div class="relative">
<style>
/* ─── Search & Filter Bar ────────────────────────── */
.meals-toolbar {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    background: #fff;
    border-radius: 16px;
    padding: 1.25rem 1.5rem;
    box-shadow: 0 4px 24px rgba(0,0,0,0.06);
    margin-top: -2rem;
    position: relative;
    z-index: 10;
    margin-bottom: 2rem;
}
@media (min-width: 768px) {
    .meals-toolbar { flex-direction: row; align-items: center; }
}
.meals-search {
    position: relative;
    flex: 1;
    min-width: 0;
}
.meals-search__icon {
    position: absolute;
    top: 50%;
    inset-inline-start: 14px;
    transform: translateY(-50%);
    width: 18px;
    height: 18px;
    color: #999;
    pointer-events: none;
}
.meals-search__input {
    width: 100%;
    padding: 0.7rem 0.75rem 0.7rem 2.6rem;
    border: 1.5px solid #e8e8ef;
    border-radius: 10px;
    font-size: 0.9rem;
    color: #2e2e30;
    background: #f9f9fc;
    transition: all 0.2s;
    outline: none;
}
[dir="rtl"] .meals-search__input { padding: 0.7rem 2.6rem 0.7rem 0.75rem; }
.meals-search__input::placeholder { color: #aaa; }
.meals-search__input:focus {
    border-color: #279ff9;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(39,159,249,0.1);
}
.meals-search__clear {
    position: absolute;
    top: 50%;
    inset-inline-end: 10px;
    transform: translateY(-50%);
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #e8e8ef;
    color: #666;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    cursor: pointer;
    transition: all 0.15s;
    padding: 0;
}
.meals-search__clear:hover { background: #ff707a; color: #fff; }

/* ─── Tag Filters ────────────────────────────────── */
.meals-tags {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}
.meals-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.4rem 1rem;
    border-radius: 100px;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border: 1.5px solid #e0e0e8;
    background: #fff;
    color: #555;
    white-space: nowrap;
}
.meals-tag:hover { border-color: #279ff9; color: #279ff9; }
.meals-tag--active {
    background: #279ff9;
    color: #fff;
    border-color: #279ff9;
    box-shadow: 0 2px 8px rgba(39,159,249,0.3);
}
.meals-tag--active:hover { background: #1e8de0; border-color: #1e8de0; color: #fff; }
.meals-tag__icon {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    object-fit: cover;
}

/* ─── Results Info ───────────────────────────────── */
.meals-info {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 0.5rem;
}
.meals-info__count {
    font-size: 0.9rem;
    color: #808089;
}
.meals-info__count strong {
    color: #2e2e30;
    font-weight: 700;
}

/* ─── Meal Cards Grid ────────────────────────────── */
.meals-grid {
    display: grid;
    gap: 1.5rem;
    grid-template-columns: 1fr;
}
@media (min-width: 640px) { .meals-grid { grid-template-columns: repeat(2, 1fr); } }
@media (min-width: 1024px) { .meals-grid { grid-template-columns: repeat(3, 1fr); } }
@media (min-width: 1280px) { .meals-grid { grid-template-columns: repeat(4, 1fr); } }

/* ─── Meal Card ──────────────────────────────────── */
.mcard {
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    border: 1px solid transparent;
    position: relative;
}
.mcard:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 32px rgba(0,0,0,0.1);
    border-color: rgba(39,159,249,0.15);
}

/* Image */
.mcard__img-wrap {
    position: relative;
    aspect-ratio: 4/3;
    overflow: hidden;
    background: #f0f0f5;
}
.mcard__img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}
.mcard:hover .mcard__img { transform: scale(1.06); }
.mcard__badge {
    position: absolute;
    top: 12px;
    inset-inline-start: 12px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 0.25rem 0.65rem;
    background: rgba(255,255,255,0.92);
    backdrop-filter: blur(6px);
    border-radius: 100px;
    font-size: 0.7rem;
    font-weight: 600;
    color: #555;
}
.mcard__badge-icon { width: 14px; height: 14px; border-radius: 50%; object-fit: cover; }

/* Rating */
.mcard__rating {
    position: absolute;
    top: 12px;
    inset-inline-end: 12px;
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 0.2rem 0.55rem;
    background: rgba(0,0,0,0.55);
    backdrop-filter: blur(6px);
    border-radius: 100px;
    font-size: 0.7rem;
    font-weight: 700;
    color: #fff;
}
.mcard__star { color: #FFC400; }

/* Body */
.mcard__body { padding: 1rem 1.15rem 1.25rem; }
.mcard__tag {
    display: inline-block;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: #279ff9;
    margin-bottom: 0.35rem;
}
.mcard__name {
    font-size: 0.95rem;
    font-weight: 700;
    color: #2e2e30;
    line-height: 1.35;
    margin-bottom: 0.5rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 2.6em;
}
.mcard__footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
}
.mcard__price-wrap { display: flex; align-items: baseline; gap: 0.4rem; }
.mcard__price {
    font-size: 1.1rem;
    font-weight: 800;
    color: #2e2e30;
}
.mcard__price-currency {
    font-size: 0.75rem;
    font-weight: 600;
    color: #999;
}
.mcard__price-old {
    font-size: 0.8rem;
    color: #bbb;
    text-decoration: line-through;
    font-weight: 500;
}

/* Add to cart button */
.mcard__cart-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    border-radius: 10px;
    border: none;
    background: #279ff9;
    color: #fff;
    cursor: pointer;
    transition: all 0.2s;
    flex-shrink: 0;
    padding: 0;
}
.mcard__cart-btn:hover {
    background: #1e8de0;
    transform: scale(1.08);
    box-shadow: 0 4px 12px rgba(39,159,249,0.35);
}
.mcard__cart-btn:active { transform: scale(0.95); }
.mcard__cart-btn svg { width: 18px; height: 18px; }

/* ─── Empty State ────────────────────────────────── */
.meals-empty {
    grid-column: 1 / -1;
    text-align: center;
    padding: 4rem 1rem;
}
.meals-empty__icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 1.25rem;
    background: #e8e8ef;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}
.meals-empty__icon svg { width: 36px; height: 36px; color: #bbb; }
.meals-empty__title { font-size: 1.25rem; font-weight: 700; color: #2e2e30; margin-bottom: 0.5rem; }
.meals-empty__desc { color: #808089; font-size: 0.9rem; }

/* ─── Pagination ─────────────────────────────────── */
.meals-pager {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    margin-top: 2.5rem;
}
.meals-pager__btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
    border-radius: 10px;
    border: 1.5px solid #e0e0e8;
    background: #fff;
    color: #555;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    padding: 0 0.5rem;
}
.meals-pager__btn:hover:not(:disabled) { border-color: #279ff9; color: #279ff9; }
.meals-pager__btn--active {
    background: #279ff9;
    color: #fff;
    border-color: #279ff9;
    box-shadow: 0 2px 8px rgba(39,159,249,0.25);
}
.meals-pager__btn--active:hover { background: #1e8de0; color: #fff; border-color: #1e8de0; }
.meals-pager__btn:disabled { opacity: 0.35; cursor: not-allowed; }
.meals-pager__btn svg { width: 16px; height: 16px; }

/* ─── Loading ────────────────────────────────────── */
.meals-loading {
    position: absolute;
    inset: 0;
    background: rgba(245,245,250,0.6);
    backdrop-filter: blur(2px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 5;
    border-radius: 12px;
}
.meals-spinner {
    width: 36px;
    height: 36px;
    border: 3px solid #e0e0e8;
    border-top-color: #279ff9;
    border-radius: 50%;
    animation: meals-spin 0.7s linear infinite;
}
@keyframes meals-spin { to { transform: rotate(360deg); } }

/* ─── Animations ─────────────────────────────────── */
@keyframes mcard-in {
    from { opacity: 0; transform: translateY(16px); }
    to { opacity: 1; transform: translateY(0); }
}
.mcard { animation: mcard-in 0.4s ease both; }
.mcard:nth-child(2) { animation-delay: 0.05s; }
.mcard:nth-child(3) { animation-delay: 0.1s; }
.mcard:nth-child(4) { animation-delay: 0.15s; }
.mcard:nth-child(5) { animation-delay: 0.2s; }
.mcard:nth-child(6) { animation-delay: 0.25s; }
.mcard:nth-child(7) { animation-delay: 0.3s; }
.mcard:nth-child(8) { animation-delay: 0.35s; }
</style>

    {{-- Loading Overlay --}}
    <div wire:loading.delay class="meals-loading">
        <div class="meals-spinner"></div>
    </div>

    {{-- Search & Filters Toolbar --}}
    <div class="meals-toolbar">
        {{-- Search --}}
        <div class="meals-search">
            <svg class="meals-search__icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                class="meals-search__input"
                placeholder="{{ __('Search meals...') }}"
            />
            @if($search)
                <button wire:click="$set('search', '')" class="meals-search__clear" title="{{ __('Clear') }}">
                    <svg style="width:12px;height:12px" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            @endif
        </div>

        {{-- Group Filters --}}
        @if(!empty($groups))
            <div class="meals-tags">
                <button
                    type="button"
                    wire:click="filterByGroup(null)"
                    class="meals-tag {{ $selectedGroup === null ? 'meals-tag--active' : '' }}"
                >
                    {{ __('All') }}
                </button>
                @foreach($groups as $group)
                    <button
                        type="button"
                        wire:click="filterByGroup({{ $selectedGroup === $group['value'] ? 'null' : $group['value'] }})"
                        class="meals-tag {{ $selectedGroup === $group['value'] ? 'meals-tag--active' : '' }}"
                    >
                        @if(!empty($group['icon']))
                            <img src="{{ $group['icon'] }}" alt="" class="meals-tag__icon" />
                        @endif
                        {{ $group['name'] }}
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Results Info --}}
    <div class="meals-info">
        <p class="meals-info__count">
            @if($search)
                {{ __('Results for') }} "<strong>{{ $search }}</strong>"
            @else
                <strong>{{ count($meals) }}</strong> {{ __('meals available') }}
            @endif
        </p>
    </div>

    {{-- Meals Grid --}}
    <div class="meals-grid" wire:key="meals-grid-{{ $currentPage }}-{{ $selectedGroup }}-{{ $search }}">
        @forelse($meals as $meal)
            @php
                $mealImg = $meal['image_url'] ?? '';
                $mealImgUrl = str_starts_with($mealImg, 'http') ? $mealImg : ($mealImg ? asset($mealImg) : asset('assets/images/meal-' . ($loop->iteration % 3 === 0 ? 3 : $loop->iteration % 3) . '.png'));
                $mealFallback = asset('assets/images/meal-' . ($loop->iteration % 3 === 0 ? 3 : $loop->iteration % 3) . '.png');
                $effectivePrice = ($meal['offer_price'] ?? 0) > 0 && ($meal['offer_price'] < $meal['price']) ? $meal['offer_price'] : $meal['price'];
                $hasOffer = ($meal['offer_price'] ?? 0) > 0 && $meal['offer_price'] < $meal['price'];
                $category = $meal['categories'][0] ?? null;
            @endphp
            <div class="mcard" wire:key="meal-{{ $meal['id'] }}">
                {{-- Image --}}
                <div class="mcard__img-wrap">
                    <img
                        src="{{ $mealImgUrl }}"
                        alt="{{ $meal['name'] }}"
                        class="mcard__img"
                        loading="lazy"
                        onerror="this.src='{{ $mealFallback }}'"
                    />
                    @if($category)
                        <span class="mcard__badge">
                            @if(!empty($category['icon']))
                                <img src="{{ $category['icon'] }}" alt="" class="mcard__badge-icon" />
                            @endif
                            {{ $category['name'] }}
                        </span>
                    @endif
                    @if(($meal['rate'] ?? 0) > 0)
                        <span class="mcard__rating">
                            <span class="mcard__star">&#9733;</span>
                            {{ $meal['rate'] }}
                        </span>
                    @endif
                </div>

                {{-- Body --}}
                <div class="mcard__body">
                    @if(!empty($meal['tag_name']))
                        <span class="mcard__tag">{{ $meal['tag_name'] }}</span>
                    @endif
                    <h3 class="mcard__name">{{ $meal['name'] }}</h3>

                    <div class="mcard__footer">
                        <div class="mcard__price-wrap">
                            <span class="mcard__price-currency">{{ __('SAR') }}</span>
                            <span class="mcard__price">{{ number_format($effectivePrice, 0) }}</span>
                            @if($hasOffer)
                                <span class="mcard__price-old">{{ number_format($meal['price'], 0) }}</span>
                            @endif
                        </div>

                        <button
                            type="button"
                            class="mcard__cart-btn"
                            wire:click="$dispatch('add-to-cart', { planId: {{ $meal['id'] }}, name: '{{ addslashes($meal['name']) }}', price: {{ $effectivePrice }}, image: '{{ addslashes($mealImgUrl) }}' })"
                            title="{{ __('Add to Cart') }}"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="meals-empty">
                <div class="meals-empty__icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </div>
                <h3 class="meals-empty__title">
                    @if($search)
                        {{ __('No results for') }} "{{ $search }}"
                    @else
                        {{ __('No meals found') }}
                    @endif
                </h3>
                <p class="meals-empty__desc">
                    @if($search)
                        {{ __('Try a different search term or clear filters.') }}
                    @else
                        {{ __('Try adjusting your filters.') }}
                    @endif
                </p>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($lastPage > 1)
        <div class="meals-pager">
            <button
                wire:click="prevPage"
                @if($currentPage <= 1) disabled @endif
                class="meals-pager__btn"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </button>

            @for($i = 1; $i <= $lastPage; $i++)
                <button
                    wire:click="goToPage({{ $i }})"
                    class="meals-pager__btn {{ $currentPage === $i ? 'meals-pager__btn--active' : '' }}"
                >
                    {{ $i }}
                </button>
            @endfor

            <button
                wire:click="nextPage"
                @if($currentPage >= $lastPage) disabled @endif
                class="meals-pager__btn"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </button>
        </div>
    @endif
</div>
