<section class="block-cta">
    <div class="block-cta-inner">
        @if(!empty($data['heading']))
            <h2>{{ $data['heading'] }}</h2>
        @endif
        @if(!empty($data['text']))
            <p>{{ $data['text'] }}</p>
        @endif
        @if(!empty($data['button_label']) && !empty($data['button_url']))
            <a href="{{ $data['button_url'] }}" class="block-btn">{{ $data['button_label'] }}</a>
        @endif
    </div>
</section>
