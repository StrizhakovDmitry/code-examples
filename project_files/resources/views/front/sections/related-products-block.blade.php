<div class="section similar-products-section">
    <div class="content">
        <div class="title">Вместе с этим товаром покупают</div>
        <div class="product-thumbs">
            @foreach($relatedProducts as $product)
                @include('front.common.elements.product-thumb')
            @endforeach
        </div>
    </div>
</div>
