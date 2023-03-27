@extends('admin.common.layout')
@section('content')
    <table class="uk-table uk-table-striped uk-table-hover uk-table-small uk-table-responsive">
        <thead>
        </thead>
        <tbody data-entities-list>
        @include('admin.models.product.list')
        </tbody>
    </table>
    <div class="pagination">
        {!! $products->links('admin.common.pagination-links') !!}
    </div>
    <div class="edit-tools">
        <a href="{{ route('admin.product.edit') }}" class="tool add" title="создать"></a>
    </div>
@endsection
