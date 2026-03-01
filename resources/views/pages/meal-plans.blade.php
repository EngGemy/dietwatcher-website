@extends('layouts.app')

@section('title', __('Meal Plans') . ' - ' . config('app.name'))

@section('content')
<section class="bg-gray-200 pt-20 pb-28">
    <div class="container">
        <livewire:meal-plans.filter-plans />
    </div>
</section>
@endsection
