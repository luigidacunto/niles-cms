@extends('layouts.public')

@section('title', $policy->titolo.' — '.config('app.public_name'))

@section('content')
<div class="max-w-2xl mx-auto px-4 py-10">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">{{ $policy->titolo }}</h1>
    <div class="prose max-w-none text-gray-700">{!! $policy->testoRisolto() !!}</div>
</div>
@stop
