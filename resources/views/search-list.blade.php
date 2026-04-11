<?php $page = 'search-list'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- ========================
        Start Page Content
    ========================= -->

    <div class="page-wrapper">
        
        <!-- Start Content -->
        <div class="content pb-0">

            <div class="card">
                <div class="card-body">
                    <form action="{{url('search-list')}}">
                        <div class="d-flex align-items-center">
                            <input type="text" class="form-control flex-fill me-3" value="Preclinic">
                            <button type="submit" class="btn btn-primary">Search</button>
                        </div>
                    </form>
                </div><!-- end card body -->
            </div><!-- end card -->

            <div class="card">
                <div class="card-body">
                    <h6 class="mb-3 text-capitalize">Search result for "Preclinic"</h6>

                    <!-- start row -->
                    <div class="row">

                        <div class="col-md-6">
                            <div class="card shadow-none">
                                <div class="card-body">
                                    <a href="#" class="text-info text-truncate mb-2 text-wrap">https://themeforest.net/search/preclinic</a>
                                    <p class="text-truncate line-clamb-2 mb-2">Preclinic - Html, Vue 3, Angular 17+, React & Node HR Project Management & CRM Admin Dashboard Template</p>
                                    <div class="d-flex align-items-center flex-wrap row-gap-2">
                                        <span class="text-gray-9 me-3 pe-3 border-end">1.7K Sales</span>
                                        <div class="text-gray-9 d-flex align-items-center me-3 pe-3 border-end">
                                            <i class="ti ti-star-filled text-warning me-1"></i>
                                            <i class="ti ti-star-filled text-warning me-1"></i>
                                            <i class="ti ti-star-filled text-warning me-1"></i>
                                            <i class="ti ti-star-filled text-warning me-1"></i>
                                            <i class="ti ti-star-filled text-light me-1"></i>
                                            (45)
                                        </div>
                                        <span class="text-gray-9">$35</span>
                                    </div>
                                </div><!-- end card body -->    
                            </div><!-- end card -->    
                        </div><!-- end col -->

                    </div>
                    <!-- end row -->

                    <h6 class="mb-3">Images</h6>

                    <!-- start row -->
                    <div class="row g-3">

                        <div class="col-xl-2 col-md-4 col-6">
                            <a href="{{URL::asset('build/img/media/media-15.jpg')}}" data-fancybox="gallery" class="gallery-item">
                                <img src="{{URL::asset('build/img/media/media-15.jpg')}}" class="rounded" alt="img">
                            </a>
                        </div><!-- end col -->

                        <div class="col-xl-2 col-md-4 col-6">
                            <a href="{{URL::asset('build/img/media/media-16.jpg')}}" data-fancybox="gallery" class="gallery-item">
                                <img src="{{URL::asset('build/img/media/media-16.jpg')}}" class="rounded" alt="img">
                            </a>
                        </div><!-- end col -->

                        <div class="col-xl-2 col-md-4 col-6">
                            <a href="{{URL::asset('build/img/media/media-17.jpg')}}" data-fancybox="gallery" class="gallery-item">
                                <img src="{{URL::asset('build/img/media/media-17.jpg')}}" class="rounded" alt="img">
                            </a>
                        </div><!-- end col -->

                        <div class="col-xl-2 col-md-4 col-6">
                            <a href="{{URL::asset('build/img/media/media-18.jpg')}}" data-fancybox="gallery" class="gallery-item">
                                <img src="{{URL::asset('build/img/media/media-18.jpg')}}" class="rounded" alt="img">
                            </a>
                        </div><!-- end col -->

                        <div class="col-xl-2 col-md-4 col-6">
                            <a href="{{URL::asset('build/img/media/media-19.jpg')}}" data-fancybox="gallery" class="gallery-item">
                                <img src="{{URL::asset('build/img/media/media-19.jpg')}}" class="rounded" alt="img">
                            </a>
                        </div><!-- end col -->

                        <div class="col-xl-2 col-md-4 col-6">
                            <a href="{{URL::asset('build/img/media/media-20.jpg')}}" data-fancybox="gallery" class="gallery-item">
                                <img src="{{URL::asset('build/img/media/media-20.jpg')}}" class="rounded" alt="img">
                            </a>
                        </div><!-- end col -->

                        <div class="col-xl-2 col-md-4 col-6">
                            <a href="{{URL::asset('build/img/media/media-21.jpg')}}" data-fancybox="gallery" class="gallery-item">
                                <img src="{{URL::asset('build/img/media/media-21.jpg')}}" class="rounded" alt="img">
                            </a>
                        </div><!-- end col -->

                        <div class="col-xl-2 col-md-4 col-6">
                            <a href="{{URL::asset('build/img/media/media-22.jpg')}}" data-fancybox="gallery" class="gallery-item">
                                <img src="{{URL::asset('build/img/media/media-22.jpg')}}" class="rounded" alt="img">
                            </a>
                        </div><!-- end col -->

                        <div class="col-xl-2 col-md-4 col-6">
                            <a href="{{URL::asset('build/img/media/media-23.jpg')}}" data-fancybox="gallery" class="gallery-item">
                                <img src="{{URL::asset('build/img/media/media-23.jpg')}}" class="rounded" alt="img">
                            </a>
                        </div><!-- end col -->

                        <div class="col-xl-2 col-md-4 col-6">
                            <a href="{{URL::asset('build/img/media/media-24.jpg')}}" data-fancybox="gallery" class="gallery-item">
                                <img src="{{URL::asset('build/img/media/media-24.jpg')}}" class="rounded" alt="img">
                            </a>
                        </div><!-- end col -->

                        <div class="col-xl-2 col-md-4 col-6">
                            <a href="{{URL::asset('build/img/media/media-25.jpg')}}" data-fancybox="gallery" class="gallery-item">
                                <img src="{{URL::asset('build/img/media/media-25.jpg')}}" class="rounded" alt="img">
                            </a>
                        </div><!-- end col -->

                        <div class="col-xl-2 col-md-4 col-6">
                            <a href="{{URL::asset('build/img/media/media-26.jpg')}}" data-fancybox="gallery" class="gallery-item">
                                <img src="{{URL::asset('build/img/media/media-26.jpg')}}" class="rounded" alt="img">
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