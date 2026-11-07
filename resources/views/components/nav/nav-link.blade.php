@props(['href', 'icon', 'content', 'active' => false])

<a {{ $attributes->class(['nav-link', 'active' => $active])->merge(['href' => $href, 'aria-current' => $active ? 'page' : null]) }}>
    <div class="sb-nav-link-icon"><i class="{{ $icon }}"></i></div>
    {{ $content }}
</a>
