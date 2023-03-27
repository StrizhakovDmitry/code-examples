<div class="product-thumb" data-product data-product-thumb data-id="{{ $product->id }}">
    <a class="product__picture" href="{{ $product->page }}">{!! $product->getFrontThumb() !!}</a>
    <a href="{{ $product->page }}" class="product__name">{{$product->name}}</a>
    @if($product->type->quick_buy)
        <a href="{{ $product->page }}" class="button type-1 product__to-page">Подробнее</a>
    @else
        <div class="product__price">
            <div class="price actual">
                <?php
                $price = $product->getPrice($customerGroup??app()->defaultCustomerGroup);
                ?>
                @if($price)
                    <div class="value">{{ $price }}</div>
                @else
                    <div class="note-button-label">По запросу</div>
                @endif
            </div>
        </div>
        <div class="product__buy-tool">
            @include('front.common.chunks.change-amount-tool')
            <button class="button type-1 buy-button" data-add-to-cart>купить</button>
        </div>
    @endif
    <div class="product__send-question"><a href="{{ route('personal_area.messages') }}">Задать вопрос специалисту</a></div>
    @include('front.common.chunks.stickers')
</div>
