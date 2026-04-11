<?php $page = 'ui-lightbox'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- ========================
        Start Page Content
    ========================= -->

    <div class="page-wrapper">

        <!-- Start Content -->
        <div class="content pb-0">

            <!-- Page Header -->
            <div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3">
                <div class="flex-grow-1">
                    <h4 class="fw-bold mb-0">Lightbox</h4>
                </div>
                <div class="text-end">
                    <ol class="breadcrumb m-0 py-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Home</a></li>
                        
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Base UI</a></li>
                        
                        <li class="breadcrumb-item active">Lightbox</li>
                    </ol>
                </div>
            </div>
            <!-- End Page Header -->

            
            <div class="row">

                <!-- Lightbox -->
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Single Image Lightbox</h5>
                        </div>
                        <div class="card-body pb-1">

                            <!-- start row -->
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <a href="{{URL::asset('build/img/media/img-01.jpg')}}" class="image-popup">
                                        <img src="{{URL::asset('build/img/media/img-01.jpg')}}" class="img-fluid" alt="image">
                                    </a>
                                </div><!-- end col -->
                                <div class="col-md-4 mb-3">
                                    <a href="{{URL::asset('build/img/media/img-02.jpg')}}" class="image-popup">
                                        <img src="{{URL::asset('build/img/media/img-02.jpg')}}" class="img-fluid" alt="image">
                                    </a>
                                </div><!-- end col -->
                            </div>
                            <!-- end row -->

                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div>
                <!-- /Lightbox -->

                <!-- Lightbox -->
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Image with Description</h5>
                        </div>
                        <div class="card-body pb-1">

                            <!-- start row -->
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <a href="{{URL::asset('build/img/media/img-03.jpg')}}" class="image-popup-desc" data-title="Title 01" data-description="Lorem ipsum dolor sit amet, consectetuer adipiscing elit">
                                        <img src="{{URL::asset('build/img/media/img-03.jpg')}}" class="img-fluid" alt="work-thumbnail">
                                    </a>
                                </div><!-- end col -->
                                <div class="col-md-4 mb-3">
                                    <a href="{{URL::asset('build/img/media/img-04.jpg')}}" class="image-popup-desc" data-title="Title 02" data-description="Lorem ipsum dolor sit amet, consectetuer adipiscing elit">
                                        <img src="{{URL::asset('build/img/media/img-04.jpg')}}" class="img-fluid" alt="work-thumbnail">
                                    </a>
                                </div><!-- end col -->
                                <div class="col-md-4 mb-3">
                                    <a href="{{URL::asset('build/img/media/img-05.jpg')}}" class="image-popup-desc" data-title="Title 03" data-description="Lorem ipsum dolor sit amet, consectetuer adipiscing elit">
                                        <img src="{{URL::asset('build/img/media/img-05.jpg')}}" class="img-fluid" alt="work-thumbnail">
                                    </a>
                                </div><!-- end col -->
                            </div>
                            <!-- end row -->

                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div>
                <!-- /Lightbox -->

            </div>

        </div> 
        <!-- End Content -->

        @component('components.footer')
        @endcomponent

    </div>

    <!-- ========================
    End Page Content
    ========================= -->
    
@endsection
