<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', config('app.name')) | {{ config('app.name') }}</title>

    <meta name="description" content="{{ config('starter.purpose') }}">
    <meta name="robots" content="noindex, nofollow">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset("img/favicon.ico?v=2") }}" />
    <meta name="theme-color" content="{{ config('starter.primary_color') }}">
    <style nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">:root { --bs-primary: {{ config('starter.primary_color') }}; }</style>
    <!--begin::Fonts(mandatory for all pages)-->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <!--end::Fonts-->
    <!--begin::Vendor Stylesheets(used for this page only)-->
    @stack('vendor_styles')
    @yield('datatable_styles')
    <!--end::Vendor Stylesheets-->

    <!--begin::Global Stylesheets Bundle(mandatory for all pages)-->
    <link href="{{ asset('theme/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('theme/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    <!--end::Global Stylesheets Bundle-->

    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        // Frame-busting to prevent site from being loaded within a frame without permission (click-jacking)
        if (window.top != window.self) { window.top.location.replace(window.self.location.href); }
    </script>
    <!-- Scripts -->
    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        window.Laravel = {!! json_encode(['csrfToken' => csrf_token()]) !!};
    </script>
    {{--    @livewireStyles--}}
    {{-- <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}" src="{{ asset('vendor/ckeditor/ckeditor.js') }}"></script> --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @yield('styles')
</head>

<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true"
      data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-hoverable="true"
      data-kt-app-sidebar-push-header="true" data-kt-app-sidebar-push-toolbar="true"
      data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" class="app-default">
<!--begin::Theme mode setup on page load-->
<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    var defaultThemeMode = "light";
    var themeMode;
    if (document.documentElement) {
        if (document.documentElement.hasAttribute("data-bs-theme-mode")) {
            themeMode = document.documentElement.getAttribute("data-bs-theme-mode");
        } else {
            if (localStorage.getItem("data-bs-theme") !== null) {
                themeMode = localStorage.getItem("data-bs-theme");
            } else {
                themeMode = defaultThemeMode;
            }
        }
        if (themeMode === "system") {
            themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
        }
        document.documentElement.setAttribute("data-bs-theme", themeMode);
    }
</script>
<!--end::Theme mode setup on page load-->

<div id="loader"></div>

<!--begin::App-->
<div class="d-flex flex-column flex-root app-root" id="kt_app_root">
    <!--begin::Page-->
    <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
        <!--begin::Header-->
        <x-header />
        <!--end::Header-->

        <!--begin::Wrapper-->
        <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
            <!--begin::Sidebar-->
            <x-left-nav />
            <!--end::Sidebar-->

            <!--begin::Main-->
            <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
                <!--begin::Content wrapper-->
                <div class="d-flex flex-column flex-column-fluid">

                    <!--begin::Toolbar-->
                    {{-- @yield('breadcrumb') --}}
                    @if (isset($breadcrumb))
                        @php
                            $last_item = end($breadcrumb);
                        @endphp
                        <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                            <!--begin::Toolbar container-->
                            <div id="kt_app_toolbar_container"
                                 class="app-container container-fluid d-flex flex-stack">
                                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                                    <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                                        {{ $last_item['title'] }}</h1>
                                    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                        @foreach ($breadcrumb as $index => $b_item)
                                            <li class="breadcrumb-item">
                                                <a href="{{ $b_item['url'] ?? '#' }}"
                                                   class="{{ $loop->last ? "text-muted" : '' }} text-hover-primary">{{ $b_item['title'] }}</a>
                                            </li>
                                            @if (!$loop->last)
                                                <li class="breadcrumb-item">
                                                    <i class="fa fa-arrow-right gray-500 fs-9"></i>
                                                    {{-- <span class="bullet bg-gray-500 w-5px h-2px"></span> --}}
                                                </li>
                                            @endif
                                        @endforeach
                                    </ul>
                                    @yield('tabs')
                                </div>

                                <div class="">
                                    @yield('actions')
                                </div>
                            </div>
                            <!--end::Toolbar container-->
                        </div>
                    @endif
                    <!--end::Toolbar-->

                    <!--begin::Content-->
                    <div id="kt_app_content" class="app-content flex-column-fluid">
                        <!--begin::Content container-->
                        <div id="kt_app_content_container" class="app-container container-fluid">
                            @yield('content')
                        </div>
                        <!--end::Content container-->
                    </div>
                    <!--end::Content-->

                </div>
                <!--end::Content wrapper-->

                <!--begin::Footer-->
                <x-footer />
                <!--end::Footer-->
            </div>
            <!--end:::Main-->
        </div>
        <!--end::Wrapper-->
    </div>
    <!--end::Page-->
</div>
<!--end::App-->

<!--begin::Scrolltop-->
<div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true">
    <i class="ki-outline ki-arrow-up"></i>
</div>
<!--end::Scrolltop-->


<!--begin::Javascript-->
<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    var hostUrl = "assets/";
</script>
<!--begin::Global Javascript Bundle(mandatory for all pages)-->
<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}" src="{{ asset('theme/plugins/global/plugins.bundle.js') }}"></script>
<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}" src="{{ asset('theme/js/scripts.bundle.js') }}"></script>
<!--end::Global Javascript Bundle-->


