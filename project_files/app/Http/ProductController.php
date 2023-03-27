<?php

namespace App\Http\Controllers;

use App\Models\AutoMark;
use App\Models\AutoModel;
use App\Models\AutoModelModification;
use App\Models\Characteristic;
use App\Models\CustomerGroup;
use App\Models\OEM;
use App\Models\PageType;
use App\Models\Product;
use App\Models\ProductAttachedVariant;
use App\Models\ProductCharacteristic;
use App\Models\ProductType;
use App\Models\ProductVariant;
use App\Models\Storage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProductController extends Controller
{
    function index()
    {
        $breadcrumbs = [['url' => route('admin.product.index'), 'label' => 'Товары']];
        $products = Product::orderBy('name')->paginate(30)->withQueryString();
        return view('admin.models.product.index', get_defined_vars());
    }

    function edit(Product $product)
    {
        $model = $product;
        $breadcrumbs = [
            ['url' => route('admin.product.index'), 'label' => 'Товары'],
            ['url' => route('admin.product.edit', $product), 'label' => $product->name ?? 'новый товар']
        ];
        $availableCharacteristics = $product->availableCharacteristics;
        $productCharacteristics = $product->characteristics()->get();
        $existsProductVariants = $product->getAttachmentVariants();
        $availableProductVariants = $product->getAvailableProductVariants();
        $storages = Storage::orderBy('sort_order')->get();
        $productsOfStocks = $product->numbersOfStorages()->get();
        return view('admin.models.product.edit', get_defined_vars());
    }

    function update(Product $product)
    {
        $postData = \request()->toArray();
        $product->fill($postData);
        $product->is_available = (int)request()->has('is_available');
        $product->stock = (int)request()->has('stock');
        $product->slug = Str::slug(Str::lower(trim(request('slug') ?? request('name'))));
        if (!$product->exists) {
            $product->sort_order = Product::max('sort_order') + 1;
        }
        $numbersOfStorages = [];
        foreach ($postData['storage'] as $storage_id => $numbersOfStorage) {
            if ($numbersOfStorage > 0) {
                $numbersOfStorages[$storage_id] = ['number_of_goods' => $numbersOfStorage];
            }
        }
        $product->numbersOfStorages()->sync($numbersOfStorages);

        $deletingMedia = \request()->get('delete-media-image');
        foreach ($deletingMedia ?? [] as $deletingMediaId => $state) {
            $deletingMedia = Media::find($deletingMediaId);
            $deletingMedia->delete();
        }
        $uploadMedia = request()->file('upload_addition_images');
        foreach ($uploadMedia ?? [] as $image) {
            try {
                $product->addMedia($image)->toMediaCollection('addition_images');
            } catch (\Throwable $e) {
                dd($image);
            }
        }
        $uploadMainImage = request()->file('upload_main_image');
        if ($uploadMainImage) {
            $product->clearMediaCollection('main_image');
            try {
                $product->addMedia($uploadMainImage)->toMediaCollection('main_image');
            } catch (\Throwable $e) {
                dd($uploadMainImage);
            }
        }
        $product->save();
        $compatibleAutoModelModifications = AutoModelModification::find($postData['compatibleautomodelmodifications'] ?? []);
        $product->compatibleAutoModelModifications()->sync($compatibleAutoModelModifications);
        $additionalOEMs = OEM::find($postData['secondaryoems'] ?? []);
        $product->secondaryOEMs()->sync($additionalOEMs);
        ProductCharacteristic::where('product_id', $product->id)->delete();
        foreach (request()->available_characteristic ?? [] as $characteristic_id => $value) {
            $value = trim($value);
            $characteristic_model = Characteristic::find($characteristic_id);
            if ($characteristic_model->characteristic_type_id === 1) {
                $value = mb_strlen($value) > 0 ? (float)$value : null;
                $valueFieldName = 'numeric_value';
            } else {
                $valueFieldName = 'value';
                $value = mb_strtolower($value);
            }
            ProductCharacteristic::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'characteristic_id' => $characteristic_id
                ],
                [$valueFieldName => $value]
            );
        }
        $product->syncPrices(request()->{mb_strtolower(class_basename($product) . '_price_for_group')});
        return redirect()->route('admin.product.edit', $product);
    }

    function delete(Product $product)
    {
        $product->delete();
        return redirect()->route('admin.product.index');
    }

    function show(Product $product, $slug = null)
    {
        $model = $product;
        $pageType = 'product-page';
        $placeholders = Config::get('app.placeholders');
        $placeholders['${product_name}'] = $product->name;
        Config::set('app.placeholders', $placeholders);
        $pageTypeModel = PageType::find(20);
        $model->meta_title = $pageTypeModel->meta_title;
        $model->meta_description = $pageTypeModel->meta_description;
        $canonical = $product->getPage();
        $pagetitle = $model->name;
        $breadcrumbs = [
            ['label' => $model->type->name, 'url' => $model->type->page],
            ['label' => $model->name]
        ];
        $relatedProducts = $product->relatedProducts()->orderByDesc('general_amount')->get();
        $compatibleAutoModelModifications = $product->compatibleAutoModelModifications;
        $isShowCompatible = $compatibleAutoModelModifications->count() > 0;
        if ($isShowCompatible) {
            $firstCompatibleAutoModelModification = $compatibleAutoModelModifications->first();
        }
        $compatibleAutoModelNames = $compatibleAutoModelModifications->map(function ($modification) {
            return $modification->mark->name . ' ' . $modification->model->name;
        })->unique();
        $product->characteristics_collection = $product->characteristics()->with('characteristicPattern', 'characteristicPattern.type')->get();
        $attachmentVariants = $product->getAttachmentVariants();
        if ($attachmentVariants->count() > 0) {
            $firstVariant = $attachmentVariants->first();
        } else {
            $firstVariant = $product;
        }

        $allStorages = Storage::orderBy('name')->get();
        $saleableAmountOfStorage = $product->numbersOfStorages()->get();

        $showableTabs = new Collection();

        $showableTabs['reviews'] = ['field_name' => 'reviews', 'label' => 'Отзывы'];
        if (mb_strlen(trim($model->description)) > 0) {
            $showableTabs['description'] = (object)['field_name' => 'description', 'label' => 'Описание'];
        }
        if (mb_strlen(trim($model->video_url)) > 0) {
            $showableTabs['video'] = ['field_name' => 'video_url', 'label' => 'Видео'];
        }

        $showableTabs = $showableTabs->map(function ($unit) {
            $unit = (object)$unit;
            return $unit;
        });
        $userCustomerGroup = \Auth::user()?\Auth::user()->customerGroup:CustomerGroup::find(\config('shop.default_customer_group'));

        $pricesForGroups = $product->pricesForGroups;

        $firstVariantOrProductPrice = $pricesForGroups->firstWhere('customer_group_id', $userCustomerGroup->id)?->price;
        $defaultGroupPrice = $pricesForGroups->firstWhere('customer_group_id', app()->defaultCustomerGroup->id)?->price;

        $reviews = $product->reviews()->whereModerateStateId(1)->orderByDesc('updated_at')->get();
        $reviewAvgRating = $reviews->median('rating');

        $microdata = app()->microdata;
        $microdata['product'] = [];
        $productMicrodata = &$microdata['product'];
        $productMicrodata = [
            '@context' => 'https://schema.org/',
            '@type' => 'Product',
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'brand' => [
                '@type' => 'Brand',
                'name' => $product->getBrand()?->name
            ],
            'sku' => $product->shop_article,
            'mpn' => $product->getPrimaryOEM(),
            'offers' => [
                '@type' => 'Offer',
                'price' => $product->getPrice($userCustomerGroup),
                'priceCurrency' => "RUB",
                'priceValidUntil' => $product->updated_at->addWeek(4)->format(SQL_DATE),
                'availability' => 'https://schema.org/InStock',
                'itemCondition' => 'https://schema.org/NewCondition',
                'url' => $product->getPage()
            ],
            'review' => [
                '@type' => 'Review',
                'reviewRating' => [
                    '@type' => 'Rating',
                    'ratingValue' => '5',
                    'bestRating' => '5'
                ],
                'author' => app()->author_microdata_part
            ]
        ];

        if ($product->getDescription()) {
            $productMicrodata['description'] = $product->getDescription();
        }

        if ($reviews->count() > 0) {
            $productMicrodata['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $reviewAvgRating,
                'reviewCount' => $reviews->count()
            ];
        }

        app()->microdata = $microdata;
        return view('front.pages.product-page', get_defined_vars());
    }

    function changeOrder(Product $product, $direction)
    {
        $product->simpleChangeOrder($direction);
        $products = Product::orderBy('sort_order')->get();
        return view('admin.models.product.list', get_defined_vars());
    }

    function getTiles()
    {
        $filter = [
            ProductType::class => request('type_id')?request('type_id'):config('shop.default_product_type_id'),
            AutoMark::class => (request('mark_id') ?? config('app.default_filter_mark_id')),
            AutoModel::class => (request('model_id') ?? config('app.default_filter_model_id')),
            AutoModelModification::class => (request('model_modification_id') ?? config('app.default_filter_modification_id')),
        ];
        $headers = [];
        if(request('mark_id')){
            $newUrlPathParts = [];
            $productType = ProductType::find(request('type_id'));
            if($productType->compatibility_to_auto){
                $newUrlPathParts[] = $productType->slug;
                $autoMark = AutoMark::find(request('mark_id'));
                $newUrlPathParts[] = $autoMark->slug;
                if(request('model_id')){
                    $newUrlPathParts[] = AutoModel::find(request('model_id'))->slug;
                }
                $headers['new-url-path'] = '/catalog/'.implode('/', $newUrlPathParts);
            }
        }
        $products = Product::getProductsByFilter($filter);
        $products = $products->sortByDesc('general_amount');
        $products = $products->skip(0)->take(\config('shop.page_tiles_limit'));
        $showeable_tiles_number = request('showeable_tiles_number')??config('shop.max_products_on_category_page');
        $render = view('front.common.elements.product-thumbs-place', ['showeable_tiles_number' => $showeable_tiles_number, 'products' => $products])->render();

        if (request()->header('With-Characteristics-Filter')) {
            $productTypeCharacteristics = ProductType::find($filter[ProductType::class])->getCharacteristicsForFilter($products);
            $render .= view('front.common.chunks.characteristics-filter', compact('productTypeCharacteristics'))->render();
        }

        if (request()->header('With-Manufacturer-Filter')) {
            $developers = ProductType::find($filter[ProductType::class])->getManufacturersByFilter($products);
            $render .= view('front.common.chunks.manufacturers-filter', compact('developers'))->render();
        }

        return response($render)->withHeaders($headers);
    }

    public function addVariant(Product $product, ProductVariant $variant)
    {
        $render = '';
        try {
            $productAttachedVariant = new ProductAttachedVariant();
            $productAttachedVariant->product_id = $product->id;
            $productAttachedVariant->product_variant_id = $variant->id;
            $productAttachedVariant->save();
            $render .= view('admin.models.product.exists-product-variant', ['existsProductVariant' => $productAttachedVariant]);
        } catch (\Throwable $e) {
        }
        $existsProductVariants = $product->variants;
        $availableProductVariants = $product->getAvailableProductVariants();
        $render .= view('admin.models.product.addition-variant-tool', get_defined_vars());
        return '<div data-render>' . $render . '</div>';
    }

    public function deleteVariant(Product $product, ProductAttachedVariant $variant)
    {
        $render = '';
        try {
            $variant->delete();
        } catch (\Throwable $e) {
        }
        $existsProductVariants = $product->variants;
        $availableProductVariants = $product->getAvailableProductVariants();
        $render .= view('admin.models.product.addition-variant-tool', get_defined_vars());
        return '<div data-render>' . $render . '</div>';
    }

    public function updateVariant(ProductAttachedVariant $variant): string
    {
        $deletingMedia = \request()->get('delete-media-image');
        foreach ($deletingMedia ?? [] as $deletingMediaId => $state) {
            $deletingMedia = Media::find($deletingMediaId);
            $deletingMedia->delete();
        }
        $uploadMedia = request()->file('upload_addition_images');
        foreach ($uploadMedia ?? [] as $image) {
            $variant->addMedia($image)->toMediaCollection('addition_images');
        }
        $uploadMainImage = request()->file('upload_main_image');
        if ($uploadMainImage) {
            $variant->clearMediaCollection('main_image');
            $variant->addMedia($uploadMainImage)->toMediaCollection('main_image');
        }
        $requestPriceFields = request()->{mb_strtolower(class_basename($variant) . '_price_for_group')};
        $variant = $variant->syncPrices($requestPriceFields);
        $render = '';
        $variant->fill(\request()->all());
        $variant->saveImageFromRequest(1);
        $variant->save();

        $render .= view('admin.models.product.exists-product-variant', ['existsProductVariant' => $variant]);
        return '<div data-render>' . $render . '</div>';
    }

    public function showEmptyCharacteristicsProducts()
    {
        $products = ProductCharacteristic::getNullableCharacteristicProducts();
        return view('admin.other.empty-characteristics-products', get_defined_vars());
    }

    public function showFullFilledCharacteristicsProducts()
    {
        $products = ProductCharacteristic::getNullableCharacteristicProducts();
        return view('admin.other.full-filed-characteristics-products', get_defined_vars());
    }
}
