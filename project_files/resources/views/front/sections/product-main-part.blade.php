@push('scripts')
    <script>window.productName = '{{ $product->name }}';</script>
@endpush
<div class="section product-main-part" data-product data-id="{{ $product->id }}">
    <div class="content">
        @include('front.pages.product.pictures-part')
        <div class="buy-tools-part">
            @include('front.pages.product.price', ['defaultGroupPrice' => $defaultGroupPrice,'price' => $firstVariantOrProductPrice, 'saleable' => $product])
            @if($attachmentVariants->count() > 1)
                <div class="variants">
                    <div class="title">Доступные варианты покупки</div>
                    <div class="change-tool variant" data-change-variant-tool>
                        @foreach($attachmentVariants as $variant)
                            <label class="change-tool_row" data-variant data-manufacturer="{{ $variant->manufacturer->name }}" data-trademark="{{ $variant->trademark->name }}" data-article="{{ $variant->shop_article }}">
                                <input data-variant-input name="variant" value="{{ $variant->id }}" type="radio" {{ $loop->first?'checked':'' }}>
                                <span class="pseudo-radio"></span>
                                <span data-variant-name>{{ $variant->getConstructedName() }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                @push('scripts')
                    <script>
                        new changeVariantTool(document.querySelector('[data-change-variant-tool]'))
                    </script>
                @endpush
            @endif
            <div class="options">
                <div class="change-tool">
                    <label class="change-tool_row"><input name="need_installation" type="checkbox"><span>Требуется установка</span></label>
                </div>
            </div>
            <div class="buttons">
                <button class="button type-1" data-add-to-cart>добавить в корзину</button>
                <button data-show-form="buy_by_one_click_product_page" class="button type-2">купить в один клик</button>
            </div>
            @if(Auth::user() && Auth::user()->isEmployee())
                @include('front.pages.product.admin-prices')
            @endif
        </div>
        @include('front.common.sections.storages')
        <div class="characteristics">
            <div class="title">Характеристики</div>
            <div class="characteristics-list">
                <span class="art"><span class="key">Артикул PST-Center</span><span data-product-common-article class="value">{{ $product->shop_article }}</span></span>
                <span class="manufacturer"><span class="key">Производитель</span><span class="value" data-product-common-manufacturer>{{ $product->manufacturer?->name??'noname' }}</span></span>
                <span class=""><span class="key">Торговая марка</span><span class="value" data-product-common-trademark>{{ $product->trademark?->name??'noname' }}</span></span>
                @if(trim($product->analog_articles))
                    <span class=""><span class="key">Аналоги</span><span class="value">{{ $product->analog_articles }}</span></span>
                @endif
                @if(trim($product->characteristics_raw))
                    <span class=""><span class="key">Характеристики</span><span class="value">{{ $product->characteristics_raw }}</span></span>
                @endif
                @if($product->main_oem)
                <span class="oem"><span class="key">Основной ОЕМ номер</span>
                    <span class="value">{{ $product->main_oem }}</span>
                </span>
                @endif
                <?php $additionalOEMs = $product->getAdditionalOEMs();?>
                @if(count($additionalOEMs) > 0)
                    <span class="additional-OEMs" data-show-more-container><span
                            class="key">Дополнительные ОЕМ номера</span><span
                            class="value">
                        <span class="OEMs-list">
                            @foreach($additionalOEMs as $additionalOEM)
                                <span class="oem">{{ $additionalOEM }}<span class="delimiter">{{!$loop->last?',':''}}</span></span>
                            @endforeach
                        </span>
                        @if(count($additionalOEMs) > 3)
                            <span class="show-more" data-show-more-tool>показать все</span>
                        @endif
                        </span>
                </span>
                @endif

                @if(trim($product->compatibility_raw))
                    <span class=""><span class="key">Совместимость</span><span
                            class="value">{{ $product->compatibility_raw }}</span></span>
                @endif
                @foreach($product->characteristics_collection as $characteristics)
                    @if($characteristics->characteristicPattern->type->slug === 'enum')
                        <span class="characteristic"><span class="key">{{ $characteristics->name }}</span><span
                                class="value">{{ $characteristics->value }}</span> <span
                                class="measure">{{ $characteristics->characteristicPattern->measure }}</span></span>
                    @else
                        @if($characteristics->numeric_value > 0)
                        <span class="characteristic"><span class="key">{{ $characteristics->name }}</span><span
                                class="value">{{ $characteristics->numeric_value }}</span> <span
                                class="measure">{{ $characteristics->characteristicPattern->measure }}</span></span>
                        @endif
                    @endif
                @endforeach
            </div>
        </div>
        @if($showableTabs->count() > 0)
            <div class="tabs-block">
                <div data-tabs-block>
                    <div data-panes>
                        @foreach($showableTabs as $tab)
                            <div data-pane class="{{ $loop->first?'active':'' }}">{{ $tab->label }}</div>
                        @endforeach
                    </div>
                    <div data-containers>
                        @foreach($showableTabs as $tab)
                            <div data-container class="{{ $loop->first?'active':'' }}">
                                @include('front.pages.product.tabs-content.'.$tab->field_name)
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
