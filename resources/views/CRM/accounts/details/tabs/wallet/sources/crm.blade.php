<div class="wallet-source text-center">

    <h5>
        <small><i class="fa fa-sign-out"></i> PROCESS BY:</small>
        <br>
        <img src="{{ asset("CRM/Capital7-1.0.0/img/user2-160x160.jpg") }}" class="img-circle" alt="User Image">
        <br>
        <a href="mailto:{{ $sender }}">
            <i class="fa fa-envelope-o"></i> {{ $sender }}
        </a>
    </h5>

    <h3>
        <small>AMOUNT</small>
        <br>
        <code>{{ $amount }}</code>
    </h3>

    <h3>
        <small>REASON</small>
        <br>
        <code>{{ $reason }}</code>
    </h3>

    <br>

    <p>
        <i class="fa fa-calendar-check-o"></i> {{ $created_at }}
    </p>
</div>