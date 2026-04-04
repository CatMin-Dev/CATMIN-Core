<nav class="catmin-breadcrumbs" aria-label="Fil d'ariane">
    <ol>
        @foreach($breadcrumbs as $crumb)
            <li>
                @if(!empty($crumb['url']))
                    <a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a>
                @else
                    <span>{{ $crumb['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
