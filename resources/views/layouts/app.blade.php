<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'FeRa Clinic SMS') }}@hasSection('title') — @yield('title')@endif</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-50 text-slate-800">

<div class="flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <aside class="w-64 flex-shrink-0 bg-indigo-950 flex flex-col select-none">

        <!-- Brand -->
        <div class="px-5 py-5 border-b border-white/5">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-indigo-500/20 rounded-xl flex items-center justify-center flex-shrink-0 ring-1 ring-indigo-400/20">
                    <svg class="w-5 h-5 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-white font-bold text-sm leading-tight tracking-tight">FeRa Clinic</p>
                    <p class="text-indigo-400/70 text-xs font-medium">SMS Platform</p>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 px-3 py-5 space-y-0.5 overflow-y-auto">

            <p class="px-3 mb-2 text-[10px] font-semibold text-indigo-500/80 uppercase tracking-widest">Navigation</p>

            <!-- Dashboard -->
            <a href="{{ route('dashboard') }}"
               class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('dashboard') ? 'bg-indigo-600/40 text-white shadow-sm ring-1 ring-white/10' : 'text-indigo-300/80 hover:text-white hover:bg-white/5' }}">
                <svg class="w-[18px] h-[18px] flex-shrink-0 {{ request()->routeIs('dashboard') ? 'text-indigo-300' : 'text-indigo-400/60 group-hover:text-indigo-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span>Dashboard</span>
            </a>

            <!-- Campaigns -->
            <a href="{{ route('campaigns.index') }}"
               class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('campaigns.*') ? 'bg-indigo-600/40 text-white shadow-sm ring-1 ring-white/10' : 'text-indigo-300/80 hover:text-white hover:bg-white/5' }}">
                <svg class="w-[18px] h-[18px] flex-shrink-0 {{ request()->routeIs('campaigns.*') ? 'text-indigo-300' : 'text-indigo-400/60 group-hover:text-indigo-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                </svg>
                <span>Campaigns</span>
            </a>

            <!-- Contacts -->
            <a href="{{ route('contacts.index') }}"
               class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('contacts.index') ? 'bg-indigo-600/40 text-white shadow-sm ring-1 ring-white/10' : 'text-indigo-300/80 hover:text-white hover:bg-white/5' }}">
                <svg class="w-[18px] h-[18px] flex-shrink-0 {{ request()->routeIs('contacts.index') ? 'text-indigo-300' : 'text-indigo-400/60 group-hover:text-indigo-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span>Contacts</span>
            </a>

            <!-- Import Contacts -->
            <a href="{{ route('contacts.import') }}"
               class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('contacts.import') ? 'bg-indigo-600/40 text-white shadow-sm ring-1 ring-white/10' : 'text-indigo-300/80 hover:text-white hover:bg-white/5' }}">
                <svg class="w-[18px] h-[18px] flex-shrink-0 {{ request()->routeIs('contacts.import') ? 'text-indigo-300' : 'text-indigo-400/60 group-hover:text-indigo-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                <span>Import Contacts</span>
            </a>

        </nav>

        <!-- User / Logout -->
        <div class="px-3 py-4 border-t border-white/5">
            <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-white/5 mb-1">
                <div class="w-8 h-8 bg-indigo-500 rounded-full flex items-center justify-center flex-shrink-0 ring-2 ring-indigo-400/30">
                    <span class="text-white text-xs font-bold">{{ strtoupper(substr(Auth::user()->name, 0, 2)) }}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-white text-xs font-semibold truncate leading-tight">{{ Auth::user()->name }}</p>
                    <p class="text-indigo-400/60 text-xs truncate">Administrator</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs text-indigo-400/70 hover:text-white hover:bg-white/5 w-full text-left transition-all duration-150 mt-0.5">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <span>Sign Out</span>
                </button>
            </form>
        </div>

    </aside>

    <!-- Main area -->
    <div class="flex-1 flex flex-col overflow-hidden">

        <!-- Top bar -->
        <header class="flex-shrink-0 bg-white border-b border-slate-200/80 px-8 py-4 flex items-center justify-between shadow-sm">
            <div>
                @hasSection('page-title')
                    <h1 class="text-base font-semibold text-slate-900 leading-tight">@yield('page-title')</h1>
                @else
                    <h1 class="text-base font-semibold text-slate-900 leading-tight">{{ config('app.name', 'FeRa Clinic SMS') }}</h1>
                @endif
                @hasSection('page-subtitle')
                    <p class="text-xs text-slate-400 mt-0.5">@yield('page-subtitle')</p>
                @endif
            </div>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2.5 bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5">
                    <div class="w-7 h-7 bg-indigo-100 rounded-full flex items-center justify-center">
                        <span class="text-indigo-700 text-xs font-bold">{{ strtoupper(substr(Auth::user()->name, 0, 2)) }}</span>
                    </div>
                    <span class="text-sm text-slate-700 font-medium hidden sm:block">{{ Auth::user()->name }}</span>
                </div>
            </div>
        </header>

        <!-- Page content -->
        <main class="flex-1 overflow-y-auto px-8 py-7">
            {{ $slot }}
        </main>

    </div>

</div>

</body>
</html>
