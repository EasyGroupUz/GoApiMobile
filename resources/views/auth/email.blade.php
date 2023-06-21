@extends('layout.app')

@section('content')
    <a href="{{route('password.reset', $token)}}">
        Reset Password
    </a>
@endsection