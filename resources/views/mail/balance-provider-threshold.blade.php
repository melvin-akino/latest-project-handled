@extends('layouts.mail')

@section('content')
    <div class="flex w-full py-6 px-10 mt-6 items-center justify-center bg-orange">
        &nbsp;

        <div class="absolute shadow-md rounded-lg py-2 px-4 bg-white inset-auto mt-8 items-center justify-center">
            <img class="object-center" width="75" src="{{ asset('images/icon.png') }}">
        </div>
    </div>

    <div class="w-full pt-8 pb-2 px-10">
       Hi, This Provider accounts is already in threshold
    </div>

  <div class="flex w-full pt-2 pb-4 px-10 items-center justify-center">
        <table>
            <tr>
                <td>Provider</td>
                <td>{{ $provider }}</td>
            </tr>
            <tr>
                <td>Username</td>
                <td>{{ $username }}</td>
            </tr>
            <tr>
                <td>Available Balance</td>
                <td>{{ $currency}} {{ $balance }}</td>
            </tr>
            <tr>
                <td>Threshold</td>
                <td> {{ $threshold }}</td>
            </tr>
        </table>
    </div>

     <div class="w-full pt-8 pb-2 px-10">
       Visit the Provider Account and fill up the wallet or deactivate this account to Admin
    </div>

    <div class="w-full py-8 px-10 text-center">
        <span class="text-sm">{!! trans('mail.footer-rights') !!}</span>
    </div>
@endsection