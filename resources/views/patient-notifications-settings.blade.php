<?php $page = 'patient-notifications-settings'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- ========================
        Start Page Content
    ========================= -->
        
    <div class="page-wrapper">

        <!-- Start Content -->
        <div class="content">

            <!-- Page Header -->
            <div class="mb-3 border-bottom pb-3">
                <h4 class="fw-bold mb-0">Settings</h4>
            </div>
            <!-- End Page Header -->

            <div class="card">
                <div class="card-body">
                    
                    <!-- end card body -->
                    <div class="row">
                        <div class="col-lg-3">
                            <div class="text-start">
                                <a href="{{url('patient-profile-settings')}}" class="btn btn-md rounded fs-14 fw-medium text-dark mb-1 w-100 justify-content-start"> <i class="ti ti-user-cog me-2 text-dark"> </i>Profile Settings  </a>
                                <a href="{{url('patient-password-settings')}}" class="btn btn-md rounded fs-14 fw-medium text-dark mb-1 w-100 justify-content-start"> <i class="ti ti-lock-star me-2 text-dark"> </i> Change Password </a>
                                <a href="{{url('patient-notifications-settings')}}" class="d-block w-100 btn btn-md border rounded fs-14 fw-medium text-primary text-start mb-1 w-100 justify-content-start"> <i class="ti ti-bell me-2 text-primary active"> </i>Notifications </a>
                            </div>

                        </div><!-- end col -->

                        <div class="col-lg-9">
                            <div class="border-1 border-start ps-4">
                                <h5 class="fw-bold pb-3 mb-4 border-1 border-bottom"> Notifications </h5>


                                    <!-- Items -->
                                    <div class="d-flex align-items-center justify-content-between flex-wrap row-gap-3 border mb-3 p-3 rounded">
                                        <div class="d-flex align-items-center">
                                            <span class="avatar avatar-lg border bg-light me-2">
                                                <i class="ti ti-calendar-time text-dark fs-16"></i>
                                            </span>
                                            <div>
                                                <h5 class="fs-14 fw-semibold mb-1">New Appointment Booking</h5>
                                                <p class="fs-13">Alert when an appointment is booked</p>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-4">
                                            <div class="">
                                                <p class="fw-medium mb-1 text-dark"> Email </p>
                                                <label class="d-flex align-items-center form-switch ps-0">
                                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                                </label>
                                            </div>
                                            <div class="">
                                                <p class="fw-medium mb-1 text-dark"> SMS </p>
                                                <label class="d-flex align-items-center form-switch ps-0">
                                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                                </label>
                                            </div>
                                            <div class="">
                                                <p class="fw-medium mb-1 text-dark"> In App </p>
                                                <label class="d-flex align-items-center form-switch ps-0">
                                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Items -->
                                    <div class="d-flex align-items-center justify-content-between flex-wrap row-gap-3 border mb-3 p-3 rounded">
                                        <div class="d-flex align-items-center">
                                            <span class="avatar avatar-lg border bg-light me-2">
                                                <i class="ti ti-calendar-x text-dark fs-16"></i>
                                            </span>
                                            <div>
                                                <h5 class="fs-14 fw-semibold mb-1">Appointment Cancellation</h5>
                                                <p class="fs-13">Alert if a appointment is cancel</p>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-4">
                                            <div class="">
                                                <p class="fw-medium mb-1 text-dark"> Email </p>
                                                <label class="d-flex align-items-center form-switch ps-0">
                                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                                </label>
                                            </div>
                                            <div class="">
                                                <p class="fw-medium mb-1 text-dark"> SMS </p>
                                                <label class="d-flex align-items-center form-switch ps-0">
                                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                                </label>
                                            </div>
                                            <div class="">
                                                <p class="fw-medium mb-1 text-dark"> In App </p>
                                                <label class="d-flex align-items-center form-switch ps-0">
                                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Items -->
                                    <div class="d-flex align-items-center justify-content-between flex-wrap row-gap-3 border mb-3 p-3">
                                        <div class="d-flex align-items-center">
                                            <span class="avatar avatar-lg border bg-light me-2">
                                                <i class="ti ti-calendar-time text-dark fs-16"></i>
                                            </span>
                                            <div>
                                                <h5 class="fs-14 fw-semibold mb-1">Lab Report Ready</h5>
                                                <p class="fs-13">Notify when test reports are available</p>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-4">
                                            <div class="">
                                                <p class="fw-medium mb-1 text-dark"> Email </p>
                                                <label class="d-flex align-items-center form-switch ps-0">
                                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                                </label>
                                            </div>
                                            <div class="">
                                                <p class="fw-medium mb-1 text-dark"> SMS </p>
                                                <label class="d-flex align-items-center form-switch ps-0">
                                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                                </label>
                                            </div>
                                            <div class="">
                                                <p class="fw-medium mb-1 text-dark"> In App </p>
                                                <label class="d-flex align-items-center form-switch ps-0">
                                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Items -->
                                    <div class="d-flex align-items-center justify-content-between flex-wrap row-gap-3 border mb-3 p-3">
                                        <div class="d-flex align-items-center">
                                            <span class="avatar avatar-lg border bg-light me-2">
                                                <i class="ti ti-activity-heartbeat text-dark fs-16"></i>
                                            </span>
                                            <div>
                                                <h5 class="fs-14 fw-semibold mb-1">Follow-up Reminders</h5>
                                                <p class="fs-13">Scheduled follow-ups from doctors</p>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-4">
                                            <div class="">
                                                <p class="fw-medium mb-1 text-dark"> Email </p>
                                                <label class="d-flex align-items-center form-switch ps-0">
                                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                                </label>
                                            </div>
                                            <div class="">
                                                <p class="fw-medium mb-1 text-dark"> SMS </p>
                                                <label class="d-flex align-items-center form-switch ps-0">
                                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                                </label>
                                            </div>
                                            <div class="">
                                                <p class="fw-medium mb-1 text-dark"> In App </p>
                                                <label class="d-flex align-items-center form-switch ps-0">
                                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Items -->
                                    <div class="d-flex align-items-center justify-content-between flex-wrap row-gap-3 border mb-3 p-3">
                                        <div class="d-flex align-items-center">
                                            <span class="avatar avatar-lg border bg-light me-2">
                                                <i class="ti ti-file-dollar text-dark fs-16"></i>
                                            </span>
                                            <div>
                                                <h5 class="fs-14 fw-semibold mb-1">Billing/Invoice Notification</h5>
                                                <p class="fs-13">Notify when a new bill or invoice is generated</p>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-4">
                                            <div class="">
                                                <p class="fw-medium mb-1 text-dark"> Email </p>
                                                <label class="d-flex align-items-center form-switch ps-0">
                                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                                </label>
                                            </div>
                                            <div class="">
                                                <p class="fw-medium mb-1 text-dark"> SMS </p>
                                                <label class="d-flex align-items-center form-switch ps-0">
                                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                                </label>
                                            </div>
                                            <div class="">
                                                <p class="fw-medium mb-1 text-dark"> In App </p>
                                                <label class="d-flex align-items-center form-switch ps-0">
                                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Items -->
                                    <div class="d-flex align-items-center justify-content-between flex-wrap row-gap-3 border mb-3 p-3">
                                        <div class="d-flex align-items-center">
                                            <span class="avatar avatar-lg border bg-light me-2">
                                                <i class="ti ti-alert-octagon text-dark fs-16"></i>
                                            </span>
                                            <div>
                                                <h5 class="fs-14 fw-semibold mb-1">System Alerts</h5>
                                                <p class="fs-13">Login attempts, data changes, or system updates.</p>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-4">
                                            <div class="">
                                                <p class="fw-medium mb-1 text-dark"> Email </p>
                                                <label class="d-flex align-items-center form-switch ps-0">
                                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                                </label>
                                            </div>
                                            <div class="">
                                                <p class="fw-medium mb-1 text-dark"> SMS </p>
                                                <label class="d-flex align-items-center form-switch ps-0">
                                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                                </label>
                                            </div>
                                            <div class="">
                                                <p class="fw-medium mb-1 text-dark"> In App </p>
                                                <label class="d-flex align-items-center form-switch ps-0">
                                                    <input class="form-check-input m-0" type="checkbox" checked="">
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                            </div>


                        </div> <!-- end col -->
                    </div>

                </div><!-- end card body -->
            </div>
            <!-- end card -->

        </div>
        <!-- End Content -->

        @component('components.footer')
        @endcomponent

    </div>

    <!-- ========================
        End Page Content
    ========================= -->

@endsection    