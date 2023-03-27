@if($product->is_available)
    <div class="left-stickers">
        <div class="sticker is_available">в наличии</div>
    </div>
@endif
@if($product->stock)
    <div class="right-sticker">
        <div class="sticker stock">акция</div>
    </div>
@endif
