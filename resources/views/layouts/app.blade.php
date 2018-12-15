<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, height=device-height">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Laravel</title>

        <link href="{{ asset('css/semantic.min.css') }}" rel="stylesheet" type="text/css">
        <link href="{{ asset('css/app.css') }}" rel="stylesheet" type="text/css">
    </head>
    <body>
        <div id="app">

            <sui-menu fixed inverted>
                <router-link header :to="{ name: 'dashboard' }" is="sui-menu-item">
                    <sui-icon name="server" size="big"></sui-icon> Servidor
                </router-link>
            </sui-menu>

            <sui-container class="main">
                <stats-bar hostname="{{ $stats->hostname }}"
                           distro="{{ $stats->os->distro }}"
                           version="{{ $stats->os->version }}">
                </stats-bar>

                <main-menu></main-menu>

                @yield('content')
            </sui-container>

        </div>

        <script src="{{ asset('js/app.js') }}"></script>
    </body>
</html>
