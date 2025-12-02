@extends('layouts.base')

@section('bodyClass', 'bg-after-login')

@section('header')
@include('components.header-user')
@endsection

@section('content')
@yield('content')
@endsection
