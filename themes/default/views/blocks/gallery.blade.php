@if(!empty($data['items']))
    <section class="gallery">
        @foreach($data['items'] as $item)
            @if(!empty($item['url']))
                <figure class="gallery-item">
                    <img src="{{ $item['url'] }}" alt="{{ $item['caption'] ?? '' }}">
                    @if(!empty($item['caption']))
                        <figcaption>{{ $item['caption'] }}</figcaption>
                    @endif
                </figure>
            @endif
        @endforeach
    </section>
@endif
