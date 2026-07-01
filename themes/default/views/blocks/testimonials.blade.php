<section class="testimonials-block">
    @if(!empty($data['heading']))
        <h2 class="testimonials-heading">{{ $data['heading'] }}</h2>
    @endif

    @if(!empty($data['items']))
        <div class="testimonials-grid">
            @foreach($data['items'] as $item)
                <blockquote class="testimonial-card">
                    @if(!empty($item['quote']))
                        <p class="testimonial-quote">&ldquo;{{ $item['quote'] }}&rdquo;</p>
                    @endif
                    <footer class="testimonial-footer">
                        @if(!empty($item['name']))
                            <strong class="testimonial-name">{{ $item['name'] }}</strong>
                        @endif
                        @if(!empty($item['role']))
                            <span class="testimonial-role">{{ $item['role'] }}</span>
                        @endif
                    </footer>
                </blockquote>
            @endforeach
        </div>
    @endif
</section>
