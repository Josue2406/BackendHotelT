<!-- @props(['url'])

@php
    $logo = config('app.mail_logo_url');   // lo definiremos en .env
    $app  = config('app.name');
@endphp

<tr>
<td class="header">
    <a href="{{ $url ?? config('app.url') }}" style="display:inline-block;">
        @if ($logo)
            <img src="{{ $logo }}" alt="{{ $app }}" class="logo" style="height:40px; max-height:40px;">
        @else
            {{ $slot }} {{-- fallback: muestra el texto (APP_NAME) si no hay logo --}}
        @endif
    </a>
</td>
</tr> -->
<!-- @props(['url'])

@php
    $app  = config('app.name');
    // Si tienes MAIL_LOGO_URL lo usa; si no, toma el de /build
    $logo = config('app.mail_logo_url') ?: asset('build/assets/Lanaku.jpeg');
@endphp

<tr>
<td class="header">
    <a href="{{ $url ?? config('app.url') }}" style="display:inline-block;">
        <img src="{{ $logo }}" alt="{{ $app }}" class="logo" style="height:40px; max-height:40px;">
    </a>
</td>
</tr> -->
@props(['url'])

@php
  $app = config('app.name');
  $logo = rtrim(config('app.url'), '/').'/assets/Lanaku.jpeg';
@endphp

<tr>
<td class="header">
  <a href="{{ $url ?? config('app.url') }}" style="display:inline-block;">
    <img src="{{ $logo }}" alt="{{ $app }}" class="logo" style="height:200px; max-height:300px;">
  </a>
</td>
</tr>
