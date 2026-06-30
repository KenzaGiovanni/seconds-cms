@php($hasImage = !empty($data['image']))
<section class="block-hero @if($hasImage) block-hero-image @endif"
         @if($hasImage) style="background-image: linear-gradient(rgba(16,20,19,0.55), rgba(16,20,19,0.55)), url('{{ $data['image'] }}');" @endif>
    <div class="block-hero-inner">
        @if(!empty($data['heading']))
            <h1>{{ $data['heading'] }}</h1>
        @endif
        @if(!empty($data['subheading']))
            <p>{{ $data['subheading'] }}</p>
        @endif
        @if(!empty($data['cta_label']) && !empty($data['cta_url']))
            <a href="{{ $data['cta_url'] }}" class="block-btn">{{ $data['cta_label'] }}</a>
        @endif
    </div>
</section>
