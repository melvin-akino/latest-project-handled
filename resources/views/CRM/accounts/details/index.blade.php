@extends('CRM.layouts.dashboard')

@section('style')
    @parent

    <style>
        .nav-tabs {
            margin: 0 20px;
        }

        .tab-content {
            margin: 20px;
        }

        #subcription-inclusions-table {
            margin-top: 20px;
        }
    </style>
@endsection

@section('breadcrumb')
    <ol class="breadcrumb">
        <li><a href="/admin"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="/admin/accounts">Accounts</a></li>
        <li class="active">Details</li>
    </ol>
@endsection

@section('content')
    <div class="box">
        <div class="box-header">
            <h3 class="box-title"><strong>User:</strong> {{ $account->name }}</h3>
        </div>
        <div class="box-body">
            <ul class="nav nav-tabs">
                <li class=""><a data-toggle="tab" href="#personal">Personal</a></li>
                <li class="temp-hide"><a data-toggle="tab" href="#user-activity">User Activity</a></li>
                <li class="temp-hide"><a data-toggle="tab" href="#bank-info">Bank Information</a></li>
               
                <li><a data-toggle="tab" href="#wallet">Wallet</a></li>
              
                <li class="pull-right"><a href="{{ route('accounts.index') }}"><i class="fa fa-long-arrow-left"></i> Back to Accounts</a></li>
            </ul>
            <div class="tab-content">
                @include('CRM.accounts.details.tabs.personal')
                @include('CRM.accounts.details.tabs.wallet.index')
               

            </div>
        </div>
    </div>
@endsection

@section('styles')
    @parent

    <!-- DataTables -->
    <link rel="stylesheet"
          href="{{ asset("CRM/AdminLTE-2.4.2/bower_components/datatables.net-bs/css/dataTables.bootstrap.min.css") }}">

    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset("CRM/AdminLTE-2.4.2/bower_components/select2/dist/css/select2.min.css") }}">
@endsection

@section('scripts')
    @parent

    <!-- DataTables -->
    <script src="{{ asset("CRM/AdminLTE-2.4.2/bower_components/datatables.net/js/jquery.dataTables.min.js") }}"></script>
    <script src="{{ asset("CRM/AdminLTE-2.4.2/bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js") }}"></script>

    <!-- Select2 -->
    <script src="{{ asset("CRM/AdminLTE-2.4.2/bower_components/select2/dist/js/select2.full.min.js") }}"></script>
@endsection

@section('script')
    @parent

    <!-- ClubNine -->
    <script src="{{ asset('CRM/Capital7-1.0.0/js/countries.js') }}"></script>

    <script>
        $(function () {
            var hash = window.location.hash;
            hash && $('ul.nav a[href="' + hash + '"]').tab('show');
        });
    </script>
@endsection