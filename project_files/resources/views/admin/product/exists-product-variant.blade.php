<form class="exists-product-variant" data-exists-products-variant data-id="{{$existsProductVariant->id}}">
    <div class="variant-name">{{ $existsProductVariant->type->name }}</div>
    <div class="field-holder">
        <label class="label">Своё название</label>
        <input type="text" name="custom_name" value="{{ $existsProductVariant->custom_name }}" class="price uk-input uk-form-width-large">
    </div>
    @include('admin.models.product.prices-for-customer-groups',['model' => $existsProductVariant])
    <div class="field-holder">
        <label class="label">Сортировка (меньше - выше)</label>
        <input type="number" name="custom_sort_order" placeholder="{{ $existsProductVariant->type->sort_order }}" value="{{ $existsProductVariant->custom_sort_order }}" class="price uk-input uk-form-width-small">
    </div>
    <div class="field-holder">
        <label class="label">Главное изображение</label>
    @include('admin.common.fields.main-image',['model' => $existsProductVariant])
    </div>
    <div class="field-holder">
        <label class="label">Дополнительные изображения</label>
    @include('admin.common.fields.addition-images',['model' => $existsProductVariant])
    </div>
    <div class="edit-tools">
        <button type="submit" data-update-variant-button class="tool save" title="сохранить"></button>
        <button type="submit" data-remove-variant-button class="tool delete" title="удалить"></button>
    </div>
</form>
