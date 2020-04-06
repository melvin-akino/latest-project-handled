<div class="wallet-source text-center">
    <h3>
        <span class="label label-success">{{ $charge_description }}</span>
    </h3>

    <h3>
        <small>AMOUNT</small>
        <br>
        <code>{{ $transaction_detail_price }}</code>
    </h3>

    <h3>
        <small>VALUE</small>
        <br>
        <code>{{ $transaction_detail_values }}</code>
    </h3>

    <br>

    <p>
        <i class="fa fa-calendar-check-o"></i> {{ $created_at }}
    </p>
</div>