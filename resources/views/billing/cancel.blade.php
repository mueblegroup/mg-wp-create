@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-yellow-100">
            <svg class="h-7 w-7 text-yellow-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M10.29 3.86l-7.5 13A1 1 0 003.65 18h16.7a1 1 0 00.86-1.5l-7.5-13a1 1 0 00-1.72 0z"/>
            </svg>
        </div>

        <h1 class="mt-5 text-2xl font-bold text-gray-900">Payment Cancelled</h1>
        <p class="mt-3 text-sm text-gray-600">
            The payment flow was cancelled before completion. No activation was made.
        </p>

        <div class="mt-8">
            <a href="{{ route('billing.index') }}" class="inline-flex rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-700">
                Back to Billing
            </a>
        </div>
    </div>
</div>
@endsection