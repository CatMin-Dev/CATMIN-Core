<a href="{{ $item['url'] }}"
   class="catmin-nav-leaf {{ !empty($item['active']) ? 'is-active' : '' }}"
   @if(!empty($item['target'])) target="{{ $item['target'] }}" @endif>
    <i class="{{ $item['icon'] ?? 'bi bi-circle' }}"></i>
    <span>{{ $item['label'] }}</span>
    @if(!empty($item['badge']))
        <span class="badge text-bg-light ms-auto">{{ $item['badge'] }}</span>
    @endif
</a>
