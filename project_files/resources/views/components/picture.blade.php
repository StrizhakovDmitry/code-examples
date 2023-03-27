<picture @if($class) class='{{ $class }}' @endif><source srcset="{{ $srcset }}" crossorigin="anonymous" type="image/webp"><img {!! $attributeX !!} {!! $attributeY !!} class="resized-picture" src="{{ $src }}" crossorigin="anonymous" alt="{{ $alt??'image' }}"></picture>

