@extends('layouts.app')

@section('title', 'Setup Two-Factor Authentication')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold text-center mb-6">Setup Two-Factor Authentication</h2>

            <p class="text-gray-600 mb-4">
                Scan the QR code below with your phone's camera or QR scanner app to get the verification code.
            </p>

            <div class="text-center mb-6">
                <img src="data:image/svg+xml;base64,{{ base64_encode($qrCode) }}" alt="QR Code"
                    class="mx-auto border border-gray-300 rounded">
            </div>

            <form method="POST" action="{{ route('2fa.setup') }}">
                @csrf

                <div class="mb-4">
                    <label for="code" class="block text-sm font-medium text-gray-700">Verification Code</label>
                    <input type="text" name="code" id="code"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                        required maxlength="6" pattern="[0-9]{6}">
                    @error('code')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                    class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Enable 2FA
                </button>
            </form>
        </div>
    </div>
@endsection
