<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">

    <style type="text/css">
        @php
            $sizes = ['xs', 'sm', 'md', 'lg', 'xl', '2xl', '3xl', '4xl'];
        @endphp

        @import url('https://fonts.googleapis.com/css?family=Assistant:100,200,300,400,600,700');

        html, body, div, table { font-family: "Assistant", sans-serif !important; line-height: 1.25rem; } .absolute { position: absolute; } .bg-orange { background-color: #ED8936; } .bg-white { background-color: #FFFFFF; } .btn { display: inline-block; padding: 4px 8px; border-radius: 4px; text-decoration: none; } .btn-lg { padding: 15px 25px !important; } .btn-primary { background-color: #ED8936; color: #FFFFFF; }
        table { padding: 10px 20px; background: rgb(255, 255, 255); border-radius: 5px; box-shadow: 0px 5px 5px -2px rgba(0, 0, 0, 0.2); font-size: 90%; }
        table tr td { padding: 5px 10px !important; } table tr td:first-child { text-align: right; } table tr td:first-child::after { content: ":"; margin-left: 10px; } table tr td:last-child { font-family: monospace !important; }
        .container { width: 1024px; } .flex { display: flex; } .font-bold { font-weight: bold; } .inset-auto { top: auto; right: auto; bottom: auto; left: auto; } .items-center { align-items: center; } .justify-center { justify-content: center; } .m-auto { margin: auto; } .mx-auto { margin-left: auto; margin-right: auto; } .my-auto { margin-top: auto; margin-bottom: auto; } @for ($i = 0; $i < count($sizes); $i++) .rounded-{{ $sizes[$i] }} { border-radius: {{ $i * 0.25 }}rem; } .text-{{ $sizes[$i] }} { font-size: {{ 0.75 + (0.125 * $i) }}rem; } @endfor .overflow-hidden { overflow: hidden; } .shadow-xs {  box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.05); } .shadow-sm {  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); } .shadow { box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06); } .shadow-md {  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); } .shadow-lg {  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); } .shadow-xl {  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); } .shadow-2xl { box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); } .text-center { text-align: center; } .text-gray { color: #888888; } .text-orange { color: #ED8936; } @for ($i = 1; $i <= 10; $i++) .m-{{ $i }} { margin: {{ $i * 0.25 }}rem; } .mx-{{ $i }} { margin-left: {{ $i * 0.25 }}rem; margin-right: {{ $i * 0.25 }}rem; } .my-{{ $i }} { margin-top: {{ $i * 0.25 }}rem; margin-bottom: {{ $i * 0.25 }}rem; } .mt-{{ $i }} { margin-top: {{ $i * 0.25 }}rem; } .mr-{{ $i }} { margin-right: {{ $i * 0.25 }}rem; } .mb-{{ $i }} { margin-bottom: {{ $i * 0.25 }}rem; } .ml-{{ $i }} { margin-left: {{ $i * 0.25 }}rem; } .p-{{ $i }} { padding: {{ $i * 0.25 }}rem; } .px-{{ $i }} { padding-left: {{ $i * 0.25 }}rem; padding-right: {{ $i * 0.25 }}rem; } .py-{{ $i }} { padding-top: {{ $i * 0.25 }}rem; padding-bottom: {{ $i * 0.25 }}rem; } .pt-{{ $i }} { padding-top: {{ $i * 0.25 }}rem; } .pr-{{ $i }} { padding-right: {{ $i * 0.25 }}rem; } .pb-{{ $i }} { padding-bottom: {{ $i * 0.25 }}rem; } .pl-{{ $i }} { padding-left: {{ $i * 0.25 }}rem; } .w-1\/{{ $i }} {  width: {{ 100 / $i }}% !important; } @endfor .w-full { width: 100% !important; } </style>
</head>
<body>
    <main class="p-4 container mx-auto">
        @yield('content')
    </main>
</body>
</html>