<div class="wallet-source text-center">
    <h2>
        <small>Reason</small>
        <br>
        <span class="label label-success">
            {{ $bet_action }}
        </span>
    </h2>
    <h3>
        <small>Status</small>
        <br>
        <code>{{ $bet_status }}</code>
    </h3>

    <h3>
        <small>Amount</small>
        <br>
        <code>{{ $amount }}</code>
    </h3>
    <h3>
        <small>Information</small>
        <br>
        <code>{!! $game_info !!}</code>
    </h3>

    <br>

    <p>
        <i class="fa fa-calendar-check-o"></i> {{ $created_at }}
    </p>
</div>