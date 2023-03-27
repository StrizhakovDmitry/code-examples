<div class="addition-variant-tool" data-additional-variants-tool>
    @if($availableProductVariants->count() > 0)
        <label for="data-available-variants"><b>Добавить новый вариант:</b> </label>
        <select class="uk-select" data-available-variants id="data-available-variants">
            @foreach($availableProductVariants as $availableProductVariant)
                <option value="{{ $availableProductVariant->id }}">{{ $availableProductVariant->name }}</option>
            @endforeach
        </select>
    @endif
    <button class="tool add" data-additional-variants-button></button>
</div>
