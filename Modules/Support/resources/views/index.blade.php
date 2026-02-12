@extends('support::layouts.master')

@section('content')
    <h1>Hello World</h1>

    <p>Module: {!! config('support.name') !!}</p>
@endsection
