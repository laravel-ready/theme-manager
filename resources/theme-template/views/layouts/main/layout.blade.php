<!DOCTYPE html>
<html lang="en">

<head>
    {{-- Main layout header --}}
    @include('theme::layouts.main.header')

    {{-- #1 Head Styles --}}
    @stack('head-styles')

    {{-- #2 Head Scripts --}}
    @stack('head-scripts')

    {{-- #3 Head Stack --}}
    @stack('head-stack')
</head>

<body>
    {{-- Header Nav --}}
    @component('theme::layouts.base.header-nav')

    <main>
        {{-- Main Content --}}
        @yield('content')
    </main>

    {{-- Main layout footer --}}
    @include('theme::layouts.base.footer')

    {{-- #1 Footer Scripts --}}
    @stack('footer-scripts')

    {{-- #2 Footer Stack --}}
    @stack('footer-stack')
</body>

</html>
