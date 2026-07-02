<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'CDG — Wrap Quote' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-100 text-slate-900 antialiased">
    <div class="min-h-full">
        @auth
            <header class="border-b border-slate-200 bg-white">
                <div class="relative mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
                    <a href="{{ route('front-desk') }}" class="flex items-center gap-2 font-semibold">
                        <span class="rounded bg-slate-900 px-2 py-1 text-sm text-white">CDG</span>
                        <span>Front Desk — Wrap Quote</span>
                    </a>
                    @can('admin')
                        <nav class="absolute left-1/2 flex -translate-x-1/2 items-center gap-6 text-sm">
                            <a href="{{ route('front-desk') }}" class="text-slate-600 hover:text-slate-900">Front Desk</a>
                            <a href="{{ route('admin.pricing') }}" class="text-slate-600 hover:text-slate-900">Pricing</a>
                            <a href="{{ route('admin.vehicles') }}" class="text-slate-600 hover:text-slate-900">Vehicles</a>
                        </nav>
                    @endcan
                    <div class="flex items-center gap-4 text-sm">
                        <span class="text-slate-500">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="rounded border border-slate-300 px-3 py-1 hover:bg-slate-50">
                                Log out
                            </button>
                        </form>
                    </div>
                </div>
            </header>
        @endauth

        <main class="mx-auto max-w-6xl px-4 py-6">
            {{ $slot }}
        </main>
    </div>
</body>
</html>
