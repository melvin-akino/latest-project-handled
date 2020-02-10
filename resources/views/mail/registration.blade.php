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
        <br /><br />
        {!! trans('mail.registration.intro') !!}
        <br />
        {{ trans('mail.registration.header') }}
    </div>

    <div class="flex w-full pt-2 pb-4 px-10">
        @foreach ($sections AS $section)
            <div class="w-1/5 overflow-hidden p-4">
                <img class="w-full" src="https://via.placeholder.com/150" alt="{{ trans('mail.registration.' . $section . '.title') }}">
                <div class="py-4">
                    <div class="font-bold text-lg mb-2 text-center text-orange">
                        {{ trans('mail.registration.' . $section . '.title') }}
                    </div>

                    <p class="text-gray text-sm text-center">
                        {{ trans('mail.registration.' . $section . '.content') }}
                    </p>
                </div>
            </div>
        @endforeach
    </div>

    <div class="flex w-full py-4 px-10">
        {{ trans('mail.registration.footer') }}
    </div>

    <div class="w-full py-8 px-10 text-center">
        <span class="text-sm">{{ trans('mail.footer-rights') }}</span>
    </div>
@endsection