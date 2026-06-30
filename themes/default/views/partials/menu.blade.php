@if($menu && $menu->rootItems->isNotEmpty())
<ul class="primary-nav" role="list">
    @foreach($menu->rootItems as $item)
        <li @class(['has-children' => $item->children->isNotEmpty()])>
            <a href="{{ $item->resolvedUrl() }}"
               @class(['active' => request()->url() === $item->resolvedUrl()])>
                {{ $item->label }}
            </a>
            @if($item->children->isNotEmpty())
                <ul>
                    @foreach($item->children as $child)
                        <li>
                            <a href="{{ $child->resolvedUrl() }}"
                               @class(['active' => request()->url() === $child->resolvedUrl()])>
                                {{ $child->label }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </li>
    @endforeach
</ul>
@endif
