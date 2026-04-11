<?php $page = 'patient-notifications'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- ========================
        Start Page Content
    ========================= -->
        
    <div class="page-wrapper">

        <!-- Start Content -->
        <div class="content">

            <!-- Start Page Header -->
            <div class="mb-3 pb-3 border-bottom mb-4">
                <h4 class="fw-bold mb-0">Notification</h4>
            </div>
            <!-- End Page Header -->

            
            <div class="row">

                <!-- Notifications -->
                <div class="col-xl-12">
                    <div class="notification-header">
                        <div class="form-sort form-wrap">
                            <div class="d-flex right-content align-items-center flex-wrap">
                                <div id="reportrange" class="reportrange-picker d-flex align-items-center">
                                    <i class="ti ti-calendar text-gray-5 fs-14 me-1"></i><span class="reportrange-picker-field">16 Apr 25 - 16 Apr 25</span>
                                </div>
                            </div>
                        </div>
                        <ul>
                            <li>
                                <a href="#" class="btn"><i class="feather-check"></i> Mark all as read</a>
                            </li>
                            <li>
                                <a href="#" class="btn btn-delete"><i class="feather-trash-2"></i> Delete all</a>
                            </li>
                        </ul>
                    </div>
                    <div class="notication-list">
                        <!-- Notification 1 -->
                        <div class="notication-item bg-white">
                            <div class="row align-items-center">
                                <div class="col-lg-9">
                                    <div class="notication-content">
                                        <span>
                                            <img src="{{URL::asset('build/img/doctors/doctor-01.jpg')}}" class="img-fluid" alt="img">
                                        </span>
                                        <div class="notication-info">
                                            <div>
                                                <p class="text-dark fw-semibold me-0">Dr. Smith</p>
                                                <p class="notify-time">updated the surgery schedule.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3">
                                    <div class="d-lg-flex align-items-center justify-content-between">
                                        <div class="noti-btn">
                                            <a href="javascript:void(0);" class="btn m-0">Decline</a>
                                            <a href="javascript:void(0);" class="btn btn-primary m-0">Accept</a>
                                        </div>
                                        <p class="m-0"> 2 mins ago </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Notification 2 -->
                        <div class="notication-item bg-white">
                            <div class="row align-items-center">
                                <div class="col-lg-9">
                                    <div class="notication-content">
                                        <span>
                                            <img src="{{URL::asset('build/img/doctors/doctor-06.jpg')}}" class="img-fluid" alt="img">
                                        </span>
                                        <div class="notication-info">
                                            <div>
                                                <p class="text-dark fw-semibold me-0">Dr. Patel</p>
                                                <p class="notify-time">completed a follow-up report for patient Emily.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3">
                                    <div class="d-lg-flex align-items-center justify-content-end">
                                        <div class="noti-btn me-3">
                                            <a href="javascript:void(0);" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#delete_notification">Delete</a>
                                        </div>
                                        <p class="m-0"> 2 mins ago </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Notification 3 -->
                        <div class="notication-item bg-white">
                            <div class="row align-items-center">
                                <div class="col-lg-9">
                                    <div class="notication-content">
                                        <span>
                                            <img src="{{URL::asset('build/img/doctors/doctor-02.jpg')}}" class="img-fluid" alt="img">
                                        </span>
                                        <div class="notication-info">
                                            <div>
                                                <p class="text-dark fw-semibold me-0">Emily</p>
                                                <p class="notify-time">booked an appointment with Dr. Patel for April 15</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3">
                                    <div class="d-lg-flex align-items-center justify-content-end">
                                        <div class="noti-btn me-3">
                                            <a href="javascript:void(0);" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#delete_notification">Delete</a>
                                        </div>
                                        <p class="m-0"> 2 mins ago </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Notification 4 -->
                        <div class="notication-item bg-white">
                            <div class="row align-items-center">
                                <div class="col-lg-9">
                                    <div class="notication-content">
                                        <span>
                                            <img src="{{URL::asset('build/img/doctors/doctor-07.jpg')}}" class="img-fluid" alt="img">
                                        </span>
                                        <div class="notication-info">
                                            <div>
                                                <p class="text-dark fw-semibold me-0">Amelia</p>
                                                <p class="notify-time">completed the pre-visit health questionnaire.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3">
                                    <div class="d-lg-flex align-items-center justify-content-end">
                                        <div class="noti-btn me-3">
                                            <a href="javascript:void(0);" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#delete_notification">Delete</a>
                                        </div>
                                        <p class="m-0"> 2 mins ago </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Notification 5 -->
                        <div class="notication-item bg-white">
                            <div class="row align-items-center">
                                <div class="col-lg-9">
                                    <div class="notication-content">
                                        <span>
                                            <img src="{{URL::asset('build/img/users/user-05.jpg')}}" class="img-fluid" alt="img">
                                        </span>
                                        <div class="notication-info">
                                            <div>
                                                <p class="text-dark fw-semibold me-0">Wick</p>
                                                <p class="notify-time">booked an appointment with Dr. smith for April 12</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3">
                                    <div class="d-lg-flex align-items-center justify-content-end">
                                        <div class="noti-btn me-3">
                                            <a href="javascript:void(0);" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#delete_notification">Delete</a>
                                        </div>
                                        <p class="m-0"> 2 mins ago </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                </div>
                <!-- Notifications -->

            </div>
                            
        </div>
        <!-- End Content -->

        @component('components.footer')
        @endcomponent

    </div>

    <!-- ========================
        End Page Content
    ========================= -->

@endsection    