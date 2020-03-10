@extends('CRM.layouts.dashboard')

@section('styles')
    <!-- DateRagePicker -->
    <link rel="stylesheet" href="{{ asset("CRM/AdminLTE-2.4.2/bower_components/bootstrap-daterangepicker/daterangepicker.css") }}">
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset("CRM/AdminLTE-2.4.2/bower_components/datatables.net-bs/css/dataTables.bootstrap.min.css") }}">
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset("CRM/AdminLTE-2.4.2/bower_components/select2/dist/css/select2.min.css") }}">
    <!-- Toastr -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
@endsection

@section('style')
    <style>
        .action-button{
            padding-right: 10px;
        }

        .editor-div, .edit-buttons {
            display:none;
        }
    </style>
@endsection

@section('breadcrumb')
    <ol class="breadcrumb">
        <li><i class="fa fa-dashboard"></i> Home</li>
        <li>Masterlist</li>
        <li class="active">{{ $page_title }}</li>
    </ol>
@endsection

@section('content')
    <ul class="nav nav-tabs game-types">
        <li class="active"><a data-toggle="tab" href="#...">Soccer</a></li>
    </ul>

    <div class="tab-content content-game-types">
        <div id="game_schedule" class="tab-pane fade in active">
            <ul class="nav nav-pills game-schedules">
                <li class=""><a data-toggle="tab" href="#IN-PLAY">IN-PLAY <span class="badge pull-right">0</span></a></li>
                <li class=""><a data-toggle="tab" href="#TODAY">TODAY <span class="badge pull-right">0</span></a></li>
                <li class="active"><a data-toggle="tab" href="#EARLY">EARLY <span class="badge pull-right">99</span></a></li>
            </ul>
        </div>
    </div>
@endsection

@section('scripts')
    <!-- MomentJS -->
    <script src="{{ asset("CRM/Capital7-1.0.0/plugins/moment/moment.min.js") }}"></script>
    <!-- DataTables -->
    <script src="{{ asset("CRM/AdminLTE-2.4.2/bower_components/datatables.net/js/jquery.dataTables.min.js") }}"></script>
    <script src="{{ asset("CRM/AdminLTE-2.4.2/bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js") }}"></script>
    <!-- ClubNine -->
    <script src="{{ asset("CRM/Capital7-1.0.0/js/datatables-ellipsis.js") }}"></script>
    <!-- Toaster -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <script type="text/javascript">
        $(function () {
            toastr.options = {
                "closeButton"      : true,
                "debug"            : false,
                "newestOnTop"      : false,
                "progressBar"      : true,
                "positionClass"    : "toast-bottom-right",
                "preventDuplicates": true,
                "onclick"          : null,
                "showDuration"     : "300",
                "hideDuration"     : "1000",
                "timeOut"          : "5000",
                "extendedTimeOut"  : "1000",
                "showEasing"       : "swing",
                "hideEasing"       : "linear",
                "showMethod"       : "fadeIn",
                "hideMethod"       : "fadeOut"
            }

            $('#data-table').DataTable({
                "processing": true,
                "serverSide": true,
                "stateSave" : true,
                "responsive": true,
                "searching" : true,
                "ajax"      : {
                    "url": "{{ route('crm.masterlist.batch_matching.data_tables') }}",
                    "error": function($err){
                        if($err.status == 401){
                            alert('Your session is expired! Please login again.');
                            window.location.href = '/admin/login';
                        }
                    }
                },
                "columnDefs": [
                    {
                        'targets': [0, 3],
                        'visible': false,
                    }
                ],
                "columns": [
                    {
                        "data"  : null,
                        "render": function (data, type, row, meta) {
                            let dom = `<div class="action-buttons">
                                    <a href="#" class="action-button action-edit" data-rid="${ row.id }" >
                                        <i class="fa fa-edit"></i> Edit
                                    </a>
                                    <a href="#" class="action-button action-delete" data-rid="${ row.id }">
                                        <i class="fa fa-trash"></i> Delete
                                    </a>
                                    <a href="#" class="action-button action-copy hidden" data-rid="${ row.id }">
                                        <i class="fa fa-copy"></i> Copy Base
                                    </a>
                                </div>
                                <div class="edit-buttons">
                                    <a href="#" class="action-button action-cancel" data-rid="${ row.id }">
                                        <i class="fa fa-times"></i> Cancel
                                    </a>
                                    <a href="#" class="action-button action-save" data-rid="${ row.id }">
                                        <i class="fa fa-copy"></i> Save
                                    </a>
                                </div>`;

                            return dom;
                        }
                    }
                ],
            });
        });
    </script>
@endsection