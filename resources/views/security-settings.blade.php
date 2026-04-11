<?php $page = 'security-settings'; ?>
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
                                    <h5 class="fw-bold">Security</h5>
                                </div>
                            </div>
                            <div class="card-body px-0 mx-3">

                                <!-- start row -->
                                <div class="row">
                                    <div class="col-lg-8">
                                        <div>
                                            <div class="d-flex align-items-center justify-content-between flex-wrap row-gap-3 border-bottom mb-3 pb-3">
                                                <div class="d-flex align-items-center">
                                                    <div>
                                                        <h5 class="fs-16 fw-semibold mb-1">Password</h5>
                                                        <p class="fs-14">Set a unique password to secure the account</p>
                                                    </div>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#change_password"><span class="btn btn-md btn-light p-1 shadow-sm border"><i class="ti ti-edit"></i></span></a>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between flex-wrap row-gap-3 border-bottom mb-3 pb-3">
                                                <div class="d-flex align-items-center">
                                                    <div>
                                                        <h5 class="fs-16 fw-semibold mb-1">Two Factor Authentication</h5>
                                                        <p class="fs-14">Use your mobile phone to receive security PIN.</p>
                                                    </div>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <label class="d-flex align-items-center form-switch ps-3">
                                                        <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between flex-wrap row-gap-3 border-bottom mb-3 pb-3">										
                                                <div class="d-flex align-items-center">
                                                    <div>
                                                        <h5 class="fs-16 fw-semibold mb-1">Google Authentication</h5>
                                                        <p class="fs-14">Connect to Google</p>
                                                    </div>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <label class="d-flex align-items-center form-switch ps-3">
                                                        <input class="form-check-input m-0 me-2" type="checkbox" checked>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between flex-wrap row-gap-3 border-bottom mb-3 pb-3">
                                                <div class="d-flex align-items-center">
                                                    <div>
                                                        <h5 class="fs-16 fw-semibold mb-1">Phone Number</h5>
                                                        <p class="fs-14">Phone Number associated with the account</p>
                                                    </div>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="me-3" data-bs-toggle="modal" data-bs-target="#phone_verification"><span class="btn btn-md btn-light border shadow-sm p-1"><i class="ti ti-edit"></i></span></a>
                                                    <a href="javascript:void(0);"><span class="btn btn-md btn-light border shadow-sm p-1"><i class="ti ti-trash"></i></span></a>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between flex-wrap row-gap-3 border-bottom mb-3 pb-3">
                                                <div class="d-flex align-items-center">
                                                    <div>
                                                        <h5 class="fs-16 fw-semibold mb-1">Email Address</h5>
                                                        <p class="fs-14">Email Address associated with the account</p>
                                                    </div>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="me-3" data-bs-toggle="modal" data-bs-target="#email_verification"><span class="btn btn-md btn-light border shadow-sm p-1"><i class="ti ti-edit"></i></span></a>
                                                    <a href="javascript:void(0);"><span class="btn btn-md btn-light border shadow-sm p-1"><i class="ti ti-trash"></i></span></a>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between flex-wrap row-gap-3 border-bottom mb-3 pb-3">
                                                <div class="d-flex align-items-center">
                                                    <div>
                                                        <h5 class="fs-16 fw-semibold mb-1">Deactivate Account</h5>
                                                        <p class="fs-14">​Your account will be deactivated and reactivated upon signing in again.</p>
                                                    </div>
                                                </div>
                                                <a href="javascript:void(0);"><span class="btn btn-md btn-light border shadow-sm p-1"><i class="ti ti-ban"></i></span></a>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                                                <div class="d-flex align-items-center">
                                                    <div>
                                                        <h5 class="fs-16 fw-semibold mb-1">Delete Account</h5>
                                                        <p class="fs-14">Your account will be permanently deleted</p>
                                                    </div>
                                                </div>
                                                <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete_modal"><span class="btn btn-md btn-light border shadow-sm p-1"><i class="ti ti-trash"></i></span></a>
                                            </div>
                                        </div>
                                    </div><!-- end col -->
                                    <div class="col-lg-4">
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <h6 class="fs-14 fw-semibold">Browsers & Devices</h6>
                                                    <p class="mb-1">The associated browsers & devices </p>
                                                    <a href="javascript:void(0);" class="btn btn-primary"><i class="ti ti-logout me-1"></i>Sign out from all</a>
                                                </div>
                                                <div class="d-flex align-items-center justify-content-between p-2 border-bottom">
                                                    <div>
                                                        <h6 class="fs-14 fw-semibold">Chrome - Windows</h6>
                                                        <span class="fs-13">30 Apr 2025, 11:15 AM</span>
                                                    </div>
                                                    <a href="javascript:void(0);" class="btn btn-md bg-white border shadow-sm p-1"><i class="ti ti-logout"></i></a>
                                                </div>
                                                <div class="d-flex align-items-center justify-content-between p-2 border-bottom">
                                                    <div>
                                                        <h6 class="fs-14 fw-semibold">Safari Macos</h6>
                                                        <span class="fs-13">30 Apr 2025, 11:15 AM</span>
                                                    </div>
                                                    <a href="javascript:void(0);" class="btn btn-md bg-white border shadow-sm p-1"><i class="ti ti-logout"></i></a>
                                                </div>
                                                <div class="d-flex align-items-center justify-content-between p-2 border-bottom">
                                                    <div>
                                                        <h6 class="fs-14 fw-semibold">Chrome - Windows</h6>
                                                        <span class="fs-13">30 Apr 2025, 11:15 AM</span>
                                                    </div>
                                                    <a href="javascript:void(0);" class="btn btn-md bg-white border shadow-sm p-1"><i class="ti ti-logout"></i></a>
                                                </div>
                                                <div class="d-flex align-items-center justify-content-between p-2 border-bottom">
                                                    <div>
                                                        <h6 class="fs-14 fw-semibold">Chrome - Windows</h6>
                                                        <span class="fs-13">19 Mar 2025, 02:50 PM</span>
                                                    </div>
                                                    <a href="javascript:void(0);" class="btn btn-md bg-white border shadow-sm p-1"><i class="ti ti-logout"></i></a>
                                                </div>
                                                <div class="d-flex align-items-center justify-content-between p-2 border-bottom">
                                                    <div>
                                                        <h6 class="fs-14 fw-semibold">Firefox Windows</h6>
                                                        <span class="fs-13">20 Feb 2025, 06:20 PM</span>
                                                    </div>
                                                    <a href="javascript:void(0);" class="btn btn-md bg-white border shadow-sm p-1"><i class="ti ti-logout"></i></a>
                                                </div>
                                                <div class="d-flex align-items-center justify-content-between p-2 border-bottom">
                                                    <div>
                                                        <h6 class="fs-14 fw-semibold">Chrome - Windows</h6>
                                                        <span class="fs-13">18 Jan 2025, 03:15 PM</span>
                                                    </div>
                                                    <a href="javascript:void(0);" class="btn btn-md bg-white border shadow-sm p-1"><i class="ti ti-logout"></i></a>
                                                </div>
                                                <div class="d-flex align-items-center justify-content-between p-2 border-bottom">
                                                    <div>
                                                        <h6 class="fs-14 fw-semibold">Safari Macos</h6>
                                                        <span class="fs-13">02 Jan 2025, 09:30 AM</span>
                                                    </div>
                                                    <a href="javascript:void(0);" class="btn btn-md bg-white border shadow-sm p-1"><i class="ti ti-logout"></i></a>
                                                </div>
                                                <div class="d-flex align-items-center justify-content-between p-2">
                                                    <div>
                                                        <h6 class="fs-14 fw-semibold">Firefox Windows</h6>
                                                        <span class="fs-13">28 Dec 2024, 05:40 PM</span>
                                                    </div>
                                                    <a href="javascript:void(0);" class="btn btn-md bg-white border shadow-sm p-1"><i class="ti ti-logout"></i></a>
                                                </div>
                                            </div><!-- end card -->
                                        </div><!-- end card -->
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