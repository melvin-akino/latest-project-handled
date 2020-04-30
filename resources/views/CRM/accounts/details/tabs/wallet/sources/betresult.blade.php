<div class="wallet-source text-center">
    <h2>
        <small>Status</small>
        <br>
        <span class="label label-success">
            {{ $bet_status  }}
        </span>
    </h2>
    
    <h3>
        <small>Stake</small>
        <br>
        <code>{{ $amount }}</code>
    </h3>

    <h3>
        <small>Amount</small>
        <br>
        <code>{{ $protif_loss }}</code>
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