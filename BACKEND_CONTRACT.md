# External API — Subscriptions contract (verified 2026-06-01)

Base URL: `config('services.external_api.url')` (currently `https://dietdev-ledsvd8q.on-forge.com/api` in dev).

## Auth model (verified by probe)

| Endpoint group | `EXTERNAL_API_TOKEN` (app) | Customer Sanctum (`session('external_api_token')`) |
|---|---|---|
| `GET /programs`, catalog, zones, branches | Yes | N/A |
| `GET/POST /subscriptions`, `POST /subscriptions/calculate` | **401 Unauthenticated** | **Required** |
| `GET/POST /orders` | **401 Unauthenticated** | **Required** |

**Website rule:** subscription checkout and order checkout must use the **customer token** from OTP (`AccountApiService::authed()`). `ExternalDataService::createSubscription()` / `calculateSubscription()` use the app token and will not work for checkout.

Probe script: `php scripts/probe-subscriptions-api.php`

---

## `POST /subscriptions/calculate`

**Purpose:** Authoritative pricing (subtotal, VAT, delivery, discount, total). Website must charge Moyasar using this total when a customer token is available.

**Content-Type:** `application/x-www-form-urlencoded` (`asForm()`)

**Auth:** Customer Sanctum token

### Request fields (from checkout promo validation + program detail shape)

| Field | Type | Required | Notes |
|---|---|---|---|
| `program_id` | string | Yes | Program (meal plan) id, e.g. `14` |
| `plan_id` | string | Yes | **Subscription plan** id (variant under program), e.g. `36` — not the program id |
| `plan_duration_id` | string | Yes | Duration package id from `GET /programs/{id}/durations` or plan `durations[]` |
| `plan_calory_id` | string | Yes | Calorie option id from subscription plan `calories[]` |
| `receiving` | string | Yes | `delivery` (home) or `pickup` (branch) — inferred from `delivery_type` |
| `with_support` | string | No | Checkout sends `0` |
| `with_weekend` | string | No | Checkout sends `0` |
| `promocode_name` | string | No | Coupon code when applied |
| `address_id` | string | Conditional | Home delivery — saved address id |
| `branch_id` | string | Conditional | Pickup — branch id |
| `start_date` | string | Likely | `Y-m-d` — included on create; add to calculate when supported |

### Response (inferred; normalize defensively)

Wrapper: `{ "success": true, "data": { ... } }` or top-level `data`.

| Key | Meaning |
|---|---|
| `total` / `grand_total` / `amount` | VAT-inclusive payable total (SAR) |
| `subtotal` / `price` | Plan line before delivery |
| `vat` / `tax` | VAT portion |
| `delivery` / `delivery_price` | Delivery fee |
| `discount` / `discount_amount` | Promo discount |
| `promocode` | Present when promo applied |

---

## `POST /subscriptions`

**Purpose:** Create a program subscription owned by the authenticated customer. Same entity as `GET /subscriptions` (app + website dashboard).

**Auth:** Customer Sanctum token

**Content-Type:** `application/x-www-form-urlencoded`

### Request fields (create = calculate fields + payment)

| Field | Type | Notes |
|---|---|---|
| (all calculate fields) | | |
| `payment_option` | string | `credit_card` after Moyasar success (matches `/orders`) |
| `useWallet` | string | `0` or `1` |
| `start_date` | string | Subscription start `Y-m-d` |
| `note` | string | Optional checkout note |

Legacy / optional (when no subscription plan variant):

| Field | Notes |
|---|---|
| `meal_type` | From cart `options.mealType` when `subscription_plan_id` absent |

### Payment ordering (inferred)

1. Website collects payment via Moyasar (local `Payment` pending → paid).
2. On paid callback, website calls `POST /subscriptions` with `payment_option=credit_card` and full cart payload.
3. If needed, `POST /subscriptions/start` with `{ date }` schedules/activates — subscription must already exist.

`subscriptions/start` accepts **only** `date`; it does not create a subscription.

### Response (normalize defensively)

| Path | Field |
|---|---|
| `data.id` / `data.subscription.id` | External subscription id |
| `data.subscription_number` / `data.number` | Human-readable reference |

---

## `POST /subscriptions/start`

| Field | Required |
|---|---|
| `date` | Yes (`Y-m-d`) |

Called after successful create when `start_date` should be applied (website uses `Payment.start_date`).

---

## Orders vs subscriptions

| Checkout cart | External entity | Endpoint |
|---|---|---|
| `session(subscription_cart)` with `duration_days` / program line | Subscription | `POST /subscriptions` |
| `session(cart)` meal lines (`meal_*` keys) | Instant meal order | `POST /orders` (existing `syncPaidPaymentToExternalOrder`) |

---

## Example ids (dev API, program 14)

- `program_id`: 14
- `plan_id` (subscription plan): 36
- `plan_duration_id`: 265
- `plan_calory_id`: 104

---

## Production checklist

- Set `EXTERNAL_API_URL` to production API in `.env` (not Forge staging default).
- Ensure checkout OTP stores `external_api_token` before payment callback runs.
- Do not rely on `ExternalDataService` for authenticated subscription create/calculate.
