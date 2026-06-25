<!DOCTYPE html>
<html lang="zh-CN" class="bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', '资产管理系统') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --glass-bg: rgba(255,255,255,0.72);
            --glass-border: rgba(255,255,255,0.3);
        }
        .card-hover {
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.08), 0 2px 8px rgba(0,0,0,0.04);
        }
        .glass {
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.4);
        }
        .sidebar-glass {
            background: rgba(15,23,42,0.92);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-in {
            animation: fadeInUp 0.4s ease-out;
        }
    </style>
    @stack('head')
</head>
<body class="font-sans antialiased text-gray-800" style="font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', 'Helvetica Neue', Figtree, sans-serif;">
    <div class="flex min-h-screen">
        @include('layouts.navigation')
        <div class="flex-1 lg:ml-64 sm:ml-16 transition-all duration-300 pb-16 sm:pb-0">
            @if (isset($header))
                <header class="glass sticky top-0 z-40 border-b border-gray-200/50">
                    <div class="px-6 py-4">
                        {{ $header }}
                    </div>
                </header>
            @endif
            <main class="p-6 animate-in">
                @if(session('success'))
                <div x-data="{show:true}" x-show="show" x-init="setTimeout(()=>show=false,4000)"
                     class="mb-4 px-4 py-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl text-sm flex items-center justify-between">
                    <span>{{ session('success') }}</span>
                    <button @click="show=false" class="text-emerald-500 hover:text-emerald-700">&times;</button>
                </div>
                @endif
                @if(session('error'))
                <div x-data="{show:true}" x-show="show" x-init="setTimeout(()=>show=false,5000)"
                     class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-800 rounded-2xl text-sm flex items-center justify-between">
                    <span>{{ session('error') }}</span>
                    <button @click="show=false" class="text-red-500 hover:text-red-700">&times;</button>
                </div>
                @endif
                {{ $slot }}
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
