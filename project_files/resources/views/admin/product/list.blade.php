@foreach($products as $product)
    <tr class="entity" data-entity-id="{{$product->id}}">
        <td>{{ $product->id }}</td>
        <td>{{ $product->type->getNameWithPath() }}</td>
        <td><a href="{{ $product->page }}">{{ $product->name }}</a></td>
        <td>{!! $product->getAdminThumb() !!}</td>
        <td><a href="{{ route("admin.product.edit", $product) }}">редактировать</a></td>
        @include('admin.common.change-order-tool',['up' => route('admin.product.change-order', ['product'=>$product, 'direction' => 'up']), 'down' => route('admin.product.change-order', ['product'=>$product, 'direction' => 'down'])])
        <td><a onclick="return confirm('Вы уверены?')" class="btn btn-danger" href="{{ route("admin.product.delete", $product) }}">удалить</a></td>
    </tr>
@endforeach
