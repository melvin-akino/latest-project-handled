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
    <title>{{ config('app.name') }} | Login</title>

    <!-- Bootstrap 3.3.7 -->
    <link rel="stylesheet" href="{{ asset("CRM/AdminLTE-2.4.2/bower_components/bootstrap/dist/css/bootstrap.min.css", env('IS_HTTPS', false)) }}">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset("CRM/AdminLTE-2.4.2/bower_components/font-awesome/css/font-awesome.min.css", env('IS_HTTPS', false)) }}">

    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset("CRM/AdminLTE-2.4.2/dist/css/AdminLTE.min.css", env('IS_HTTPS', false)) }}">

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
        <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

    <!-- Google Font -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic|Assistant:300,400,500,600,700">

    <style type="text/css">
        .logotype-label {
            vertical-align: middle;

            color         : #757575;
            font-size     : 80%;
            font-weight   : 400;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
    </style>

</head>

<body class="hold-transition login-page">
    <div class="login-box">
        <div class="login-logo">
            <img style="width: 100px;" src="{{ asset("images/icon-2.png") }}">
            <span class="logotype-label">Multiline</span>
        </div>

        <div class="login-box-body">
            <p class="login-box-msg">Sign in to start your session</p>

            @if($errors->has('session'))
                <div class="alert alert-danger" align="center"><span class="glyphicon glyphicon-exclamation-sign"></span> &nbsp; {{ $errors->first('session') }}</div>
            @endif

            <form action="{{ route('crm.login') }}" method="post" id="login-form">
                {{ csrf_field() }}

                <div class="form-group has-feedback">
                    <input type="email" name="email" class="form-control" placeholder="Email" required autofocus>

                    @if ($errors->has('email'))
                        <span class="help-block">
                            <strong>{{ $errors->first('email') }}</strong>
                        </span>
                    @endif

                    @if ($errors->has('token_error'))
                        {{ $errors->first('token_error') }}
                    @endif

                    <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
                </div>

                <div class="form-group has-feedback">
                    <input type="password" name="password" class="form-control" placeholder="Password" required>

                    @if ($errors->has('password'))
                        <span class="help-block">
                            <strong>{{ $errors->first('password') }}</strong>
                        </span>
                    @endif

                    <span class="glyphicon glyphicon-lock form-control-feedback"></span>
                </div>

                <div class="row">
                    <div class="col-xs-6 col-xs-offset-6">
                        <button type="submit" class="btn btn-primary btn-block btn-flat pull-right" data-loading-text='{{ trans('loading.sign_in') }}'>
                            Sign In
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- jQuery 3 -->
    <script src="{{ asset("CRM/AdminLTE-2.4.2/bower_components/jquery/dist/jquery.min.js", env('IS_HTTPS', false)) }}"></script>

    <!-- Bootstrap 3.3.7 -->
    <script src="{{ asset("CRM/AdminLTE-2.4.2/bower_components/bootstrap/dist/js/bootstrap.min.js", env('IS_HTTPS', false)) }}"></script>

    <!-- AdminLTE App -->
    <script src="{{ asset("CRM/AdminLTE-2.4.2/dist/js/adminlte.min.js", env('IS_HTTPS', false)) }}"></script>

    <!-- SweetAlert -->
    <script src="https://unpkg.com/sweetalert2@7.1.2/dist/sweetalert2.all.js"></script>

    <!-- Include a polyfill for ES6 Promises (optional) for IE11, UC Browser and Android browser support -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/core-js/2.4.1/core.js"></script>

    <script>
        $(function () {
            $('#login-form').submit(function () {
                $(this).find(':submit').button('loading');
            });

            if ('{{ session()->has('swal') }}') {
                swal({
                    title: '{{ session()->get('swal.title') }}',
                    html: '{{ session()->get('swal.html') }}',
                    type: '{{ session()->get('swal.type') }}'
                });
            }
        });
    </script>

</body>
</html>