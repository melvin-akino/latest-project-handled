<div id="bank-info" class="tab-pane fade">
    <table id="bank-info-table" class="table table-bordered table-striped">
        <thead>
        <tr>
            <th>Currency</th>
            <th>Name</th>
            <th>Code</th>
            <th>Country</th>
            <th>Branch</th>
            <th>Address</th>
            <th>Address 2</th>
            <th>Account Name</th>
            <th>Account #</th>
            <th>Swift Code</th>
            <th>Default?</th>
            <th>Created At</th>
        </tr>
        </thead>
        <tbody>
        @foreach($banks as $bank)
            <tr>
                <td>{{ is_null($bank->currency) ? "N/A" : $bank->currency->currency }}</td>
                <td>{{ empty($bank->bank_name) ? "N/A" : $bank->bank_name }}</td>
                <td>{{ empty($bank->bank_code) ? "N/A" : $bank->bank_code }}</td>
                <td>{{ empty($bank->bank_country) ? "N/A" : $bank->bank_country }}</td>
                <td>{{ empty($bank->bank_branch) ? "N/A" : $bank->bank_branch }}</td>
                <td>{{ empty($bank->bank_address) ? "N/A" : $bank->bank_address }}</td>
                <td>{{ empty($bank->bank_address_2) ? "N/A" : $bank->bank_address_2 }}</td>
                <td>{{ empty($bank->bank_account_name) ? "N/A" : $bank->bank_account_name }}</td>
                <td>{{ empty($bank->bank_account_number) ? "N/A" : $bank->bank_account_number }}</td>
                <td>{{ empty($bank->bank_swift) ? "N/A" : $bank->bank_swift }}</td>
                <td>{{ $bank->is_default ? 'Yes' : 'No' }}</td>
                <td>{{ $bank->created_at }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

@section('script')
    @parent

    <script>
        $(function () {
            $('#bank-info-table').DataTable({
                "stateSave": true,
                "responsive": true,
            });
        });
    </script>
@endsection