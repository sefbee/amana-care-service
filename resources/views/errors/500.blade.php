@extends('errors::minimal')

@section('title', __('Server Error'))
@section('code', '500')
@section('message', __('Server Error'))

@section('content')
<!--begin::Title-->
<h1 class="fw-bolder fs-2qx text-gray-900 mb-4">System Error</h1>
<!--end::Title-->
<!--begin::Text-->
<div class="fw-semibold fs-6 text-gray-500 mb-7">Something went wrong! Please try again later.</div>
<!--end::Text-->
<!--begin::Illustration-->
<div class="mb-11">
	<img src="{{ asset('theme/media/auth/500-error.png') }}" class="mw-100 mh-300px theme-light-show" alt="" />
	<img src="{{ asset('theme/media/auth/500-error-dark.png') }}" class="mw-100 mh-300px theme-dark-show" alt="" />
</div>
<!--end::Illustration-->

@endsection
