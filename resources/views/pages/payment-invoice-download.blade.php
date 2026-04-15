<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $payment->order_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 28px; color:#111827; }
        h1 { margin: 0 0 8px; }
        p { margin: 0 0 6px; color:#4b5563; }
        .box { border:1px solid #e5e7eb; border-radius: 10px; padding:16px; margin-top:16px; }
        .row { display:flex; justify-content:space-between; margin: 8px 0; gap:12px; }
        .muted { color:#6b7280; }
        .grand { font-weight:700; color:#16a34a; font-size:1.1rem; border-top:1px solid #e5e7eb; padding-top:10px; margin-top:10px; }
    </style>
</head>
<body>
    <h1>Diet Watchers Invoice</h1>
    <p class="muted">Order: {{ $payment->order_number }}</p>
    <p class="muted">Date: {{ $payment->updated_at?->format('d/m/Y H:i') }}</p>

    <div class="box">
        <div class="row"><span>Customer</span><strong>{{ $payment->customer_name }}</strong></div>
        <div class="row"><span>Phone</span><strong>{{ $payment->customer_phone }}</strong></div>
        <div class="row"><span>Delivery</span><strong>{{ $payment->delivery_type === 'pickup' ? 'Pickup' : 'Home Delivery' }}</strong></div>
        @if($payment->street)
            <div class="row"><span>Address</span><strong>{{ $payment->city }} {{ $payment->street }} {{ $payment->building }}</strong></div>
        @endif
    </div>

    <div class="box">
        <div class="row"><span>Subtotal</span><strong>SAR {{ number_format($payment->subtotal / 100, 2) }}</strong></div>
        <div class="row"><span>Delivery fees</span><strong>SAR {{ number_format($payment->delivery_fee / 100, 2) }}</strong></div>
        <div class="row"><span>Discount</span><strong>- SAR {{ number_format($payment->discount_amount / 100, 2) }}</strong></div>
        <div class="row"><span>VAT</span><strong>SAR {{ number_format($payment->vat_amount / 100, 2) }}</strong></div>
        <div class="row grand"><span>Total Paid</span><span>SAR {{ number_format($payment->amount_in_sar, 2) }}</span></div>
    </div>
</body>
</html>
