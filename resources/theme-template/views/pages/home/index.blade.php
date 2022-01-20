@extends('theme::layouts.main.layout')

@section('content')
    {{-- Hero --}}
    @include('theme::components.common.hero')

    @include('theme::components.landing.features')

    @include('theme::components.landing.about')

    @include('theme::components.landing.pricing')

    @include('theme::components.landing.faq')

    @include('theme::components.landing.testimonials')

    @include('theme::components.landing.team')

    @include('theme::components.landing.contact')

@endsection
