@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-green-100">
            <svg class="h-7 w-7 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
        </div>

        <h1 class="mt-5 text-2xl font-bold text-gray-900">Payment Submitted</h1>
        <p class="mt-3 text-sm text-gray-600">
            Your payment has been submitted. We will activate your subscription after webhook confirmation from HitPay.
        </p>

        <div class="mt-8">
            <a href="{{ route('billing.index') }}" class="inline-flex rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-700">
                Return to Billing
            </a>
        </div>
    </div>
</div>
@endsection