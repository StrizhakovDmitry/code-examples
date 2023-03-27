<div data-product-variants-tool>
    <div class="uk-padding-small uk-form-horizontal">
        <div class="uk-margin">
            <label class="uk-form-label"><b>Добавить варианты</b></label>
            <div class="uk-form-controls">@include('admin.models.product.addition-variant-tool')</div>
        </div>
    </div>
    <div class="product-variants uk-padding-small uk-form-horizontal">
        <div class="uk-margin">
            <label class="uk-form-label"><b>Варинты</b></label>
            <div class="uk-form-controls">
                @include('admin.models.product.exists-product-variants')
            </div>
        </div>
    </div>
    @push('scripts')
        const dataProductVariantsToolElement = document.querySelector('[data-product-variants-tool]');
        new additionalVariantHandler(dataProductVariantsToolElement);
    @endpush
</div>
