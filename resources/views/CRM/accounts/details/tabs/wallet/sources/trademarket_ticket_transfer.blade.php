<div class="wallet-source text-center">
	<div class="row">
		<div class="col-md-6">
			<h5>
				<small><i class="fa fa-sign-out"></i> SENDER</small>
				<br />
				<img src="{{ asset("CRM/Capital7-1.0.0/img/user2-160x160.jpg") }}" class="img-circle" alt="User Image">
				<br />
				<a href="mailto:{{ $sender }}"><i class="fa fa-envelope-o"></i> {{ $sender }}</a>
			</h5>
		</div>

		<div class="col-md-6">
			<h5>
				<small><i class="fa fa-sign-in"></i> RECEIVER</small>
				<br />
				<img src="{{ asset("CRM/Capital7-1.0.0/img/user2-160x160.jpg") }}" class="img-circle" alt="User Image">
				<br />
				<a href="mailto:{{ $receiver }}"><i class="fa fa-envelope-o"></i> {{ $receiver }}</a>
			</h5>
		</div>
	</div>

	<h3>
		<small>TICKET#</small>
		<br />
		<code>{{ $ticket }}</code>
	</h3>

	<h3>
		<small>AMOUNT</small>
		<br />
		<code>{{ $amount }}</code>
	</h3>

	<h3>
		<small>NOTE</small>
		<br />
		<code>{{ $note }}</code>
	</h3>

	<br />

	<p>
		<i class="fa fa-calendar-check-o"></i> {{ $created_at }}
	</p>
</div>