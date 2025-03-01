@push('head')
    <meta name="robots" content="noindex"/>
    <meta name="google" content="notranslate">
    <link
          href="{{ asset('/vendor/orchid/favicon.svg') }}"
          sizes="any"
          type="image/svg+xml"
          id="favicon"
          rel="icon"
    >

    <!-- For Safari on iOS -->
    <meta name="theme-color" content="#21252a">
@endpush

<div class="h2 d-flex align-items-center">
    @auth
        <x-orchid-icon path="bs.house" class="d-inline d-sm-none"/>
    @endauth

    <p class="my-0 {{ auth()->check() ? 'd-none d-sm-block' : '' }}">
        {{ config('app.name') }}
        <small class="align-top opacity">Orchid</small>
    </p>
</div>
