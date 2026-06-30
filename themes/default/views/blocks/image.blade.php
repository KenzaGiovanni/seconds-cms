@if(!empty($data['url']))
    <figure>
        <img src="{{ $data['url'] }}" alt="{{ $data['alt'] ?? '' }}"
             @if(!empty($data['width'])) width="{{ $data['width'] }}" @endif
             @if(!empty($data['height'])) height="{{ $data['height'] }}" @endif>
        @if(!empty($data['caption']))
            <figcaption>{{ $data['caption'] }}</figcaption>
        @endif
    </figure>
@endif
