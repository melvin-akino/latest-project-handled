@extends('CRM.layouts.dashboard')

@section('styles')
    <!-- DataTables -->
    <link rel="stylesheet"
          href="{{ asset("CRM/AdminLTE-2.4.2/bower_components/datatables.net-bs/css/dataTables.bootstrap.min.css") }}">
@endsection

@section('breadcrumb')
    <ol class="breadcrumb">
        <li><a href="/admin"><i class="fa fa-dashboard"></i> Home</a></li>
        <li class="active">Minmax Monitoring</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Minmax </h3>
                </div>
                <!-- /.box-header -->
                <div class="box-body">
                    <table  id='minmax-data' class="table table-bordered table-striped" style="table-layout:fixed; width: 100%;">
                        <thead>
                        <tr>

                            <th style="width:10%;word-wrap: break-word;">Market Id</th>
                            <th  style="width:45%;word-wrap: break-word;">Latest</th>
                            <th  style="width:45%;word-wrap: break-word;">Previous</th>
                            
                        </tr>
                        </thead>
                        @foreach($minmaxs as $minmax)
                        <tr>
                            @php

                            $latest = json_decode($minmax['latest']);
                            $previous = json_decode($minmax['previous']);

                            @endphp
                            <Td style="width:10%;word-wrap: break-word;">  {{ $latest->data->market_id}} </Td>
                            <td style="width:45%;word-wrap: break-word;"> 
                                   <pre>{{  json_encode($latest,JSON_PRETTY_PRINT) }} </pre>
                            </td>
                            <Td style="width:45%;word-wrap: break-word;">  
                                <pre>{{  json_encode($previous,JSON_PRETTY_PRINT) }} </pre>
                            </Td>
                        </tr>
                        @endforeach  
                    </table>
                </div>
                <!-- /.box-body -->
                
                <div class="box-footer">
                    
                </div><!-- /.box-footer-->
                
            </div>
            <!-- /.box -->
        </div>
        <!-- /.col -->
    </div>
    <!-- /.row -->

@endsection

@section('scripts')
    <!-- DataTables -->
    <script src="{{ asset("CRM/AdminLTE-2.4.2/bower_components/datatables.net/js/jquery.dataTables.min.js") }}"></script>
    <script src="{{ asset("CRM/AdminLTE-2.4.2/bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js") }}"></script>
    <script src="https://cdn.datatables.net/plug-ins/1.10.19/dataRender/ellipsis.js"></script>
@endsection
@section('script')


   <script type="text/javascript" >
        $(document).ready(function() {
            $('#minmax-data').DataTable();            
        });
    </script>
 
@endsection

