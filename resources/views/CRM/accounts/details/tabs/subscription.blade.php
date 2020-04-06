<div id="subscription" class="tab-pane fade">
    @if($account_membership)
        <div class="row">
            <div class="col-md-3">
                <strong>ID:</strong> {{ $account_membership->membership_id }}<br>
                <strong>Name:</strong> {{ $account_membership->name }}
            </div>
            <div class="col-md-3">
                <strong>Status:</strong> {{ $account_membership->status->status_name }}<br>
                <strong>Sign up Date:</strong> {{ $account_membership->created_at }}
            </div>
        </div>
        <strong>Inclusions:</strong>
        <table id="subcription-inclusions-table" class="table table-bordered table-striped">
            <thead>
            <tr>
                <th>Name</th>
                <th>Description</th>
                <th>Value</th>
            </tr>
            </thead>

            @foreach($account_membership->membership_inclusions as $inclusion)
                <tr>
                    <td>{{ $inclusion->name }}</td>
                    <td>{{ $inclusion->description }}</td>
                    <td>{{ $inclusion->value }}</td>
                </tr>
            @endforeach
        </table>
    @else
        <p class="text-muted">{{ trans('validation.custom.subscription.empty', ['name' => $account->getDisplayName()]) }}</p>
    @endif
</div>