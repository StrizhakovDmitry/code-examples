@extends('admin.common.layout')
@section('content')
    <form id="edit-form" data-product-id="{{ $product->id }}" class="uk-padding-small uk-form-horizontal" method="POST"
          action="{{route('admin.product.update', $product)}}" enctype="multipart/form-data">
   @csrf
   @include('admin.common.fields.string',['label' => 'Название', 'required' => true, 'field_name' => 'name'])
   @include('admin.common.fields.slug')
   @include('admin.common.fields.belongs-to-field',['label' => 'Тип товара','relation' => $model->type()])
   @include('admin.common.fields.string',['label' => 'Артикул PST-Center', 'required' => true, 'field_name' => 'shop_article'])
   @include('admin.common.fields.belongs-to-field',['label' => 'Производитель','relation' => $model->developer()])
   @include('admin.common.fields.belongs-to-field',['label' => 'Торговая марка','relation' => $model->trademark()])
   <div class="uk-margin">
       <label class="uk-form-label">Характеристики</label>
       <div class="uk-form-controls uk-form-width-large">
           {{ !$product->exists?'Выберите тип товара и сохраните товар перед заполнением характеристик':'' }}
           @if($product->type->exists && $availableCharacteristics->count() < 1)
               Выберите доступные для типа <a
                   href="{{$product->type->getEditPage()}}">{{ $product->type->name }}</a> характеристики.
           @endif
           <div class="available-characteristics">
               @foreach($availableCharacteristics as $availableCharacteristic)
                   <label
                       for="characteristic_{{$availableCharacteristic->id}}">{{$availableCharacteristic->name}}</label>
                   @if($availableCharacteristic->characteristic_type_id === 1)
                       <input id="characteristic_{{$availableCharacteristic->id}}"
                              class="uk-input uk-form-width-medium"
                              name="available_characteristic[{{$availableCharacteristic->id}}]" type="number"
                              step="0.01"
                              value="{{ optional($productCharacteristics->firstWhere('characteristic_id', $availableCharacteristic->id))->numeric_value }}">
                   @else
                       <input id="characteristic_{{$availableCharacteristic->id}}"
                              class="uk-input uk-form-width-medium"
                              name="available_characteristic[{{$availableCharacteristic->id}}]" type="text"
                              value="{{ optional($productCharacteristics->firstWhere('characteristic_id', $availableCharacteristic->id))->value }}">
                   @endif
                   <span class="measure">{{$availableCharacteristic->measure}}</span>
               @endforeach
           </div>
       </div>
   </div>
   <div class="uk-margin">
       <label class="uk-form-label">Количество на складе</label>
       <div class="uk-form-controls storages-list">
           @foreach($storages as $storage)
               <div class="storage">
                   <input id="stock_{{ $storage->id }}" name="storage[{{ $storage->id }}]" type="number"
                          class="uk-input uk-form-width-small"
                          value="{{ $productsOfStocks->find($storage)?->pivot->number_of_goods }}">
                   <label for="stock_{{ $storage->id }}">{{ $storage->name }}</label>
               </div>
           @endforeach
       </div>
   </div>
   @include('admin.models.product.prices-for-customer-groups')
   @include('admin.common.fields.belongs-to-field',['label' => 'Основной OEM','relation' => $model->primaryOEM()])
   {{--@include('admin.common.fields.string',['label' => 'Дополнительные OEM','relation' => $product->secondaryOEMs()])--}}
   @include('admin.common.fields.belong-to-many-field',['label' => 'Совместимость с авто','relation' => $product->compatibleAutoModelModifications()])
   @include('admin.common.fields.checkbox-field', ['label' => 'В наличие','field_name' => 'is_available'])
   @include('admin.common.fields.checkbox-field', ['label' => 'Акция','field_name' => 'stock'])
   @include('admin.common.fields.html-field',['label' => 'Аналоги', 'field_name' => 'analogs'])
   @include('admin.common.fields.wysiwyg', ['label' => 'Описание','field_name' => 'description', 'model' => $product])
   @include('admin.common.fields.string',['label' => 'Видео на youtube', 'required' => false, 'field_name' => 'video_url'])
   @include('admin.common.fields.main-image',['model' => $product])
   @include('admin.common.fields.addition-images',['model' => $product])
</form>
@include('admin.models.product.product-variants-block')
<div class="edit-tools">
   <button type="submit" form="edit-form" class="tool save" title="сохранить"></button>
</div>
@endsection
