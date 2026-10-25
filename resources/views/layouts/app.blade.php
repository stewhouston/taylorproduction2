<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @head

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link rel="icon" type="image/x-icon" href="/favicon.ico">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @section('scripts')
        <script type="text/javascript" src="/js/jQuery-2.2.0.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/lodash.js/4.16.5/lodash.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/numeral.js/1.4.5/numeral.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.15.2/moment.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.43/js/bootstrap-datetimepicker.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/featherlight/1.5.0/featherlight.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-timeago/1.5.3/jquery.timeago.min.js"></script>
        @show

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tooltipster/3.3.0/css/tooltipster.min.css" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tooltipster/3.3.0/css/themes/tooltipster-noir.min.css" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.43/css/bootstrap-datetimepicker.min.css" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/featherlight/1.6.1/featherlight.min.css" />
        <link rel="stylesheet" href="/css/datepicker3.css">

        <!-- Styles -->
        @livewireStyles
    </head>
    <body class="font-sans antialiased">
        <x-banner />

        <div class="min-h-screen bg-gray-100">
            @livewire('navigation-menu')

            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        @stack('modals')

        @livewireScripts
    </body>
</html>
