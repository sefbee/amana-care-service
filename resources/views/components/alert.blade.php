@props(['show_validations'=> true])
@if (isset($message))
    <div class="alert alert-{{ isset($class) ? $class : 'success' }} alert-dismissible d-flex align-items-center p-5">
        <i class="ki-outline ki-shield-tick fs-2hx text-success me-4"><span class="path1"></span><span
                class="path2"></span></i>
        <div class="d-flex flex-column">
            <h4 class="mb-1 text-dark">{{ isset($type) ? ucfirst($type) : 'Info' }}!!</h4>

            <span>
                @if (is_array($message))
                @foreach ($message as $item)
                {{ $item }} <br>
                @endforeach
                @else
                {{ $message }}
                @endif

            </span>
        </div>
    </div>
@elseif ($errors->any() && $show_validations)
    <div class="alert alert-danger alert-dismissible d-flex align-items-center p-5">
        <!--begin::Icon-->
        <i class="ki-outline ki-information-5 text-danger fs-2hx me-4"></i>
        <!--end::Icon-->
        <!--begin::Wrapper-->
        <div class="d-flex flex-column">
            <!--begin::Title-->
            <h4 class="mb-1 text-dark">Error!</h4>
            <!--end::Title-->

            <!--begin::Content-->
            <span>
                @foreach ($errors->all() as $error)
                {{ $error }}
                <br>
                @endforeach
            </span>
            <!--end::Content-->
        </div>
    </div>
@endif