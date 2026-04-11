<?php $page = 'ui-rangeslider'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- ========================
        Start Page Content
    ========================= -->

    <div class="page-wrapper cardhead">
        
        <!-- Start Content -->
        <div class="content pb-0">

            <!-- Page Header -->
            <div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3">
                <div class="flex-grow-1">
                    <h4 class="fw-bold mb-0">Rangeslider</h4>
                </div>
                <div class="text-end">
                    <ol class="breadcrumb m-0 py-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Home</a></li>
                        
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Advanced UI</a></li>
                        
                        <li class="breadcrumb-item active">Rangeslider</li>
                    </ol>
                </div>
            </div>
            <!-- End Page Header -->

            <!-- start row -->
            <div class="row">

                <!-- Rangeslider -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Default</h5>
                        </div>
                        <div class="card-body">
                            <input type="text" id="range_01">
                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div><!-- end col -->
                <!-- /Rangeslider -->

                <!-- Rangeslider -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Min-Max</h5>
                        </div>
                        <div class="card-body">
                            <input type="text" id="range_02">
                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div><!-- end col -->
                <!-- /Rangeslider -->

                <!-- Rangeslider -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Prefix</h5>
                        </div>
                        <div class="card-body">
                            <input type="text" id="range_03">
                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div><!-- end col -->
                <!-- /Rangeslider -->

                <!-- Rangeslider -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Range</h5>
                        </div>
                        <div class="card-body">
                            <input type="text" id="range_04">
                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div><!-- end col -->
                <!-- /Rangeslider -->

                <!-- Rangeslider -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Step</h5>
                        </div>
                        <div class="card-body">
                            <input type="text" id="range_05">
                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div><!-- end col -->
                <!-- /Rangeslider -->

                <!-- Rangeslider -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Custom Values</h5>
                        </div>
                        <div class="card-body">
                            <input type="text" id="range_06">
                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div><!-- end col -->
                <!-- /Rangeslider -->

                <!-- Rangeslider -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Modern skin</h5>
                        </div>
                        <div class="card-body">
                            <input type="text" id="range_13">
                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div><!-- end col -->
                <!-- /Rangeslider -->

                <!-- Rangeslider -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Sharp Skin</h5>
                        </div>
                        <div class="card-body">
                            <input type="text" id="range_14">
                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div><!-- end col -->
                <!-- /Rangeslider -->

                <!-- Rangeslider -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Round skin</h5>
                        </div>
                        <div class="card-body">
                            <input type="text" id="range_15">
                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div><!-- end col -->
                <!-- /Rangeslider -->

                <!-- Rangeslider -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Square Skin</h5>
                        </div>
                        <div class="card-body">
                            <input type="text" id="range_16">
                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div><!-- end col -->
                <!-- /Rangeslider -->

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
