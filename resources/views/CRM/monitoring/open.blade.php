@extends('CRM.layouts.dashboard')
@section('style')
    <style>
        .full-view{
            display:none;
        }

        .more-text{
            background:lightblue;
            color:navy;
            font-size:13px;
            padding:3px;
            cursor:pointer;
        }
    </style>
@endsection
@section('breadcrumb')
    <ol class="breadcrumb">
        <li><i class="fa fa-dashboard"></i> Home</li>
        <li class="active">Open Orders Monitoring</li>
    </ol>
@endsection
@section('content')
    <div class="row">
        <table class="table table-striped" id="OpenOrdersTable">
        <thead>
            <tr>
                <th class="text-left">Content</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orders as $o)
            <tr>
                <td>{{ $o }}</td>
            </tr>
            @endforeach
        </tbody>
        </table>
            }
    </div>
@endsection

@section('styles')
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset("CRM/AdminLTE-2.4.2/bower_components/datatables.net-bs/css/dataTables.bootstrap.min.css") }}">
@endsection

@section('scripts')

    <!-- DataTables -->  
    <script src="{{ asset("CRM/AdminLTE-2.4.2/bower_components/datatables.net/js/jquery.dataTables.min.js") }}"></script>
    <script src="{{ asset("CRM/AdminLTE-2.4.2/bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js") }}"></script>
    <script type="text/javascript" >
        $(document).ready(function() {
            $('#OpenOrdersTable').DataTable();          
        });
    </script>
@endsection