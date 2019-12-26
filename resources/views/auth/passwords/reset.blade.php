@extends('layouts.auth')

@section('content')
    <reset-password email="{{$email}}" token="{{$token}}"></reset-password>
@endsection
