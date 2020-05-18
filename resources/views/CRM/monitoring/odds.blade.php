@extends('CRM.layouts.dashboard')
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
            <tr>
                <td>{{ $o['league'] }}</td>
                <td>{{ $o['home'] }}</td>
                <td>{{ $o['away'] }}</td>
                <td>{{ $o['schedule'] }}</td>
                <td><pre>{{ json_encode($o['latest'],JSON_PRETTY_PRINT) }}</pre></td>
                <td><pre>{{ json_encode($o['previous'],JSON_PRETTY_PRINT) }}</pre></td>
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
        });
    </script>
@endsection