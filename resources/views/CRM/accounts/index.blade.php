@extends('CRM.layouts.dashboard')

@section('styles')
    <!-- DataTables -->
    <link rel="stylesheet"
          href="{{ asset("CRM/AdminLTE-2.4.2/bower_components/datatables.net-bs/css/dataTables.bootstrap.min.css") }}">
@endsection

@section('breadcrumb')
    <ol class="breadcrumb">
        <li><a href="/admin"><i class="fa fa-dashboard"></i> Home</a></li>
        <li class="active">Accounts</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">All Accounts</h3>
                </div>
                <!-- /.box-header -->
                <div class="box-body">
                    <table id="accounts-table" class="table table-bordered table-striped">
                        <thead>
                        <tr>

                            <th>Username</th>
                            <th>First Name</th>
                            <th>Last Name</th>

                            <th>Email</th>
                            
                            <th>Registered At</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                    </table>
                </div>
                <!-- /.box-body -->
                
                <div class="box-footer">
                    <button type="button" class="btn btn-success btn-xs" data-toggle="modal" data-target="#modal-add-account" title="Add"><i
                                class="fa fa-plus"></i></button>
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
    @parent

     <script src="{{ asset('CRM/Capital7-1.0.0/js/form-validation.js') }}"></script>

    <script>
        $(function(){

            $("#modal-add-account").on("hidden.bs.modal", function () {
                var form = $('#formaddaccount');
                form.trigger('reset');
                clearErr(form);
            });

            {{--$('#formaddusers').submit(function (e) {--}}
                {{--e.preventDefault();--}}
                {{--var form = $(this);--}}
                {{--var btn = form.find(':submit').button('loading');--}}

                {{--$.ajax({--}}
                    {{--type: form.prop('method'),--}}
                    {{--url: form.prop('action'),--}}
                    {{--data: form.serialize(),--}}
                    {{--success: function (response) {--}}
                        {{--if (response.status == '{{ config('response.type.success') }}') {--}}
                            {{--location.reload();--}}
                            {{--return;--}}
                        {{--} else if (response.status == '{{ config('response.type.error') }}') {--}}
                            {{--assocErr(response.errors, form);--}}
                        {{--}--}}
                    {{--}--}}
                {{--}).done(function () {--}}
                    {{--btn.button('reset');--}}
                {{--});--}}
            {{--});--}}


            $('#accounts-table').DataTable({
                "processing": true,
                "serverSide": true,
                "stateSave": true,
                "responsive": true,
                "ajax": {
                    "url": "/admin/accounts/datatable",
                    "error": function($err){
                        if($err.status == 401){
                            alert('Your session is expired! Please login again.');
                            window.location.href = '/admin/login';
                        }
                    }
                },
                columnDefs: [{
                    targets: 2,
                    render: $.fn.dataTable.render.ellipsis(16, true)
                }, {
                    targets: 4,
                    render: $.fn.dataTable.render.ellipsis(16, true)
                },  {
                    targets: 5,
                    orderable: false
                }],
                "columns": [
                    {
                        "data": "name",
                        "render": function (data, type, row, meta) {
                            if (type === 'display') {
                                data = !row.name ? 'N/A' : row.name;
                            }
                            return data;
                        }
                    },

                    {
                        "data": "firstnamne",
                        "render": function (data, type, row, meta) {
                            if (type === 'display') {
                                data = !row.firstname ? 'N/A' : row.firstname;
                            }
                            return data;
                        }
                    },
                    {
                        "data": "lastname",
                        "render": function (data, type, row, meta) {
                            if (type === 'display') {
                                data = !row.lastname ? 'N/A' : row.lastname;
                            }
                            return data;
                        }
                    },


                    {
                        "data": "email",
                        "render": function (data, type, row, meta) {

                            if (type === 'display') {
                                data = !row.email ? 'N/A' : row.email;
                            }
                            return data;
                        }
                    },
                
                    {"data": "created_at"},
                    {
                        "data": null,
                        "render": function (data, type, row, meta) {
                            if (type === 'display') {
                                data = '<a href="/admin/accounts/' + row.id + '#personal"><i class="fa fa-user-o"></i> Details</a>';
                            }
                            return data;
                        }
                    }
                ]
            });

            /**
             * Generates a random password
             *
             * @param numLc Number of lowercase letters to be used (default 4)
             * @param numUc Number of uppercase letters to be used (default 4)
             * @param numDigits Number of digits to be used (default 4)
             * @param numSpecial Number of special characters to be used (default 2)
             * @returns {*|string|String}
             */
            var generatePassword = function(numLc, numUc, numDigits, numSpecial) {
                numLc = numLc || 4;
                numUc = numUc || 4;
                numDigits = numDigits || 4;
                numSpecial = numSpecial || 2;


                var lcLetters = 'abcdefghijklmnopqrstuvwxyz';
                var ucLetters = lcLetters.toUpperCase();
                var numbers = '0123456789';
                var special = '&@!#+';

                var getRand = function(values) {
                    return values.charAt(Math.floor(Math.random() * values.length));
                }

                //+ Jonas Raoni Soares Silva
                //@ http://jsfromhell.com/array/shuffle [v1.0]
                function shuffle(o){ //v1.0
                    for(var j, x, i = o.length; i; j = Math.floor(Math.random() * i), x = o[--i], o[i] = o[j], o[j] = x);
                    return o;
                };

                var pass = [];
                for(var i = 0; i < numLc; ++i) { pass.push(getRand(lcLetters)) }
                for(var i = 0; i < numUc; ++i) { pass.push(getRand(ucLetters)) }
                for(var i = 0; i < numDigits; ++i) { pass.push(getRand(numbers)) }
                for(var i = 0; i < numSpecial; ++i) { pass.push(getRand(special)) }

                return shuffle(pass).join('');
            }


            $('#generate_pass').click(function(){

                var randPassword = generatePassword(3,3,1,1);

                $('#add-password-input').val(randPassword);
            });



        });
    </script>
@endsection
