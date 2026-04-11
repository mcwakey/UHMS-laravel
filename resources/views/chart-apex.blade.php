<?php $page = 'chart-apex'; ?>
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
                    <h4 class="fw-bold mb-0">Apex Chart</h4>
                </div>
                <div class="text-end">
                    <ol class="breadcrumb m-0 py-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Home</a></li>
                        
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Charts</a></li>
                        
                        <li class="breadcrumb-item active">Apex Chart</li>
                    </ol>
                </div>
            </div>
            <!-- End Page Header -->

            <!-- start row -->
            <div class="row">

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Apex Simple</h5>
                        </div>
                        <div class="card-body">
                            <div id="s-line" class="chart-set"></div>
                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div><!-- end col -->

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Area Chart</h5>
                        </div>
                        <div class="card-body">
                            <div id="s-line-area" class="chart-set"></div>
                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div><!-- end col -->

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Column Chart</h5>
                        </div>
                        <div class="card-body">
                            <div id="s-col-chart" class="chart-set"></div>
                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div><!-- end col -->

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Column Stacked Chart</h5>
                        </div>
                        <div class="card-body">
                            <div id="s-col-stacked" class="chart-set"></div>
                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div><!-- end col -->


                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Bar Chart</h5>
                        </div>
                        <div class="card-body">
                            <div id="s-bar" class="chart-set"></div>
                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div><!-- end col -->

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Mixed Chart</h5>
                        </div>
                        <div class="card-body">
                            <div id="mixed-chart" class="chart-set"></div>
                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div><!-- end col -->

                <div class="col-md-6 d-flex">
                    <div class="card w-100">
                        <div class="card-header">
                            <h5 class="card-title">Donut Chart</h5>
                        </div>
                        <div class="card-body">
                            <div id="donut-chart" class="chart-set"></div>
                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div><!-- end col -->

                <div class="col-md-6 d-flex">
                    <div class="card w-100">
                        <div class="card-header">
                            <h5 class="card-title">Radial Chart</h5>
                        </div>
                        <div class="card-body">
                            <div id="radial-chart" class="chart-set"></div>
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