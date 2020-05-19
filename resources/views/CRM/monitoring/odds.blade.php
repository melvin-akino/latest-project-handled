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
        <li class="active">Odds Monitoring</li>
    </ol>
@endsection
@section('content')
    <div class="row">
        <table class="table table-striped" id="OddsTable">
        <thead>
            <tr>
                <th class="text-left">League</th>
                <th class="text-left">Home Team</th>
                <th class="text-left">Away Team</th>
                <th class="text-left">Schedule</th>
                <th class="text-left">Latest</th>
                <th class="text-left">Previous</th>
            </tr>
        </thead>
        <tbody>
            @foreach($odds as $o)
            @php
                $latest = json_encode($o['latest'],JSON_PRETTY_PRINT);
                $previous = json_encode($o['previous'],JSON_PRETTY_PRINT);
            @endphp 
            <tr>
                <td>{{ $o['league'] }}</td>
                <td>{{ $o['home'] }}</td>
                <td>{{ $o['away'] }}</td>
                <td>{{ $o['schedule'] }}</td>
                <td>
                    <span class="more-text">toggle...</span>
                    <span class="full-view"><pre>{{ $latest }}</pre></span>                                        
                </td>
                <td>
                    <span class="more-text">toggle...</span>
                    <span class="full-view"><pre>{{ $previous }}</pre></span>                                        
                </td>
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
            $('#OddsTable').DataTable();
            
            $('#OddsTable tbody').on('click', 'span.more-text', function () {
                $(this).siblings('.full-view').toggle();
            });            
        });
    </script>
@endsection