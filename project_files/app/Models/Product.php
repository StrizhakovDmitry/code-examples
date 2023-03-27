<?php

namespace App\Models;

use App\Interfaces\Sale;
use App\Interfaces\Searchable;
use App\Listeners\ProductCharacteristicsListener;
use App\Traits\SingleImage;
use App\Traits\SortOrderChangeable;
use App\Traits\Updatable1c;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements Sale, Searchable, HasMedia
{
    use HasFactory, SoftDeletes, SortOrderChangeable, SingleImage, InteractsWithMedia, \App\Traits\Sale, Updatable1c;

    const exchange_1c_url_path = 'nomeklatura';
    const exchange_1c_file = 'app/1c_products.json';
    const related_products_1c_url_path = 'nomenkl_svyazannaya';
    const related_products_1c_file = 'app/1c_related_products.json';
    const related_products_table_name = 'related_products';

    public function getKeyType()
    {
        return 'string';
    }

    protected $guarded = ['id'];

    protected static function boot()
    {
        parent::boot();
    }

    protected $dispatchesEvents = [
        'saved' => ProductCharacteristicsListener::class,
    ];

    private $mediaImageSizes = [
        'thumb-main-product-page' => 640,
        'thumb-product-admin' => 150,
        'thumb-admin' => 150,
        'thumb-product-front' => 156,
        'thumb-product-tile-front' => 290,
    ];



    public function registerMediaConversions(Media $media = null): void
    {
        //php artisan media-library:regenerate
        foreach ($this->mediaImageSizes as $sizeName => $sizeValue) {
            $this->addMediaConversion($sizeName)->width($sizeValue)->queued();
        }
    }

    public static function getGlobalPriceRange(): object
    {
        $minProductPrice = Cache::remember('min_product_price', 600, function () {
            return PriceOfCustomerGroup::min('price');
        });
        $maxProductPrice = Cache::remember('max_product_price', 600, function () {
            return PriceOfCustomerGroup::max('price');
        });
        return (object)['min' => $minProductPrice, 'max' => $maxProductPrice];
    }

    public function getVideoUrl()
    {
        return trim($this->video_url);
    }

    public function getFrontPrefetchImageLinks()
    {
        $links = [];
        $models = [];

        if ($this->variants->count() > 0) {
            foreach ($this->variants as $variant) {
                $models[] = $variant;
            }
        } else {
            $models[] = $this;
        }
        foreach ($models as $model) {
            $mainImage = $model->getFirstMedia('main_image');
            if ($mainImage) {
                $links[] = (object)['href' => $mainImage->getUrl(), 'type' => $mainImage->mime_type];
                $links[] = (object)['href' => $mainImage->getUrl('thumb-main-product-page'), 'type' => $mainImage->mime_type];
            }
            $additionImages = $model->getMedia('addition_images');
            $additionImages->each(function ($image) use (&$links) {
                $links[] = (object)['href' => $image->getUrl(), 'type' => $image->mime_type];
                $links[] = (object)['href' => $image->getUrl('thumb-product-front'), 'type' => $image->mime_type];
            });
        }

        return view('front.pages.product.prefetch-links', ['links' => $links]);
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getProductabled()
    {
        return $model = $this->variants->count() ? $this->variants->first() : $this;
    }

    public function getAdminThumb()
    {
        return $this->getSingleImage('thumb-product-admin');
    }

    public function getFrontThumb()
    {
        return $this->getSingleImage('thumb-product-tile-front');
    }

    function getCacheKey(CustomerGroup $customerGroup){
        return 'customer-group-' . $customerGroup->id . '-product-' . $this->id . '-thumb';
    }

    public function issetCacheTile(CustomerGroup $customerGroup){
        $cacheKey = $this->getCacheKey($customerGroup);
        return Cache::has($cacheKey);
    }

    public function makeCacheTile(CustomerGroup $customerGroup){
        $cacheKey = $this->getCacheKey($customerGroup);
        $this->characteristics->each(function ($characteristic) {
            $characteristic->name = $characteristic->getNameAttribute();
        });
        $render = view('front.common.elements.product-thumb', ['product' => $this, 'customerGroup' => $customerGroup])->render();
        Cache::put($cacheKey, $render, 86400);
    }

    public static function cacheAllTiles()
    {
        Log::channel('exchange1c')->info('start ' . __METHOD__);
        $customerGroups = CustomerGroup::all();
        foreach($customerGroups as $customerGroup) {
            self::all()->each(function ($product) use ($customerGroup){
                $product->makeCacheTile($customerGroup);
            });
        }
        Log::channel('exchange1c')->info('finish ' . __METHOD__);
    }

    public function cacheTile(CustomerGroup $customerGroup):void{
        $render = view('front.common.elements.product-thumb', ['product' => $this, 'customerGroup' => $customerGroup])->render();
        $cacheKey = $this->getCacheKey($customerGroup);
        Cache::put($cacheKey, $render, 86400);
    }

    public function renderTile()
    {
        $cacheKey = $this->getCacheKey(Auth::user()->customerGroup??app()->defaultCustomerGroup);
        if (Cache::has($cacheKey)) {
            $render = Cache::get($cacheKey);
        } else {
            $this->characteristics->each(function ($characteristic) {
                $characteristic->name = $characteristic->getNameAttribute();
            });
            $render = view('front.common.elements.product-thumb', ['product' => $this, 'customerGroup' => Auth::user()->customerGroup??app()->defaultCustomerGroup])->render();
            Cache::put($cacheKey, $render, 86400);
        }
        return $render;
    }

    function getSingleImage(string $imageConversionName)
    {
        $model = $this->getProductabled();
        $mainImage = $model->getFirstMedia('main_image');
        if (empty($mainImage)) {
            $imagePlaceholder = Variable::find(1);
            $mainImage = $imagePlaceholder->getFirstMedia('main_image');
        }
        return '<img src="' . $mainImage->getUrl($imageConversionName) . '" alt="' . $this->name . '">';
    }

    /**
     * @return null|int
     * Возвращает наименьшую стоимость варианта данного товара для указанной группы покупателей
     */
    public function getProductVariantsMinPrice(): null|int
    {
        $variantModel = new ProductAttachedVariant();
        return $this->variants()
            ->join('price_of_customer_groups', 'price_of_customer_groups.saleable_id', '=', 'product_product_variant.id')
            ->where('price_of_customer_groups.customer_group_id', app()->currentCustomerGroup)
            ->where('price_of_customer_groups.saleable_type', get_class($variantModel))
            ->where('product_product_variant.product_id', $this->id)
            ->min('price_of_customer_groups.price');
    }

    /**
     * Возвращает минимальный прайс товара (включая варианты) для пользоватля в зависимости от групы.
     * @param Authenticatable $user
     * @return array|mixed|null
     */
    public function getMinPrice()
    {
        $productVariantMinPrice = $this->getProductVariantsMinPrice(app()->currentCustomerGroup);
        $productPrice = $this->getPrice(app()->currentCustomerGroup);
        return min_without_null([$productVariantMinPrice, $productPrice]);
    }

    public function getFirstVariantOrProductPrice(CustomerGroup $customerGroup): int|null
    {
        try {
            $price = $this->variants()->orderByDesc('custom_sort_order')->first()->getPrice($customerGroup);
        } catch (\Throwable $t) {
            dd($this->id);
        }
        return $price;
    }

    function getMainOemAttribute()
    {
        $attributes = $this->getAttributes();
        return $attributes['main_oem'];
    }

    function primaryOEM()
    {
        return $this->belongsTo(OEM::class, 'main_oem_id')->withDefault(new OEM());
    }

    function getPageAttribute()
    {
        $route_name = 'product-page';
        return Route::has($route_name) ? route($route_name, $this) : null;
    }

    function getPage()
    {
        return $this->getPageAttribute();
    }

    function getEditPageAttribute()
    {
        return route('admin.product.edit', $this);
    }

    function compatibleAutoModelModifications()
    {
        return $this->belongsToMany(AutoModelModification::class);
    }

    static function getProductsByFilter($filter)
    {
        $key = md5(serialize($filter));
        $mc = new \Memcached();
        $mc->addServer("127.0.0.1",11211);
        $productsIdxs = $mc->get($key);

        if(!$productsIdxs){
            //1) все продукты заданого типа
            $getProductsQuery = Product::where([['type_id', $filter[ProductType::class]]])->select('products.*')->distinct();

            if(ProductType::find($filter[ProductType::class])->compatibility_to_auto){
                $getProductsQuery->join('auto_model_modification_product', 'auto_model_modification_product.product_id', '=', 'products.id');
                //далее расширяющиеся условия выборки
                if ($filter[AutoModelModification::class] > 0) { //если есть модификация, ищем ищем только по ней
                    $getProductsQuery->where([['auto_model_modification_product.auto_model_modification_id', $filter[AutoModelModification::class]]]);
                } else {
                    if ($filter[AutoModel::class] > 0) { //если есть модель, ищем по ней
                        $getProductsQuery->join('auto_model_modifications', 'auto_model_modifications.id', '=', 'auto_model_modification_product.auto_model_modification_id');
                        $getProductsQuery->where([['auto_model_modifications.auto_model_id', $filter[AutoModel::class]]]);
                    } else {
                        if ($filter[AutoMark::class] > 0) { //если есть марка, ищем по ней
                            $getProductsQuery->join('auto_model_modifications', 'auto_model_modifications.id', '=', 'auto_model_modification_product.auto_model_modification_id');
                            $getProductsQuery->join('auto_models', 'auto_models.id', '=', 'auto_model_modifications.auto_model_id');
                            $getProductsQuery->where([['auto_models.auto_mark_id', $filter[AutoMark::class]]]);
                        }
                    }
                }
            }
            $productsIdxs = $getProductsQuery->pluck('id')->toArray();
            $mc->set($key, $productsIdxs);
        }

        $products = Product::find($productsIdxs);
        return $products;
    }

    public function type()
    {
        return $this->belongsTo(ProductType::class)->withDefault(new ProductType());
    }

    public function availableCharacteristics()
    {
        return $this->type->characteristics();
    }

    public function characteristics()
    {
        return $this->hasMany(ProductCharacteristic::class, 'product_id');
    }

    public function developer()
    {
        return $this->belongsTo(Developer::class)->withDefault(new Developer());
    }

    public function manufacturer()
    {
        return $this->developer();
    }

    public function trademark()
    {
        return $this->belongsTo(Developer::class, 'trademark_id')->withDefault(new Developer());
    }

    public function getBrand()
    {
        return $this->developer;
    }

    static function applyManufacturerFilter($products, $filterManufacturerData)
    {
        if (is_array($filterManufacturerData)) {
            $products = $products->whereIn('developer_id', $filterManufacturerData);
        }
        return $products;
    }

    static function applyCharacteristicsFilter($products, $filterCharacteristicsData)
    {
        if (!is_array($filterCharacteristicsData)) {
            return $products;
        }

        $characteristicSlugDictionary = array_flip(Characteristic::all()->pluck('slug', 'id')->toArray());
        $allProductCharacteristics = ProductCharacteristic::query();

        $productsIdxsFromFilter = []; //массив массивов id товаров, которые будут добавляться походу добавления фильтров
        //это "разрешительный" массив
        //если массива нет, то это равно массиву из всех товаров базы
        if (array_key_exists('enum', $filterCharacteristicsData)) {
            foreach ($filterCharacteristicsData['enum'] as $characteristicName => $characteristicValue) {
                $loopCharacteristics = (clone($allProductCharacteristics));
                $loopCharacteristics->where('characteristic_id', $characteristicSlugDictionary[$characteristicName]);
                if ($characteristicValue) {
                    $loopCharacteristics->where('value', $characteristicValue);
                }
                $loopCharacteristics = $loopCharacteristics->pluck('product_id')->toArray();
                $productsIdxsFromFilter [] = $loopCharacteristics;
            }
        }

        if (array_key_exists('number', $filterCharacteristicsData)) {
            foreach ($filterCharacteristicsData['number'] as $characteristicName => $characteristicDiapason) {
                $cidx = $characteristicSlugDictionary[$characteristicName];
                $loopCompatibilityProducts = (clone($allProductCharacteristics))
                    ->where('characteristic_id', $cidx)
                    ->where(function ($query) use ($characteristicDiapason) {
                        $query->where('numeric_value', '>=', (float)$characteristicDiapason['min'])->orWhereNull('numeric_value');
                    })
                    ->where(function ($query) use ($characteristicDiapason) {
                        $query->where('numeric_value', '<=', (float)$characteristicDiapason['max'])->orWhereNull('numeric_value');
                    })->pluck('product_id')->toArray();
                $productsIdxsFromFilter[] = $loopCompatibilityProducts;
            }
        }
        $productsIdxsFromFilterCount = count($productsIdxsFromFilter);

        if ($productsIdxsFromFilterCount >= 2) {
            $productsIdxsFromFilter = call_user_func_array('array_intersect', $productsIdxsFromFilter);
            $applyFilter = true;
        } elseif ($productsIdxsFromFilterCount === 1) {
            $productsIdxsFromFilter = current($productsIdxsFromFilter);
            $applyFilter = true;
        } elseif ($productsIdxsFromFilterCount < 1) {
            $applyFilter = false;
        } else {
            dd('Ошибка, такого не может быть');
        }

        if ($applyFilter) {
            $products = $products->whereIn('id', $productsIdxsFromFilter);
        }

        return $products;
    }

    public function getVariants()
    {
        return $this->variants;
    }

    //доступные для товара варианты (определенные в админке)
    public function variants()
    {
        return $this->hasMany(ProductAttachedVariant::class)->with('type');
    }

    public function getAttachmentVariants()
    {
        $this->variants()->each(function ($variant) {
            if (is_null($variant->custom_sort_order)) {
                $variant->custom_sort_order = $variant->type->sort_order;

            }
        });
        $this->variants = $this->variants->sortByDesc('custom_sort_order');
        return $this->variants;
    }

    //доступные для прикрепления к товару вариаты
    public function getAvailableProductVariants()
    {
        $existsProductVariantsIdxs = $this->variants->map(function ($variant) {
            return $variant->product_variant_id;
        });
        $availableProductVariants = ProductVariant::whereNotIn('id', $existsProductVariantsIdxs)->orderBy('sort_order')->get();
        return $availableProductVariants;
    }

    function getFirstAvailableProductVariant()
    {
        return $this->getAttachmentVariants()->first();
    }

    function getProductId()
    {
        return $this->id;
    }

    function getName()
    {
        return $this->name;
    }

    function getCalculatedProduct()
    {
        $variants = $this->variants()->orderBy('custom_sort_order')->get();
        if ($variants->count() > 0) {
            $calculatedProduct = $variants->first();
        } else {
            $calculatedProduct = $this;
        }
        return $calculatedProduct;
    }

    function getArticle()
    {
        return $this->shop_article;
    }

    function getPrimaryOEM()
    {
        return $this->getMainOemAttribute();
    }

    function getAssociatedText(): string
    {
        $text = [];
        $text[]= $fullCutedArticle =  $this->shop_article;
        $text[]= $leftCutedArticle = preg_replace('~^\D*~','', $this->shop_article);
        $text[]= $rightCutedArticle = preg_replace('~\D*$~','', $this->shop_article);
        $text[]= $cutedArticle = preg_replace('~\D*~','', $this->shop_article);
        $text[]= $this->main_oem;
        $text[]= $this->additional_oems;
        $text[]= $this->characteristics_raw;
        $text[]= $this->compatibility_raw;
        $text[]= $this->analog_articles;
        $text[]= $this->name;
        foreach($this->getAttachmentVariants() as $attachmentVariant) {
            $text[]= $attachmentVariant->custom_name;
            $text[]= $fullCutedArticle =  $attachmentVariant->shop_article;
            $text[]= $leftCutedArticle = preg_replace('~^\D*~','', $attachmentVariant->shop_article);
            $text[]= $rightCutedArticle = preg_replace('~\D*$~','', $attachmentVariant->shop_article);
            $text[]= $cutedArticle = preg_replace('~\D*~','', $attachmentVariant->shop_article);
        }
        return implode(' ', $text);

    }

    public function createProductCharacteristics(): void
    {

        /* //отладочный запрос, не удалять
         *
         * $query = DB::select("SELECT DISTINCT characteristic_id, c.name, product_type_id FROM characteristic_product_type
            INNER JOIN characteristics c on characteristic_product_type.characteristic_id = c.id
            INNER JOIN product_types pt ON characteristic_product_type.product_type_id = pt.id
            INNER JOIN products ON products.type_id = characteristic_product_type.product_type_id AND products.id = '".$this->id."';");
        dd($query);*/

        $productTypesCharacteristics = DB::select("SELECT DISTINCT characteristic_id, product_type_id FROM characteristic_product_type
            INNER JOIN product_types pt ON characteristic_product_type.product_type_id = pt.id
            INNER JOIN products ON products.type_id = characteristic_product_type.product_type_id AND products.id = '" . $this->id . "';");
        foreach ($productTypesCharacteristics as $values) {
            $products = ProductType::find($values->product_type_id)->getProducts();
            $products->each(function ($product) use ($values) {
                $queryCharacteristicsProductsQuery = DB::raw("SELECT characteristic_id, product_id FROM characteristic_product
                    WHERE characteristic_id = {$values->characteristic_id} AND product_id = {$product->id}
                ");
                $queryCharacteristicsProducts = DB::select($queryCharacteristicsProductsQuery);
                $characteristicsAmount = count($queryCharacteristicsProducts);
                if ($characteristicsAmount < 1) {
                    $insertProductCharacteristicsQuery = DB::raw("INSERT INTO characteristic_product (characteristic_id, product_id) VALUES ('{$values->characteristic_id}','{$product->id}')");
                    DB::insert($insertProductCharacteristicsQuery);
                }
            });
        }
    }

    public function numbersOfStorages(): BelongsToMany
    {
        return $this->belongsToMany(Storage::class, 'product_storage')->withPivot('number_of_goods');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function getReviewsAvg()
    {
        return $this->hasMany(Review::class)->median('rating');
    }

    public function getAdditionalOEMs(): array
    {
        return $this->additional_oems ? array_map(function ($oem) {
            return trim($oem);
        }, explode(',', trim($this->additional_oems))) : [];
    }

    public function relatedProducts()
    {
        return $this->belongsToMany(related: self::class, table: 'related_products', relatedKey: 'id', relatedPivotKey: 'related_id', foreignPivotKey: 'id');
    }

    public static function updateRelatedProducts()
    {
        Log::channel('exchange1c')->info('start ' . __METHOD__);
        $localFileName = self::loadJson(self::related_products_1c_url_path, self::related_products_1c_file);
        $feedContentData = self::parse1cData($localFileName);
        if ($feedContentData && count($feedContentData) > 0) {
            DB::table(self::related_products_table_name)->truncate();
            $queryValues = [];
            foreach ($feedContentData as $object_1c) {
                $queryValues[] = '("' . $object_1c->nomenkl_ID . '", "' . $object_1c->nomenkl_svyazannaya_ID . '")';
            }
            $query = "INSERT INTO " . self::related_products_table_name . " (id, related_id) VALUES " . implode(', ', $queryValues) . ";";
            DB::statement($query);
        }
        Log::channel('exchange1c')->info('finish ' . __METHOD__);
    }

    public static function updateFrom1c()
    {
        Log::channel('exchange1c')->info('start ' . __METHOD__);
        $feedContentData = self::parse1cData(storage_path(self::exchange_1c_file));
        if ($feedContentData && count($feedContentData) > 0) {
            DB::table((new self)->getTable())->truncate();
            array_map(function ($object_1c) {
                if ($object_1c->nomenklOsnTovar === 'Да') {
                    $productModel = new Product();
                    $productModel->id = $object_1c->nomenklID;
                    $productModel->name = $object_1c->nomenklNameInostr;
                    $productModel->slug = Str::slug(Str::lower(trim($object_1c->nomenklArticul)));
                    $productModel->type_id = $object_1c->nomenklParent_id;
                    $productModel->shop_article = $object_1c->nomenklArticul;
                    $productModel->main_oem = $object_1c->nomenklOEM;
                    $productModel->additional_oems = $object_1c->nomenklZamenaOEM;
                    $productModel->developer_id = $object_1c->nomenklManufacturer;
                    $productModel->trademark_id = $object_1c->nomenklTorgMarka;
                    $productModel->characteristics_raw = $object_1c->nomenklKharakt;
                    $productModel->compatibility_raw = $object_1c->nomenklApplicDesc;
                    $productModel->analog_articles = $object_1c->nomenklBrend; //(аналоги у конкурентов)
                    $productModel->save();
                    ProductAttachedVariant::where([['shop_article', 'like', $object_1c->nomenklArticul . '%']])
                        ->update(['product_id' => $object_1c->nomenklID]);
                }
            }, $feedContentData);
            $setProductVariantsWithOEMQuery = 'UPDATE product_product_variant ppv INNER JOIN products p ON ppv.main_oem = p.main_oem SET ppv.product_id = p.id WHERE 1;';
            DB::statement($setProductVariantsWithOEMQuery);
            $setProductVariantsWithUUIDQuery = 'UPDATE product_product_variant ppv INNER JOIN products p ON ppv.id = p.id SET ppv.product_id = p.id, ppv.sort_order = 1, ppv.custom_sort_order = 1 WHERE 1;';
            DB::statement($setProductVariantsWithUUIDQuery);
        }
        Log::channel('exchange1c')->info('finish ' . __METHOD__);
    }

    public static function checkAndReportOfEmptyCharacteristics()
    {
        $products = ProductCharacteristic::getNullableCharacteristicProducts();
        $uncorrect_products_amount = $products->uncorrect_products->count();
        if ($uncorrect_products_amount > 0) {
            send_tg_message('В выгрузке имеются товары с незаполненными характеристиками в количестве ' . $uncorrect_products_amount . ' единиц. Такие товары не отображаются при использовании фильтра' .
                '.  <a href="' . route('admin.empty-characteristics-products') . '">Ссылка на недозаполненные товары</a>. Для сравнения <a href="' . route('admin.full-filled-characteristics-products') . '">Ссылка на заполненные товары</a> (' . $products->correct_products->count() . ' единиц) ');
        }
    }

    public static function updateGeneralAmount(){
        Log::channel('exchange1c')->info('start ' . __METHOD__);
        $updateCacheProductsTableColumnQuery = 'UPDATE products p INNER JOIN
(SELECT p.id as product_id, SUM(number_of_goods) as general_amount FROM products as p INNER JOIN product_product_variant ppv on p.id = ppv.product_id
INNER JOIN product_storage ps on ppv.id = ps.product_id GROUP BY p.id) AS calculateAmount ON p.id = calculateAmount.product_id
SET p.general_amount = calculateAmount.general_amount WHERE true;';
        DB::statement($updateCacheProductsTableColumnQuery);
        Log::channel('exchange1c')->info('finish ' . __METHOD__);
    }

    public function associatedSearchModel(){
        return $this->morphMany(Search::class, 'searcheable')->first();
    }
}
