@extends('frontend.layouts.app')

@section('frontent-content')

<section class="privacy-policy-section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="text-center mb-5" data-aos="fade-up">
                    <h1 class="fw-bold mt-2">
                        {{ $page->title }}
                    </h1>
                </div>

                <div class="privacy-content">
                    <p class="lead text-center mb-4">
                        {!! $page->short_desc !!}
                    </p>

                    <div class="long-description">
                        {!! $page->long_desc !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
