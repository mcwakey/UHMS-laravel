<?php $page = 'system-update'; ?>
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
                                <div class="d-flex align-items-center justify-content-between">
                                    <h5 class="fw-bold">System Update</h5>
                                    <a href="javascript:void(0);" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#generate">Check for Update</a>
                                </div>
                            </div>
                            <div class="card-body px-0 mx-3">
                                <div class="d-flex align-items-center mb-3">
                                    <span class="p-2 bg-light rounded-circle d-inline-flex align-items-center justify-content-center text-primary me-1"><i class="ti ti-circle-check-filled fs-20"></i></span>
                                    <div>
                                        <div class="d-flex align-items-center mb-1">
                                            <h6 class="fs-14 mb-0 me-2">You are up to date</h6>
                                            <span class="badge badge-soft-primary border border-primary fw-medium">Current Version : 8.0</span>
                                        </div>
                                        <span class="fs-13">Last Checked : Today 10:30 AM</span>
                                    </div>
                                </div>

                                <div class="p-3 bg-light border rounded-2 mb-3">
                                    <p class="mb-0"><i class="ti ti-info-circle me-1 text-info"></i>Before updating, it's best to back up your files and database and review the changelog.</p>
                                </div>

                                <!-- start row -->
                                    <div class="row">
                                    <div class="col-lg-6">
                                        <div class="mb-0">
                                            <label class="form-label">Purchase Key<span class="text-danger ms-1">*</span></label>
                                            <input type="text" class="form-control">
                                        </div>
                                    </div><!-- end col -->
                                    <div class="col-lg-6">
                                        <div class="mb-0">
                                            <label class="form-label">User Name<span class="text-danger ms-1">*</span></label>
                                            <input type="text" class="form-control">
                                        </div>
                                    </div><!-- end col -->
                                    </div>
                                    <!-- end row -->

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