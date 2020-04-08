@extends('CRM.layouts.dashboard')

@section('style')
    <style>
        canvas {
            -moz-user-select   : none;
            -webkit-user-select: none;
            -ms-user-select    : none;
        }

        .chart {
            height: 360px;
        }

        .ellipted {
            max-width    : 100px !important;
            white-space  : nowrap;
            text-overflow: ellipsis;
            overflow     : hidden;
        }

        .btn-link {
            padding: 0;
        }
    </style>
@endsection

@section('breadcrumb')
    <ol class="breadcrumb">
        <li><i class="fa fa-dashboard"></i> Home</li>
        <li class="active">Dashboard</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-4">
            <div class="info-box">
                <span class="info-box-icon bg-aqua"><i class="ion ion-ios-people-outline"></i></span>

                <div class="info-box-content">
                    <span class="info-box-text">TOTAL ACCOUNTS</span>
                    <span class="info-box-number">{{ $total_accounts }}</span> 
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="info-box">
                <span class="info-box-icon bg-red"><i class="ion ion-android-checkmark-circle"></i></span>

                <div class="info-box-content">
                    <span class="info-box-text">REGISTERED TODAY</span>
                    <span class="info-box-number">{{ $registered_today }}</span>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <!-- DateRagePicker -->
    <link rel="stylesheet" href="{{ asset("CRM/AdminLTE-2.4.2/bower_components/bootstrap-daterangepicker/daterangepicker.css") }}">
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset("CRM/AdminLTE-2.4.2/bower_components/datatables.net-bs/css/dataTables.bootstrap.min.css") }}">
@endsection

@section('scripts')
    <!-- ChartJS -->
    <script src="{{ asset("CRM/AdminLTE-2.4.2/bower_components/chart.js/dist/Chart.min.js") }}"></script>
    <script src="{{ asset("CRM/Capital7-1.0.0/js/utils.js") }}"></script>
    <!-- MomentJS -->
    <script src="{{ asset("CRM/Capital7-1.0.0/plugins/moment/moment.min.js") }}"></script>
    <!-- DateRagePicker -->
    <script src="{{ asset("CRM/AdminLTE-2.4.2/bower_components/bootstrap-daterangepicker/daterangepicker.js") }}"></script>
    <!-- DataTables -->
    <script src="{{ asset("CRM/AdminLTE-2.4.2/bower_components/datatables.net/js/jquery.dataTables.min.js") }}"></script>
    <script src="{{ asset("CRM/AdminLTE-2.4.2/bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js") }}"></script>
@endsection