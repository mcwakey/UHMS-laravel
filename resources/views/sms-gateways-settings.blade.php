<?php $page = 'sms-gateways-settings'; ?>
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
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <h5 class="fw-bold">SMS Gateways</h5>
                                </div>
                            </div>
                            <div class="card-body px-0 mx-3">
                                <!-- start row -->
                            <div class="row">

                                <div class="col-md-6">
                                    <div class="card shadow-none">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between mb-3">
                                                <img src="{{URL::asset('build/img/icons/nexmo.svg')}}" alt="Img">
                                                <span class="badge badge-soft-success fs-13 fw-medium"><i class="ti ti-point-filled"></i>Connected</span>
                                            </div>
                                            <div>
                                                <p class="mb-0">Enables seamless communication through SMS, voice, and APIs.</p>
                                            </div>
                                        </div> <!-- end card body -->
                                        <div class="card-footer d-flex align-items-center justify-content-between ">
                                            <div class="d-flex align-items-center">
                                                <a class="btn btn-sm btn-light border rounded-2 p-1 me-2" href="#" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash"></i></a>
                                                <a class="btn btn-sm btn-light border rounded-2 p-1" href="#" data-bs-toggle="modal" data-bs-target="#add_nexmo"><i class="ti ti-settings"></i></a>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input m-0" type="checkbox" checked="">
                                            </div>
                                        </div> <!-- end card footer -->
                                    </div> <!-- end card -->
                                </div> <!-- end col -->

                                <div class="col-md-6">
                                    <div class="card shadow-none">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between mb-3">
                                                <img src="{{URL::asset('build/img/icons/2factor.svg')}}" alt="Img">
                                                <span class="badge badge-soft-success fs-13 fw-medium"><i class="ti ti-point-filled"></i>Connected</span>
                                            </div>
                                            <div>
                                                <p class="mb-0">2Factor offers simple sms integration API and sample code to send SMS</p>
                                            </div>
                                        </div> <!-- end card body -->
                                        <div class="card-footer d-flex align-items-center justify-content-between ">
                                            <div class="d-flex align-items-center">
                                                <a class="btn btn-sm btn-light border rounded-2 p-1 me-2" href="#" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash"></i></a>
                                                <a class="btn btn-sm btn-light border rounded-2 p-1" href="#" data-bs-toggle="modal" data-bs-target="#add_nexmo"><i class="ti ti-settings"></i></a>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input m-0" type="checkbox" checked="">
                                            </div>
                                        </div> <!-- end card footer -->
                                    </div> <!-- end card -->
                                </div> <!-- end col -->

                                <div class="col-md-6">
                                    <div class="card shadow-none">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between mb-3">
                                                <img src="{{URL::asset('build/img/icons/twilio.svg')}}" alt="Img">
                                                <span class="badge bg-light fs-13 fw-medium text-dark"><i class="ti ti-point-filled"></i>Not Connected</span>
                                            </div>
                                            <div>
                                                <p class="mb-0">Twilio provides APIs for messaging, voice, and video integration.</p>
                                            </div>
                                        </div> <!-- end card body -->
                                        <div class="card-footer d-flex align-items-center justify-content-between ">
                                            <div class="d-flex align-items-center">
                                                <a class="btn btn-sm btn-light border rounded-2 p-1 me-2" href="#" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash"></i></a>
                                                <a class="btn btn-sm btn-light border rounded-2 p-1" href="#" data-bs-toggle="modal" data-bs-target="#add_nexmo"><i class="ti ti-settings"></i></a>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input m-0" type="checkbox" checked="">
                                            </div>
                                        </div> <!-- end card footer -->
                                    </div> <!-- end card -->
                                </div> <!-- end col -->

                            </div>
                            <!-- end row -->
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