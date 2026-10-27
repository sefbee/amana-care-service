<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', 'Error') | {{ config('app.name') }}</title>

    <link rel="shortcut icon" href="{{ asset("img/favicon.ico?v=2") }}" />
    <!--begin::Fonts(mandatory for all pages)-->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <!--end::Fonts-->
    <!--begin::Global Stylesheets Bundle(mandatory for all pages)-->
    <link href="{{ asset("theme/plugins/global/plugins.bundle.css") }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset("theme/css/style.bundle.css") }}" rel="stylesheet" type="text/css" />
    <!--end::Global Stylesheets Bundle-->
    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        // Frame-busting to prevent site from being loaded within a frame without permission (click-jacking) if (window.top != window.self) { window.top.location.replace(window.self.location.href); }
    </script>
</head>
    <body
        id="kt_body"
        class="app-blank bgi-size-cover bgi-position-center bgi-no-repeat"
    >
        <!--begin::Theme mode setup on page load-->
        <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
            var defaultThemeMode = "light";
            var themeMode;
            if (document.documentElement) {
                if (
                    document.documentElement.hasAttribute("data-bs-theme-mode")
                ) {
                    themeMode =
                        document.documentElement.getAttribute(
                            "data-bs-theme-mode",
                        );
                } else {
                    if (localStorage.getItem("data-bs-theme") !== null) {
                        themeMode = localStorage.getItem("data-bs-theme");
                    } else {
                        themeMode = defaultThemeMode;
                    }
                }
                if (themeMode === "system") {
                    themeMode = window.matchMedia(
                        "(prefers-color-scheme: dark)",
                    ).matches
                        ? "dark"
                        : "light";
                }
                document.documentElement.setAttribute(
                    "data-bs-theme",
                    themeMode,
                );
            }
        </script>

        <div class="d-flex flex-column flex-root" id="kt_app_root">
            <!--begin::Page bg image-->
            <style nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
                body {
                    background-image: url('{{ asset("theme/media/auth/bg1.jpg") }}');
                }
                [data-bs-theme="dark"] body {
                    background-image: url('{{ asset("theme/media/auth/bg1-dark.jpg") }}');
                }
            </style>
            <!--end::Page bg image-->
            <!--begin::Authentication - Signup Welcome Message -->
            <div class="d-flex flex-column flex-center flex-column-fluid">
                <!--begin::Content-->
                <div class="d-flex flex-column flex-center text-center p-10">
                    <!--begin::Wrapper-->
                    <div class="card card-flush w-lg-650px py-5">
                        <div class="card-body py-15 py-lg-20">

                            @yield('content')

                            <!--begin::Link-->
                            <div class="mb-0">
                                <a
                                    href="{{ route('dashboard') }}"
                                    class="btn btn-sm btn-primary"
                                    >Return Home</a
                                >
                            </div>
                            <!--end::Link-->
                        </div>
                    </div>
                    <!--end::Wrapper-->
                </div>
                <!--end::Content-->
            </div>
            <!--end::Authentication - Signup Welcome Message-->
        </div>

        <!--begin::Javascript-->
        <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
            var hostUrl = "assets/";
        </script>
        <!--begin::Global Javascript Bundle(mandatory for all pages)-->
        <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}" src="{{ asset("theme/plugins/global/plugins.bundle.js") }}"></script>
        <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}" src="{{ asset("theme/js/scripts.bundle.js") }}"></script>
        <!--end::Global Javascript Bundle-->
        <!--end::Javascript-->
    </body>
</html>
