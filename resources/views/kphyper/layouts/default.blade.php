<!DOCTYPE html>
<html lang="{{ str_replace('_','-',strtolower(app()->getLocale())) }}">
@include('kphyper.layouts._header')
<body data-layout="topnav">
    <div class="wrapper">
        <div class="content-page">
            <div class="content">
                @include('kphyper.layouts._nav')
                <div class="container">
                    @yield('content')
                </div>
            </div><!-- content -->
            @include('kphyper.layouts._footer')
        </div><!-- content-page -->
    </div><!-- wrapper -->
    @include('kphyper.layouts._script')
    @section('js')
    @show
</body>
</html>