<!--begin::Vendors Javascript(used for this page only)-->
{{-- <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}" src="{{ asset('theme/plugins/custom/fullcalendar/fullcalendar.bundle.js') }}"></script> --}}
{{-- <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}" src="{{ asset('theme/plugins/custom/formrepeater/formrepeater.bundle.js') }}"></script> --}}
{{-- <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}" src="{{ asset("theme/plugins/custom/datatables/datatables.bundle.js") }}"></script> --}}

<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}" type="text/javascript">
    $(document).ready(function() {
        $('.select2').select2();

        $(".table").on('change', '.form-switch-input', function(e) {
            const target = $(this).data('target');
            $('#loader').show();
            $.ajax({
                method: 'post',
                url: target,
                cache: false,
                data: {
                    'toggle_input' : $(this).prop('checked')
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
            })
                .done(function(res) {
                    $('#loader').hide();
                    location.reload();
                })
        })
    });

    function deleteItemById(item_id) {
        if (confirm('Are you sure want to delete this?')) {
            $(`#${item_id}`).slideUp(300, function() {
                $(this).remove();
            });
        }
        return
    }

    function convert24To12Format(time24) {
        const [sHours, minutes] = time24.match(/([0-9]{1,2}):([0-9]{2})/).slice(1);
        const period = +sHours < 12 ? 'AM' : 'PM';
        const hours = +sHours % 12 || 12;
        return `${hours}:${minutes} ${period}`;
    }

    function confirmDelete(formId = 'deleteForm') {
        if (confirm('Are you sure want to delete?')) {
            document.getElementById(formId)?.requestSubmit();
            return true;
        } else {
            return false;
        }
    }

    function confirmDeleteButton() {
        if (confirm('Are you sure want to delete?')) {
            return true
        } else {
            return false;
        }
    }

    function confirmPasswordReset() {
        if (confirm('Are you sure want to reset password?')) {
            return true
        } else {
            return false;
        }
    }

    function confirmDuplicateQuestion() {
        if (confirm('Are you sure want to duplicate this question?')) {
            return true
        } else {
            return false;
        }
    }

    document.addEventListener('submit', function(event) {
        const form = event.target;
        const message = form.dataset.confirm || event.submitter?.dataset.confirm;
        if (message && !window.confirm(message)) {
            event.preventDefault();
        }
    });

    document.addEventListener('click', function(event) {
        const target = event.target instanceof Element ? event.target : null;
        if (!target) return;

        const deleteButton = target.closest('[data-confirm-delete]');
        if (deleteButton) {
            event.preventDefault();
            confirmDelete(deleteButton.dataset.confirmDelete);
            return;
        }

        const logoutLink = target.closest('[data-logout-link], [data-logout-form]');
        if (logoutLink) {
            event.preventDefault();
            const formId = logoutLink.dataset.logoutForm;
            const logoutForm = formId ? document.getElementById(formId) : logoutLink.closest('form');
            logoutForm?.requestSubmit();
            return;
        }

        const confirmLink = target.closest('a[data-confirm]');
        if (confirmLink && !window.confirm(confirmLink.dataset.confirm)) {
            event.preventDefault();
        }
    });

    document.addEventListener('change', function(event) {
        const target = event.target;
        if (!(target instanceof HTMLInputElement || target instanceof HTMLSelectElement)) return;

        if (target.matches('[data-check-all]')) {
            document.getElementsByName(target.dataset.checkAll).forEach(function(input) {
                input.checked = target.checked;
            });
        }

        if (target.matches('[data-submit-on-change]')) {
            target.form?.requestSubmit();
        }
    });

    function slugify(str, slug_str = '-', word_length = null) {
        str = str.replace(/^\s+|\s+$/g, ''); // trim leading/trailing white space
        str = str.toLowerCase(); // convert string to lowercase
        str = str.replace(/[^a-z0-9 -]/g, '') // remove any non-alphanumeric characters
            .replace(/\s+/g, slug_str) // replace spaces with hyphens
            .replace(/-+/g, slug_str); // remove consecutive hyphens

        if (word_length) {
            const words = str.split(slug_str);
            str = words.slice(0, word_length).join(slug_str);
        }

        return str;
    }

    function copyToClipboard(text) {
        navigator.clipboard.writeText('${'+text+'}').then(function() {
            alert("Copied to clipboard: " + text);
        }).catch(function(error) {
            alert("Failed to copy: " + error);
        });
    }

    // function copyToClipboard(text) {
    //     const tempInput = document.createElement('input');
    //     tempInput.value = text;
    //     document.body.appendChild(tempInput);
    //     tempInput.select();
    //
    //     try {
    //         const successful = document.execCommand('copy');
    //         alert(successful ? 'Copied to clipboard ' + text : 'Failed to copy');
    //     } catch (err) {
    //         console.error('Copy error: ', err);
    //     }
    //     document.body.removeChild(tempInput);
    // }

</script>



@stack('vendor_scripts')
@yield('datatable_scripts')
<!--end::Vendors Javascript-->

<!-- LiveWIre Script !-->
{{--@livewireScripts--}}

<!--begin::Custom Javascript(used for this page only)-->
@stack('scripts')

{{-- <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}" src="{{ asset("theme/js/widgets.bundle.js") }}"></script> --}}
{{-- <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}" src="{{ asset("theme/js/custom/widgets.js") }}"></script> --}}
{{-- <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}" src="{{ asset("theme/js/custom/apps/chat/chat.js") }}"></script> --}}
<!--end::Custom Javascript-->
<!--end::Javascript-->
</body>

</html>
