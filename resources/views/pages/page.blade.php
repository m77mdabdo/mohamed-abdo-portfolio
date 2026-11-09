@extends('layouts.site')

@section('content')
    @foreach($sections as $section)
        @include($section['view'], $section['data'])
    @endforeach
@endsection
