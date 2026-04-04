<footer class="cf-footer" role="contentinfo">
    <div class="container">
        <span>&copy; {{ date('Y') }} {{ $siteName ?? config('app.name', 'CATMIN') }}</span>
        @if(config('catmin_frontend.contact_enabled'))
            &nbsp;&middot;&nbsp;
            <a href="{{ route('frontend.contact') }}" class="text-muted text-decoration-none">Contact</a>
        @endif
    </div>
</footer>
