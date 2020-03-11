<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Title -->
    <title>{{ $page_title ?: config('app.name') }}</title>

    <!-- Bootstrap 3.3.7 -->
    <link rel="stylesheet" href="{{ asset("CRM/AdminLTE-2.4.2/bower_components/bootstrap/dist/css/bootstrap.min.css", env('IS_HTTPS', false)) }}">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset("CRM/AdminLTE-2.4.2/bower_components/font-awesome/css/font-awesome.min.css", env('IS_HTTPS', false)) }}">

    <!-- Ionicons -->
    <link rel="stylesheet" href="{{ asset("CRM/AdminLTE-2.4.2/bower_components/Ionicons/css/ionicons.min.css", env('IS_HTTPS', false)) }}">

    @yield('styles')

    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset("CRM/AdminLTE-2.4.2/dist/css/AdminLTE.min.css", env('IS_HTTPS', false)) }}">

    <!-- AdminLTE Skins. Choose a skin from the css/skins folder instead of downloading all of them to reduce the load. -->
    <link rel="stylesheet" href="{{ asset("CRM/AdminLTE-2.4.2/dist/css/skins/skin-blue.min.css", env('IS_HTTPS', false)) }}">

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
        <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

    <!-- Google Font -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">

    <!-- ClubNine -->
    <link rel="stylesheet" href="{{ asset('CRM/Capital7-1.0.0/css/custom.css', env('IS_HTTPS', false)) }}">

    @yield('style')

    <style>
        @import url('https://fonts.googleapis.com/css?family=Ubuntu:100,300,400,500,600,700');

        span.logo-lg{
            font-family: "Ubuntu", sans-serif !important;
            font-weight: 100 !important;
        }
            span.logo-lg strong{
                font-weight: 500 !important;
            }

        .temp-hide{
            display: none !important;
        }

        .centered{
            text-align: center;
        }

        .right_aligned{
            text-align: right;
        }

        button[data-balloon] {
            overflow : visible;
            word-wrap: break-word;
        }

        .detail {
            position: absolute;
            z-index : 9999999;
        }

        #img-attachmen {
            border-radius: 5px;
            cursor       : pointer;
            transition   : 0.3s;
        }

        #img-attachmen:hover {
            opacity: 0.7;
        }

        .modal-content {
            margin   : auto;
            display  : block;
            width    : 80%;
            max-width: 700px;
        }

        #caption {
            margin    : auto;
            display   : block;
            width     : 80%;
            max-width : 700px;
            text-align: center;
            color     : #ccc;
            padding   : 10px 0;
            height    : 150px;
        }

        .modal-content, #caption {
            animation-name    : zoom;
            animation-duration: 0.6s;
        }

        @keyframes zoom {
            from {
                transform: scale(0);
            }

            to {
                transform: scale(1);
            }
        }

        @media only screen and (max-width: 700px) {
            .modal-content {
                width: 100%;
            }
        }
    </style>
</head>

<body class="hold-transition skin-blue sidebar-mini">

<div class="wrapper">
    @include('CRM.layouts.header')

    @include('CRM.layouts.sidebar')

    <div class="content-wrapper">
        <section class="content-header">
            <h1>
                {{ $page_title }}
                <br />
                <small>{{ $page_description }}</small>
            </h1>

            @yield('breadcrumb')
        </section>

        <section class="content">
            @yield('content')
        </section>
    </div>

    @include('CRM.layouts.footer')
</div>

<!-- jQuery 3 -->
<script src="{{ asset("CRM/AdminLTE-2.4.2/bower_components/jquery/dist/jquery.min.js", env('IS_HTTPS', false)) }}"></script>

<!-- Bootstrap 3.3.7 -->
<script src="{{ asset("CRM/AdminLTE-2.4.2/bower_components/bootstrap/dist/js/bootstrap.min.js", env('IS_HTTPS', false)) }}"></script>

<!-- AdminLTE App -->
<script src="{{ asset("CRM/AdminLTE-2.4.2/dist/js/adminlte.min.js", env('IS_HTTPS', false)) }}"></script>
@yield('scripts')

<!-- Optional: include a polyfill for ES6 Promises for IE11 and Android browser -->
<script src="https://unpkg.com/sweetalert2@7.15.0/dist/sweetalert2.all.js"></script>

<!-- Include a polyfill for ES6 Promises (optional) for IE11, UC Browser and Android browser support -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/core-js/2.4.1/core.js"></script>

{{-- SENTRY LOGGING --}}
<script src="https://browser.sentry-cdn.com/5.1.0/bundle.min.js" crossorigin="anonymous"></script>

<!-- Global Script -->
<script>
    $(function () {
        if ('{{ session()->has('swal') }}') {
            swal({
                title: '{{ session()->get('swal.title') }}',
                html : '{{ session()->get('swal.html') }}',
                type : '{{ session()->get('swal.type') }}'
            });
        }

        // Sentry.init({ dsn: '{{ env('SENTRY_JAVASCRIPT_DSN') }}' });
    });
</script>

@yield('script')

</body>
</html>