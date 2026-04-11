<?php $page = 'ui-ratio'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- ========================
        Start Page Content
    ========================= -->

    <div class="page-wrapper">

        <!-- Start Content -->
        <div class="content pb-0">

            <!-- Page Header -->
            <div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 py-3">
                <div class="flex-grow-1">
                    <h4 class="fw-bold mb-0">Ratio Video</h4>
                </div>

                <div class="text-end">
                    <ol class="breadcrumb m-0 py-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Home</a></li>

                        <li class="breadcrumb-item"><a href="javascript: void(0);">Base UI</a></li>

                        <li class="breadcrumb-item active">Ratio Video</li>
                    </ol>
                </div>
            </div>
            <!-- End Page Header -->

            <!-- start row -->
            <div class="row">
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Responsive Ratio video 21:9</h5>
                        </div>

                        <div class="card-body">
                            <p class="text-muted">Use class <code>.ratio-21x9</code>
                                <!-- 21:9 aspect ratio -->
                                <div class="ratio ratio-21x9">
                                    <iframe src="https://www.youtube.com/embed/6bzTrChjEdc?autohide=0&showinfo=0&controls=0"></iframe>
                                </div>
                        </div><!-- end card body -->
                    </div><!-- end card -->

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Responsive Ratio video 1:1</h5>
                        </div>

                        <div class="card-body">
                            <p class="text-muted">Use class <code>.ratio-1x1</code></p>
                            <!-- 1:1 aspect ratio -->
                            <div class="ratio ratio-1x1">
                                <iframe src="https://www.youtube.com/embed/6bzTrChjEdc?autohide=0&showinfo=0&controls=0"></iframe>
                            </div>
                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div><!-- end col -->

                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Responsive Ratio video 16:9</h5>
                        </div>

                        <div class="card-body">
                            <p class="text-muted">Use class <code>.ratio-16x9</code></p>
                                <!-- 16:9 aspect ratio -->
                                <div class="ratio ratio-16x9">
                                    <iframe src="https://www.youtube.com/embed/6bzTrChjEdc?autohide=0&showinfo=0&controls=0"></iframe>
                                </div>
                        </div><!-- end card body -->
                    </div><!-- end card -->

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Responsive Ratio video 4:3</h5>
                        </div>

                        <div class="card-body">
                            <p class="text-muted">Use class <code>.ratio-4x3</code></p>
                                <!-- 4:3 aspect ratio -->
                                <div class="ratio ratio-4x3">
                                    <iframe src="https://www.youtube.com/embed/6bzTrChjEdc?autohide=0&showinfo=0&controls=0"></iframe>
                                </div>
                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div><!-- end col -->
            </div>
            <!-- end row -->

        </div>
        <!-- End Content -->

        @component('components.footer')
        @endcomponent

    </div>

    <!-- ========================
        End Page Content
    ========================= -->
    
@endsection