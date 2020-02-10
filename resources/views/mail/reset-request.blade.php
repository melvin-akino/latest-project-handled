@extends('layouts.mail')

@section('content')
    <div class="flex w-full py-6 px-10 mt-6 items-center justify-center bg-orange">
        &nbsp;

        <div class="absolute shadow-md rounded-lg py-2 px-4 bg-white inset-auto mt-8 items-center justify-center">
            <img class="object-center" width="75" src="{{ asset('images/icon.png') }}">
        </div>
    </div>

    <div class="w-full pt-8 pb-2 px-10">
        Hi <strong>{{ $name }}</strong>,
    </div>

    <div class="flex w-full py-4 px-10">
        {!! trans('mail.password.request.body') !!}
    </div>

    <div class="flex w-full pt-2 pb-4 px-10 text-center items-center justify-center">
        <a href="{{ $url }}" class="btn btn-primary btn-lg">{{ trans('mail.password.request.reset-button') }}</a>
    </div>

    <div class="flex w-full py-4 px-10">
        {{ trans('mail.password.request.footer') }}
    </div>

    <div class="w-full py-8 px-10">
        <span class="text-sm">{!! trans('mail.remarks') !!}</span>
    </div>

    <div class="w-full py-8 px-10 text-center">
        <span class="text-sm">{!! trans('mail.footer-rights') !!}</span>
    </div>
@endsection