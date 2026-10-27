@extends('layouts.base')
@section('title', 'Draft Preview')

@php
    $breadcrumb = [
        ['title' => 'Website', 'url' => route('cms.pages.index')],
        ['title' => 'Pages', 'url' => route('cms.pages.index')],
        ['title' => $page->title, 'url' => route('cms.pages.edit', $page)],
        ['title' => 'Draft Preview'],
    ];
@endphp

@section('actions')
    <a href="{{ route('cms.pages.edit', $page) }}" class="btn btn-light-primary btn-sm">Back to Editor</a>
@endsection

@section('content')
<div class="row">
    <div class="col-md-10 offset-md-1">
        <x-alert :show_validations="false" />
        <div class="alert alert-info">
            <strong>Draft preview</strong> · /{{ $page->slug }}<br>
            <span>Meta title: {{ $page->meta_title }}</span><br>
            <span>Meta description: {{ $page->meta_description }}</span>
            @if($page->canonical_url)<br><span>Canonical URL: {{ $page->canonical_url }}</span>@endif
            @if($openGraphMedia)<br><img src="{{ $openGraphMedia->url() }}" alt="{{ $openGraphMedia->alt_text }}" class="img-thumbnail mt-2" style="max-height:120px">@endif
        </div>
        @foreach($sections as $section)
            @php $content = $section->content ?? []; @endphp
            <section class="card shadow-sm mb-4 {{ $section->is_active ? '' : 'opacity-50' }}">
                @if(!$section->is_active)<div class="card-header"><span class="badge badge-light-warning">Hidden in draft</span></div>@endif
                <div class="card-body p-4 p-lg-5">
                    @switch($section->type)
                        @case('hero')
                            @if(!empty($content['background_media_id']) && ($media = $mediaById->get($content['background_media_id'])))
                                <img src="{{ $media->url() }}" alt="{{ $media->alt_text }}" class="img-fluid rounded mb-4" style="max-height:360px; width:100%; object-fit:cover">
                            @endif
                            @if(!empty($content['eyebrow']))<div class="text-uppercase text-muted small mb-2">{{ $content['eyebrow'] }}</div>@endif
                            <h1 class="fw-bold">{{ $content['headline'] ?? '' }}</h1>
                            @if(!empty($content['subheadline']))<p class="lead">{{ $content['subheadline'] }}</p>@endif
                            @if(!empty($content['cta_label']))<a href="{{ $content['cta_link'] ?? '#' }}" class="btn btn-primary">{{ $content['cta_label'] }}</a>@endif
                            @break
                        @case('rich_text')
                            <div class="cms-rich-text">{!! $content['html'] ?? '' !!}</div>
                            @break
                        @case('service_grid')
                            <h2>{{ $content['title'] ?? 'Services' }}</h2>
                            @if(!empty($content['body']))<p>{{ $content['body'] }}</p>@endif
                            <div class="row g-3">
                                @foreach($services->take((int) ($content['limit'] ?? 4)) as $serviceEntry)
                                    <div class="col-md-6"><div class="border rounded p-3 h-100"><h5>{{ $serviceEntry['service']->name }}</h5><p>{{ $serviceEntry['service']->description }}</p>@if(!empty($content['show_prices']) && $serviceEntry['price'])<strong>{{ $serviceEntry['price']->formatted_amount }} / {{ $serviceEntry['price']->unit }}</strong>@endif</div></div>
                                @endforeach
                            </div>
                            @break
                        @case('image_text_split')
                            @php $splitMedia = !empty($content['media_id']) ? $mediaById->get($content['media_id']) : null; @endphp
                            <div class="row align-items-center g-4">
                                @if($splitMedia && ($content['layout_alignment'] ?? 'left') === 'left')<div class="col-md-6"><img src="{{ $splitMedia->url() }}" alt="{{ $splitMedia->alt_text }}" class="img-fluid rounded"></div>@endif
                                <div class="col-md-6"><h2>{{ $content['title'] ?? '' }}</h2><p>{{ $content['body'] ?? '' }}</p>@if(!empty($content['cta_label']))<a href="{{ $content['cta_link'] ?? '#' }}" class="btn btn-primary">{{ $content['cta_label'] }}</a>@endif</div>
                                @if($splitMedia && ($content['layout_alignment'] ?? 'left') === 'right')<div class="col-md-6"><img src="{{ $splitMedia->url() }}" alt="{{ $splitMedia->alt_text }}" class="img-fluid rounded"></div>@endif
                            </div>
                            @break
                        @case('testimonial_carousel')
                            <h2>{{ $content['title'] ?? 'Testimonials' }}</h2>
                            <div class="row g-3">@foreach($content['items'] ?? [] as $item)<div class="col-md-6"><blockquote class="border rounded p-3 h-100"><p>“{{ $item['review_text'] ?? '' }}”</p><footer>{{ $item['client_name'] ?? '' }} · {{ $item['rating'] ?? '' }}/5</footer></blockquote></div>@endforeach</div>
                            @break
                        @case('faq_accordion')
                            <h2>{{ $content['title'] ?? 'Frequently asked questions' }}</h2>
                            @foreach($content['items'] ?? [] as $item)<details class="border-bottom py-3"><summary class="fw-semibold">{{ $item['question'] ?? '' }}</summary><div class="pt-2">{{ $item['answer'] ?? '' }}</div></details>@endforeach
                            @break
                        @case('dynamic_lead_form')
                            <h2>{{ $content['title'] ?? 'Contact us' }}</h2><p>{{ $content['body'] ?? '' }}</p>
                            <div class="row"><div class="col-md-8">@foreach($content['fields'] ?? [] as $field)<div class="mb-3"><label class="form-label">{{ $field['label'] ?? '' }}</label><input class="form-control" disabled placeholder="{{ $field['key'] ?? '' }}"></div>@endforeach<button type="button" class="btn btn-primary" disabled>Submit inquiry (preview)</button></div></div>
                            @break
                    @endswitch
                </div>
            </section>
        @endforeach
        @if($sections->isEmpty())<div class="card"><div class="card-body text-center text-muted py-5">No sections to preview.</div></div>@endif
    </div>
</div>
@endsection
