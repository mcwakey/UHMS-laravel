<?php $page = 'chart-c3'; ?>
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
                    <h4 class="fw-bold mb-0">C3 Chart</h4>
                </div>
                <div class="text-end">
                    <ol class="breadcrumb m-0 py-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Home</a></li>
                        
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Charts</a></li>
                        
                        <li class="breadcrumb-item active">C3 Chart</li>
                    </ol>
                </div>
            </div>
            <!-- End Page Header -->

            <!-- start row -->
            <div class="row">

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Bar Chart</div>
                        </div>
                        <div class="card-body">
                            <div id="chart-bar-stacked"></div>
                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div><!-- end col -->

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Multiple Bar Chart</div>
                        </div>
                        <div class="card-body">
                            <div id="chart-bar"></div>
                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div><!-- end col -->

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Horizontal Bar Chart</div>
                        </div>
                        <div class="card-body">
                            <div id="chart-bar-rotated"></div>
                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div><!-- end col -->

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Line Chart</div>
                        </div>
                        <div class="card-body">
                            <div id="chart-sracked"></div>
                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div><!-- end col -->

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Line Chart</div>
                        </div>
                        <div class="card-body">
                            <div id="chart-spline-rotated"></div>
                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div><!-- end col -->

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Line Chart</div>
                        </div>
                        <div class="card-body">
                            <div id="chart-area-spline-sracked"></div>
                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div><!-- end col -->

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Pie Chart</div>
                        </div>
                        <div class="card-body">
                            <div id="chart-pie"></div>
                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div><!-- end col -->

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Donut Chart</div>
                        </div>
                        <div class="card-body">
                            <div id="chart-donut"></div>
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