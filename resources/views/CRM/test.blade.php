@extends('CRM.layouts.dashboard')

@section('styles')
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset("CRM/AdminLTE-2.4.2/bower_components/datatables.net-bs/css/dataTables.bootstrap.min.css", env('IS_HTTPS', false)) }}">
@endsection

@section('breadcrumb')
@endsection

@section('content')
    <form id="test-swt">
        {{ csrf_field() }}
        <input type="text" name="swtable" value="sports">

        <button type="button" id="submit">Test</button>
    </form>

    <table id="accounts-table" class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Key</th>
                <th>Value</th>
            </tr>
        </thead>
    </table>
@endsection

@section('scripts')
    <!-- DataTables -->
    <script src="{{ asset("CRM/AdminLTE-2.4.2/bower_components/datatables.net/js/jquery.dataTables.min.js", env('IS_HTTPS', false)) }}"></script>
    <script src="{{ asset("CRM/AdminLTE-2.4.2/bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js", env('IS_HTTPS', false)) }}"></script>
    <script src="https://cdn.datatables.net/plug-ins/1.10.19/dataRender/ellipsis.js"></script>

    <script type="text/javascript">
        $(function () {

            $('#submit').on('click', function () {
                let data = $('#test-swt').serialize();
                let table = $('#accounts-table').DataTable();

                table.destroy();

                $.post("{{ route('crm.swt.check') }}", data, function (response) {
                    $('#accounts-table').DataTable({
                        data: response,
                        columnDefs: [
                        ],
                        columns: [
                            {
                                data: "key",
                                render: function (data, type, row, meta) {
                                    return `<code>${ data }</code>`;
                                }
                            },
                            {
                                data: "data",
                                render: function (data, type, row, meta) {
                                    if (type === 'display') {
                                        const object = data;

                                        data = `<table>`;
                                        for (const property in object) {
                                            data += `<tr> <td width="1%"><code>${ property }</code></td> <td>${ object[property] }</td> </tr>`;
                                        }
                                        data += `</table>`;
                                    }

                                    return data;
                                }
                            }
                        ]
                    });
                });
            });
        });
    </script>
@endsection