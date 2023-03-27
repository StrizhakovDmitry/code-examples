@if($entity->exists && $entity->getImageUrlAttribute($imageNumber))
    <img class="uploaded-image" src="{{ $entity->getImageUrlAttribute($imageNumber) }}?timestamp={{ time() }}" alt="{{ $entity->name }}">
@endif
<div class="delete-attache-tool uk-margin uk-flex uk-flex-column">
    <div class="uk-margin" uk-margin>
        <div uk-form-custom="target: true">
            <input type="file" name="{{ 'image_'.$imageNumber }}">
            <input class="uk-input uk-form-width-large" type="text" placeholder="Выбрать картинку" disabled>
        </div>
    </div>
    <label class="uk-margin">
        <span>Удалить картинку</span>&nbsp;<input class="uk-checkbox" type="checkbox" name="{{ 'delete_image_'.$imageNumber }}">
    </label>
</div>
