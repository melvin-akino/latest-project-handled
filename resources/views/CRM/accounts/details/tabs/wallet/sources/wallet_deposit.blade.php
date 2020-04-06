<div class="wallet-source text-center">
    <h2>
        <small>ACCOUNT NUMBER</small>
        <br>
        <span class="label label-success">
            {{ $wallet_account_number }}
        </span>
    </h2>

    <h3>
        <small>AMOUNT</small>
        <br>
        <code>{{ $amount }}</code>
    </h3>

    <br>

    <p>
        <i class="fa fa-calendar-check-o"></i> {{ $created_at }}
    </p>
</div>