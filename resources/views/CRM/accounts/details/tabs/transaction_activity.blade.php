<div id="transaction-activity" class="tab-pane fade">
    @if($account->getTransactionActivity()->count())
        <table id="transaction-activity-table" class="table table-bordered table-striped">
            <thead>
            <tr>
                <th>ID</th>
                <th>Date/Time</th>
                <th>Type</th>
                <th>Description</th>
                <th>Price</th>
                <th>Status</th>
            </tr>
            </thead>
        </table>
    @else
        <p class="text-muted">{{ trans('validation.custom.transaction_activity.empty', ['name' => $account->getDisplayName()]) }}</p>
    @endif
</div>

@section('script')
    @parent

    <script>
        $(function () {
            $('#transaction-activity-table').DataTable({
                "processing": true,
                "serverSide": true,
                "stateSave": true,
                "responsive": true,
                "ajax": "{{ route('transaction.activityDataTable', $account) }}",
                "columns": [
                    {"data": "transaction_log_id"},
                    {"data": "transaction_log_created_at"},
                    {"data": "charge_type_name"},
                    {"data": "charge_description"},
                    {"data": "charge_price"},
                    {"data": "transaction_status_description"}
                ]
            });
        });
    </script>
@endsection