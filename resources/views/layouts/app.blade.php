<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-url" content="{{ url('/') }}">

    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="icon" type="image/png" href="{{asset('images/logo-2.png')}}">
    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body>
    <div id="app">
        <nav class="flex justify-between bg-gray-200 shadow-md w-full h-16 pb-2">
            <div class="flex">
                <img src="{{asset('images/logo-2.png')}}" class="w-12 mt-2 ml-5">
                <router-link to="/" class="text-gray-700 text-sm uppercase mt-6 ml-5">Trade</router-link>
                <router-link to="/settlement" class="text-gray-700 text-sm uppercase mt-6 ml-5">Settlement</router-link>
                <router-link to="/open-orders" class="text-gray-700 text-sm uppercase mt-6 ml-5">Open Orders</router-link>
                <router-link to="/settings" class="text-gray-700 text-sm uppercase mt-6 ml-5">Settings</router-link>
            </div>

            <div class="flex">
                <a class="text-gray-700 text-sm uppercase mt-6 ml-5" href="#" role="button">
                    {{ Auth::user()->name }} <span class="caret"></span>
                </a>
                <a class="text-gray-700 text-sm uppercase mt-6 ml-5 mr-5" href="{{ route('logout') }}"
                    onclick="event.preventDefault();
                        document.getElementById('logout-form').submit();">
                    Logout
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
            </div>
        </nav>

        <main class="py-4">
            @yield('content')
        </main>
    </div>
</body>
</html>
