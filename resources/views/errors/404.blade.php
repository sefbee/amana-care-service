@extends('errors::layout-styled')

@section('title', __('Not Found'))
@section('code', '404')
@section('message', __('Not Found'))

@section('content')

<!--begin::Title-->
<h1 class="fw-bolder fs-2hx text-gray-900 mb-4">
    Oops!
</h1>
<!--end::Title-->
<!--begin::Text-->
<div class="fw-semibold fs-6 text-gray-500 mb-7">
    Not found
</div>
<!--end::Text-->
<!--begin::Illustration-->
<div class="mb-3">
    <img
        src="{{ asset('theme/media/auth/404-error.png') }}"
        class="mw-100 mh-300px theme-light-show"
        alt="404 image"
    />
    <img
        src="{{ asset('theme/media/auth/404-error-dark.png') }}"
        class="mw-100 mh-300px theme-dark-show"
        alt=""
    />
</div>
<!--end::Illustration-->

@endsection
