<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Laborobo') }}</title>
    <meta name="description" content="{{ $description ?? 'AI-powered work management for small teams. Turn chaos into structured, profitable operations.' }}">

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|ibm-plex-mono:400,500,600" rel="stylesheet" />

    @vite(['resources/css/app.css'])
</head>
<body class="bg-background text-foreground font-sans antialiased">
    {{-- Navigation --}}
    <header class="sticky top-0 z-50 border-b border-border bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
        <nav class="mx-auto flex h-16 max-w-6xl items-center justify-between px-6">
            <a href="/" class="flex items-center gap-2">
                <img src="/logo.svg" alt="{{ config('app.name') }}" class="h-8 w-auto">
                <span class="text-lg tracking-tight">Your work OS</span>
            </a>

            <div class="hidden items-center gap-8 md:flex">
                <a href="/use-cases/agencies" class="text-sm text-muted-foreground transition hover:text-foreground">Agencies</a>
                <a href="/use-cases/consultancies" class="text-sm text-muted-foreground transition hover:text-foreground">Consultancies</a>
                <a href="/use-cases/operations" class="text-sm text-muted-foreground transition hover:text-foreground">Operations</a>
            </div>

            <div class="flex items-center gap-3">
                @auth
                    <span class="text-sm text-muted-foreground">{{ Auth::user()->name }}</span>
                    <a href="/today" class="inline-flex h-9 items-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition hover:bg-primary/90">
                        Go to App
                    </a>
                @else
                    <a href="/login" class="text-sm font-medium text-muted-foreground transition hover:text-foreground">Log in</a>
                    <a href="/register" class="inline-flex h-9 items-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition hover:bg-primary/90">
                        Get Started
                    </a>
                @endauth
            </div>
        </nav>
    </header>

    {{-- Page Content --}}
    <main>
        {{ $slot }}
    </main>

    {{-- Footer --}}
    <footer class="border-t border-border bg-secondary/50">
        <div class="mx-auto max-w-6xl px-6 py-12">
            <div class="flex flex-col items-center justify-between gap-6 md:flex-row">
                <div class="flex items-center gap-2">
                    <img src="/logo.svg" alt="{{ config('app.name') }}" class="h-6 w-auto opacity-60">
                    <span class="text-sm text-muted-foreground">&copy; {{ date('Y') }} {{ config('app.name', 'Laborobo') }}. All rights reserved.</span>
                </div>
                <div class="flex gap-6">
                    <a href="/use-cases/agencies" class="text-sm text-muted-foreground transition hover:text-foreground">Agencies</a>
                    <a href="/use-cases/consultancies" class="text-sm text-muted-foreground transition hover:text-foreground">Consultancies</a>
                    <a href="/use-cases/operations" class="text-sm text-muted-foreground transition hover:text-foreground">Operations</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
