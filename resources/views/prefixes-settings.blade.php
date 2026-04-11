<?php $page = 'prefixes-settings'; ?>
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
                                <h5 class="fw-bold">Prefixes</h5>
                            </div>
                            <div class="card-body px-0 mx-3">
                                <form action="{{url('profile-settings')}}">

                                    <!-- start row -->
                                    <div class="row">
                                        <div class="col-lg-6">

                                            <!-- start row -->
                                            <div class="row align-items-center mb-3">
                                                <div class="col-lg-4">
                                                    <label class="form-label mb-0">Doctor</label>
                                                </div><!-- end col -->
                                                <div class="col-lg-8">
                                                    <div class="input-group d-flex align-items-center">
                                                        <span class="input-group-text border-end-0 fs-14 bg-white pe-1">DOC -</span>
                                                        <input type="text" class="form-control border-start-0 ps-0" aria-label="Username">
                                                    </div>
                                                </div><!-- end col -->
                                            </div>
                                            <!-- end row -->

                                        </div><!-- end col -->
                                        <div class="col-lg-6">

                                            <!-- start row -->
                                            <div class="row align-items-center mb-3">
                                                <div class="col-lg-4">
                                                    <label class="form-label mb-0">Patient</label>
                                                </div><!-- end col -->
                                                <div class="col-lg-8">
                                                    <div class="input-group d-flex align-items-center">
                                                        <span class="input-group-text border-end-0 fs-14 bg-white pe-1">PNT -</span>
                                                        <input type="text" class="form-control border-start-0 ps-0" aria-label="Username">
                                                    </div>
                                                </div><!-- end col -->
                                            </div>
                                            <!-- end row -->

                                        </div><!-- end col -->
                                        <div class="col-lg-6">

                                            <!-- start row -->
                                            <div class="row align-items-center mb-3">
                                                <div class="col-lg-4">
                                                    <label class="form-label mb-0">Appointment</label>
                                                </div><!-- end col -->
                                                <div class="col-lg-8">
                                                    <div class="input-group d-flex align-items-center">
                                                        <span class="input-group-text border-end-0 fs-14 bg-white pe-1">APT -</span>
                                                        <input type="text" class="form-control border-start-0 ps-0" aria-label="Username">
                                                    </div>
                                                </div><!-- end col -->
                                            </div>
                                            <!-- end row -->

                                        </div><!-- end col -->
                                        <div class="col-lg-6">

                                            <!-- start row -->
                                            <div class="row align-items-center mb-3">
                                                <div class="col-lg-4">
                                                    <label class="form-label mb-0">Staff</label>
                                                </div><!-- end col -->
                                                <div class="col-lg-8">
                                                    <div class="input-group d-flex align-items-center">
                                                        <span class="input-group-text border-end-0 fs-14 bg-white pe-1">STF -</span>
                                                        <input type="text" class="form-control border-start-0 ps-0" aria-label="Username">
                                                    </div>
                                                </div><!-- end col -->
                                            </div>
                                            <!-- end row -->

                                        </div><!-- end col -->
                                        <div class="col-lg-6">

                                            <!-- start row -->
                                            <div class="row align-items-center mb-3">
                                                <div class="col-lg-4">
                                                    <label class="form-label mb-0">Visit</label>
                                                </div><!-- end col -->
                                                <div class="col-lg-8">
                                                    <div class="input-group d-flex align-items-center">
                                                        <span class="input-group-text border-end-0 fs-14 bg-white pe-1">VST -</span>
                                                        <input type="text" class="form-control border-start-0 ps-0" aria-label="Username">
                                                    </div>
                                                </div><!-- end col -->
                                            </div>
                                            <!-- end row -->

                                        </div><!-- end col -->
                                        <div class="col-lg-6">

                                            <!-- start row -->
                                            <div class="row align-items-center mb-3">
                                                <div class="col-lg-4">
                                                    <label class="form-label mb-0">Invoice</label>
                                                </div><!-- end col -->
                                                <div class="col-lg-8">
                                                    <div class="input-group d-flex align-items-center">
                                                        <span class="input-group-text border-end-0 fs-14 bg-white pe-1">INV -</span>
                                                        <input type="text" class="form-control border-start-0 ps-0" aria-label="Username">
                                                    </div>
                                                </div><!-- end col -->
                                            </div>
                                            <!-- end row -->

                                        </div><!-- end col -->
                                    </div>
                                    <!-- end row -->

                                    <div class="d-flex align-items-center justify-content-end">
                                        <a href="javascript:void(0);" class="btn btn-light me-3">Cancel</a>
                                        <a href="javascript:void(0);" class="btn btn-primary">Save Changes</a>
                                    </div>
                                </form>
                            </div>
                        </div>
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