<section class="feature-grid">
    @if(!empty($data['heading']))
        <h2 class="feature-grid-heading">{{ $data['heading'] }}</h2>
    @endif

    @if(!empty($data['items']))
        <div class="feature-grid-items">
            @foreach($data['items'] as $item)
                <div class="feature-card">
                    @if(!empty($item['title']))
                        <h3>{{ $item['title'] }}</h3>
                    @endif
                    @if(!empty($item['text']))
                        <p>{{ $item['text'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</section>
