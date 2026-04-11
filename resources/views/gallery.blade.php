<?php $page = 'gallery'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- ========================
        Start Page Content
    ========================= -->
        
    <div class="page-wrapper">

        <!-- Start Content -->
        <div class="content">

            <!-- Page Header -->
            <div class="pb-3 mb-3 border-bottom">
                <h4 class="fw-bold mb-0">Gallery</h4>
            </div>
            <!-- End Page Header -->

            <div class="card">
                <div class="card-body">

                    <!-- start row -->
                    <div class="row row-gap-3 mb-3">
                        <div class="col-md-6 col-xl">
                            <a href="{{URL::asset('build/img/gallery/gallery-img-01.jpg')}}" class="image-popup">
                                <img src="{{URL::asset('build/img/gallery/gallery-01.jpg')}}" alt="img" class="img-fluid">
                            </a>
                        </div><!-- end col -->
                        <div class="col-md-6 col-xl">
                            <a href="{{URL::asset('build/img/gallery/gallery-img-02.jpg')}}" class="image-popup">
                                <img src="{{URL::asset('build/img/gallery/gallery-02.jpg')}}" alt="img" class="img-fluid">
                            </a>
                        </div><!-- end col -->
                        <div class="col-md-6 col-xl">
                            <a href="{{URL::asset('build/img/gallery/gallery-img-03.jpg')}}" class="image-popup">
                                <img src="{{URL::asset('build/img/gallery/gallery-03.jpg')}}" alt="img" class="img-fluid">
                            </a>
                        </div><!-- end col -->
                        <div class="col-md-6 col-xl">
                            <a href="{{URL::asset('build/img/gallery/gallery-img-04.jpg')}}" class="image-popup">
                                <img src="{{URL::asset('build/img/gallery/gallery-04.jpg')}}" alt="img" class="img-fluid">
                            </a>
                        </div><!-- end col -->
                        <div class="col-md-6 col-xl">
                            <a href="{{URL::asset('build/img/gallery/gallery-img-05.jpg')}}" class="image-popup">
                                <img src="{{URL::asset('build/img/gallery/gallery-05.jpg')}}" alt="img" class="img-fluid">
                            </a>
                        </div><!-- end col -->
                    </div>
                    <!-- end row -->

                    <!-- start row -->
                        <div class="row row-gap-3 mb-3">
                        <div class="col-md-6 col-xl">
                            <a href="{{URL::asset('build/img/gallery/gallery-img-06.jpg')}}" class="image-popup">
                                <img src="{{URL::asset('build/img/gallery/gallery-06.jpg')}}" alt="img" class="img-fluid">
                            </a>
                        </div><!-- end col -->
                        <div class="col-md-6 col-xl">
                            <a href="{{URL::asset('build/img/gallery/gallery-img-07.jpg')}}" class="image-popup">
                                <img src="{{URL::asset('build/img/gallery/gallery-07.jpg')}}" alt="img" class="img-fluid">
                            </a>
                        </div><!-- end col -->
                        <div class="col-md-6 col-xl">
                            <a href="{{URL::asset('build/img/gallery/gallery-img-08.jpg')}}" class="image-popup">
                                <img src="{{URL::asset('build/img/gallery/gallery-08.jpg')}}" alt="img" class="img-fluid">
                            </a>
                        </div><!-- end col -->
                        <div class="col-md-6 col-xl">
                            <a href="{{URL::asset('build/img/gallery/gallery-img-09.jpg')}}" class="image-popup">
                                <img src="{{URL::asset('build/img/gallery/gallery-09.jpg')}}" alt="img" class="img-fluid">
                            </a>
                        </div><!-- end col -->
                        <div class="col-md-6 col-xl">
                            <a href="{{URL::asset('build/img/gallery/gallery-img-10.jpg')}}" class="image-popup">
                                <img src="{{URL::asset('build/img/gallery/gallery-10.jpg')}}" alt="img" class="img-fluid">
                            </a>
                        </div><!-- end col -->
                    </div>
                    <!-- end row -->

                    <!-- start row -->
                    <div class="row row-gap-3">
                        <div class="col-md-6 col-xl">
                            <a href="{{URL::asset('build/img/gallery/gallery-img-11.jpg')}}" class="image-popup">
                                <img src="{{URL::asset('build/img/gallery/gallery-11.jpg')}}" alt="img" class="img-fluid">
                            </a>
                        </div><!-- end col -->
                        <div class="col-md-6 col-xl">
                            <a href="{{URL::asset('build/img/gallery/gallery-img-12.jpg')}}" class="image-popup">
                                <img src="{{URL::asset('build/img/gallery/gallery-12.jpg')}}" alt="img" class="img-fluid">
                            </a>
                        </div><!-- end col -->
                        <div class="col-md-6 col-xl">
                            <a href="{{URL::asset('build/img/gallery/gallery-img-13.jpg')}}" class="image-popup">
                                <img src="{{URL::asset('build/img/gallery/gallery-13.jpg')}}" alt="img" class="img-fluid">
                            </a>
                        </div><!-- end col -->
                        <div class="col-md-6 col-xl">
                            <a href="{{URL::asset('build/img/gallery/gallery-img-14.jpg')}}" class="image-popup">
                                <img src="{{URL::asset('build/img/gallery/gallery-14.jpg')}}" alt="img" class="img-fluid">
                            </a>
                        </div><!-- end col -->
                        <div class="col-md-6 col-xl">
                            <a href="{{URL::asset('build/img/gallery/gallery-img-15.jpg')}}" class="image-popup">
                                <img src="{{URL::asset('build/img/gallery/gallery-15.jpg')}}" alt="img" class="img-fluid">
                            </a>
                        </div><!-- end col -->
                    </div>
                    <!-- end row -->
                    

                </div><!-- end card body -->
            </div><!-- end card -->
            
                            
        </div>
        <!-- End Content -->

        @component('components.footer')
        @endcomponent

    </div>

    <!-- ========================
        End Page Content
    ========================= -->

@endsection    