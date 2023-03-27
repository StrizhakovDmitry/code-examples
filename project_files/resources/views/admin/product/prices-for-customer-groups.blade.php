<?php
use App\Models\CustomerGroup;
$customerGroups = CustomerGroup::orderBy('sort_order')->get();
?>
<div class="uk-margin price-for-groups-edit-field">
    <label class="uk-form-label">Цена для группы</label>
    <div class="uk-form-controls price-for-groups-list">
        @foreach($customerGroups as $customerGroup)
            <div class="price-for-groups">
                <input id="{{mb_strtolower(class_basename($model))}}_price_for_group_{{ $customerGroup->id }}" name="{{mb_strtolower(class_basename($model))}}_price_for_group[{{ $customerGroup->id }}]"
                       type="number"
                       class="uk-input uk-form-width-small"
                       value="{{ $model->getPrice($customerGroup) }}">
                <label for="{{mb_strtolower(class_basename($model))}}_price_for_group_{{ $customerGroup->id }}">{{ $customerGroup->name }}</label>
            </div>
        @endforeach
    </div>
</div>
