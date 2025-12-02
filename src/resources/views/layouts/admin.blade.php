@extends('layouts.base')

@section('bodyClass', 'bg-after-login')

@section('header')
@include('components.header-admin')
@endsection

@section('content')
@yield('content')
@endsection
