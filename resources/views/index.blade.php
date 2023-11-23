@extends('layout.layout')

@section('title')
    {{-- Your page title --}}
@endsection
@section('content')

    <div class="container-fluid">
    </div>

<script>

    var conn = new WebSocket('ws://185.196.213.73:8090/');
    conn.onopen = function(e) {
        console.log("Connection established!");
    };
    conn.onmessage = function(e) {
        console.log(e.data);
    };

</script>
@endsection