<?php $page = 'notifications-settings'; ?>
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
                                <h5 class="fw-bold">Notifications</h5>
                            </div><!-- end card header -->
                            <div class="card-body px-0 mx-3">
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
                                <div class="d-flex align-items-center justify-content-between flex-wrap row-gap-3 border mb-0 p-3">
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