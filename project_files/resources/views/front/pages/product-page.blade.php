@extends('front.common.layout')
@section('content')
    {!! $product->getFrontPrefetchImageLinks() !!}
    @include('front.common.sections.product-main-part')
    @if($relatedProducts->count() > 0)
    @include('front.common.sections.related-products-block')
    @endif
@endsection
@push('header_custom_content')
    <meta name="product_id" content="{{ $product->id }}">
@endpush
