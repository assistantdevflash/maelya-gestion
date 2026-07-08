<img 
    src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 {{ $width ?? 400 }} {{ $height ?? 300 }}'%3E%3C/svg%3E" 
    data-src="{{ $src }}" 
    alt="{{ $alt }}"
    {{ $attributes->merge(['class' => $class]) }}
    @if($width) width="{{ $width }}" @endif
    @if($height) height="{{ $height }}" @endif
    loading="lazy"
    decoding="async"
>
<script>
if ('loading' in HTMLImageElement.prototype) {
    document.querySelectorAll('img[data-src]').forEach(img => {
        img.src = img.dataset.src;
    });
} else {
    // Fallback pour anciens navigateurs
    var script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/lazysizes@5/lazysizes.min.js';
    document.body.appendChild(script);
}
</script>
