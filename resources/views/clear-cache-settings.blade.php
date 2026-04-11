<?php $page = 'clear-cache-settings'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- ========================
        Start Page Content
    ========================= -->
        
    <div class="page-wrapper">

        <!-- Start Content -->
        <div class="content" id="profilePage">

            <!-- Page Header -->
            <div class="mb-3 border-bottom pb-3">
                <h4 class="fw-bold mb-0">Settings</h4>
            </div>
            <!-- End Page Header -->

            <div class="card">
                <div class="card-body p-0">
                    <div class="settings-wrapper d-flex">

                        @component('components.settings-sidebar')
                        @endcomponent

                        <div class="card flex-fill mb-0 border-0 bg-light-500 shadow-none">
                            <div class="card-header border-bottom px-0 mx-3">
                                <div class="d-flex">
                                    <h5 class="fw-bold">Clear Cache</h5>
                                </div>
                            </div>
                            <div class="card-body px-0 mx-3">
                                <p>Clearing the cache may improve performance but will remove temporary files, stored preferences, and cached data from websites and applications.</p>
                                <a href="javascript:void(0);" class="btn btn-primary">Clear Cache</a>
                            </div><!-- end card body -->
                        </div><!-- end card -->

                    </div>

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