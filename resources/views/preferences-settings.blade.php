<?php $page = 'preferences-settings'; ?>
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
                                    <h5 class="fw-bold">Preferences</h5>
                                </div>
                            </div>
                            <div class="card-body px-0 mx-3">
                            <!-- start row -->
                            <div class="row">

                                <div class="col-md-6 col-xl-4">
                                    <div class="card shadow-none">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <p class="text-dark fw-medium mb-0">Doctors</p>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                                </div>
                                            </div>
                                        </div> <!-- end card body -->
                                    </div> <!-- end card -->
                                </div> <!-- end col -->
                                <div class="col-md-6 col-xl-4">
                                    <div class="card shadow-none">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <p class="text-dark fw-medium mb-0">Patients</p>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                                </div>
                                            </div>
                                        </div> <!-- end card body -->
                                    </div> <!-- end card -->
                                </div> <!-- end col -->
                                <div class="col-md-6 col-xl-4">
                                    <div class="card shadow-none">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <p class="text-dark fw-medium mb-0">Appointments</p>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                                </div>
                                            </div>
                                        </div> <!-- end card body -->
                                    </div> <!-- end card -->
                                </div> <!-- end col -->
                                <div class="col-md-6 col-xl-4">
                                    <div class="card shadow-none">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <p class="text-dark fw-medium mb-0">Locations</p>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                                </div>
                                            </div>
                                        </div> <!-- end card body -->
                                    </div> <!-- end card -->
                                </div> <!-- end col -->
                                <div class="col-md-6 col-xl-4">
                                    <div class="card shadow-none">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <p class="text-dark fw-medium mb-0">Visits</p>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                                </div>
                                            </div>
                                        </div> <!-- end card body -->
                                    </div> <!-- end card -->
                                </div> <!-- end col -->
                                <div class="col-md-6 col-xl-4">
                                    <div class="card shadow-none">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <p class="text-dark fw-medium mb-0">Services</p>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                                </div>
                                            </div>
                                        </div> <!-- end card body -->
                                    </div> <!-- end card -->
                                </div> <!-- end col -->
                                <div class="col-md-6 col-xl-4">
                                    <div class="card shadow-none">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <p class="text-dark fw-medium mb-0">Designations</p>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                                </div>
                                            </div>
                                        </div> <!-- end card body -->
                                    </div> <!-- end card -->
                                </div> <!-- end col -->
                                <div class="col-md-6 col-xl-4">
                                    <div class="card shadow-none">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <p class="text-dark fw-medium mb-0">Departments</p>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                                </div>
                                            </div>
                                        </div> <!-- end card body -->
                                    </div> <!-- end card -->
                                </div> <!-- end col -->
                                <div class="col-md-6 col-xl-4">
                                    <div class="card shadow-none">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <p class="text-dark fw-medium mb-0">Activities</p>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                                </div>
                                            </div>
                                        </div> <!-- end card body -->
                                    </div> <!-- end card -->
                                </div> <!-- end col -->
                                <div class="col-md-6 col-xl-4">
                                    <div class="card shadow-none">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <p class="text-dark fw-medium mb-0">Reports</p>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                                </div>
                                            </div>
                                        </div> <!-- end card body -->
                                    </div> <!-- end card -->
                                </div> <!-- end col -->
                                <div class="col-md-6 col-xl-4">
                                    <div class="card shadow-none">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <p class="text-dark fw-medium mb-0">Staffs</p>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                                </div>
                                            </div>
                                        </div> <!-- end card body -->
                                    </div> <!-- end card -->
                                </div> <!-- end col -->
                                <div class="col-md-6 col-xl-4">
                                    <div class="card shadow-none">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <p class="text-dark fw-medium mb-0">Invoices</p>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                                </div>
                                            </div>
                                        </div> <!-- end card body -->
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