@extends('layouts.app')

@section('title', 'Recovery Codes')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold text-center mb-6">Recovery Codes</h2>

            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            <p class="text-gray-600 mb-4">
                Save these recovery codes in a safe place. You can use them to access your account if you lose your phone.
            </p>

            <div class="bg-gray-100 p-4 rounded mb-4">
                <div class="grid grid-cols-2 gap-2 text-sm font-mono">
                    @foreach ($recoveryCodes as $code)
                        <div>{{ $code }}</div>
                    @endforeach
                </div>
            </div>

            <div class="flex space-x-4">
                <a href="{{ route('profile') }}"
                    class="flex-1 bg-gray-600 text-white py-2 px-4 rounded-md text-center hover:bg-gray-700">
                    Back to Profile
                </a>
                <form method="POST" action="{{ route('2fa.regenerate-recovery-codes') }}" class="flex-1">
                    @csrf
                    <button type="submit" class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700">
                        Regenerate Codes
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
