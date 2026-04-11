<?php $page = 'appointment-calendar'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- ========================
        Start Page Content
    ========================= -->

    <div class="page-wrapper">

        <!-- Start Content -->
        <div class="content">

            <!-- Start Page Header -->
            <div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-1 border-bottom">
                <div class="flex-grow-1">
                    <h4 class="fw-semibold mb-0"> Appointment </h4>
                </div>
                <div class="text-end d-flex">
                    <!-- dropdown-->
                    <div class="dropdown me-1">
                        <a href="javascript:void(0);" class="btn btn-md fs-14 fw-normal border bg-white rounded text-dark d-inline-flex align-items-center"  data-bs-toggle="dropdown">
                            Export<i class="ti ti-chevron-down ms-2"></i>
                        </a>
                        <ul class="dropdown-menu p-2">
                            <li>
                                <a class="dropdown-item" href="#">Download as PDF</a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#">Download as Excel</a>
                            </li>
                        </ul>
                    </div>
                    <div class="bg-white border rounded px-1 pb-0 text-center d-flex align-items-center shadow-sm justify-content-center">
                        <a href="{{url('appointments')}}" class="bg-white rounded p-1 d-flex align-items-center justify-content-center"> <i class="ti ti-list fs-14 text-body"></i></a>
                        <a href="{{url('appointment-calendar')}}" class="bg-light rounded p-1 d-flex align-items-center justify-content-center"> <i class="ti ti-calendar-event fs-14 text-body"></i> </a>
                    </div>

                    <a href="{{url('new-appointment')}}" class="btn btn-primary ms-2 fs-13 btn-md"><i class="ti ti-plus me-1"></i> New Appointment </a>
                </div>
            </div>
            <!-- End Page Header -->

            <!--  Start Filter -->
            <div class=" d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="d-flex right-content align-items-center flex-wrap mb-3">
                        <div id="reportrange" class="reportrange-picker d-flex align-items-center">
                            <i class="ti ti-calendar text-gray-5 fs-14 me-1"></i><span class="reportrange-picker-field">16 Apr 25 - 16 Apr 25</span>
                        </div>
                    </div>
                </div>

                <div class="d-flex table-dropdown mb-3 pb-1 right-content align-items-center flex-wrap row-gap-3">
                    <div class="dropdown me-2">
                        <a href="javascript:void(0);" class="bg-white border rounded btn btn-md text-dark fs-14 py-1 align-items-center d-flex fw-normal" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                            <i class="ti ti-filter text-gray-5 me-1"></i>Filters
                        </a>
                        <div class="dropdown-menu dropdown-lg dropdown-menu-end filter-dropdown p-0" id="filter-dropdown">
                            <div class="d-flex align-items-center justify-content-between border-bottom filter-header">
                                <h4 class="mb-0 fw-bold">Filter</h4>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="link-danger text-decoration-underline">Clear All</a>
                                </div>
                            </div>
                            <form action="#">
                                <div class="filter-body pb-0">
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <label class="form-label">Patient</label>
                                            <a href="javascript:void(0);" class="link-primary mb-1">Reset</a>
                                        </div>
                                        <div class="dropdown">
                                            <a href="javascript:void(0);" class="dropdown-toggle btn bg-white  d-flex align-items-center justify-content-start fs-13 p-2 fw-normal border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                                Select <i class="ti ti-chevron-down ms-auto"></i>
                                            </a>
                                            <div class="dropdown-menu shadow-lg w-100 dropdown-info p-3">
                                                <div class="mb-3">
                                                    <div class="input-icon-start input-icon position-relative">
                                                        <span class="input-icon-addon fs-12">
                                                            <i class="ti ti-search"></i>
                                                        </span>
                                                        <input type="text" class="form-control form-control-md" placeholder="Search">
                                                    </div>
                                                </div>
                                                <ul class="mb-3">
                                                    <li class="mb-1">
                                                        <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                            <input class="form-check-input m-0 me-2" type="checkbox">
                                                            <span class="avatar avatar-xs rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-33.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Alberto Ripley
                                                        </label>
                                                    </li>
                                                    <li class="mb-1">
                                                        <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                            <input class="form-check-input m-0 me-2" type="checkbox">
                                                            <span class="avatar avatar-xs rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-12.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Bernard Griffith
                                                        </label>
                                                    </li>
                                                    <li class="mb-1">
                                                        <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                            <input class="form-check-input m-0 me-2" type="checkbox">
                                                            <span class="avatar avatar-xs rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-02.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Carol Lam
                                                        </label>
                                                    </li>
                                                    <li>
                                                        <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                            <input class="form-check-input m-0 me-2" type="checkbox">
                                                            <span class="avatar avatar-xs rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-08.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Ezra Belcher
                                                        </label>
                                                    </li>
                                                </ul>
                                                <div class="row g-2">
                                                    <div class="col-6">
                                                        <a href="javascript:void(0);" class="btn btn-outline-white w-100 close-filter">Cancel</a>
                                                    </div>
                                                    <div class="col-6">
                                                        <a href="javascript:void(0);" class="btn btn-primary w-100">Select</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <label class="form-label">Doctor</label>
                                            <a href="javascript:void(0);" class="link-primary mb-1">Reset</a>
                                        </div>
                                        <div class="dropdown">
                                            <a href="javascript:void(0);" class="dropdown-toggle btn bg-white  d-flex align-items-center justify-content-start fs-13 p-2 fw-normal border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                                Select <i class="ti ti-chevron-down ms-auto"></i>
                                            </a>
                                            <div class="dropdown-menu shadow-lg w-100 dropdown-info p-3">
                                                <div class="mb-3">
                                                    <div class="input-icon-start input-icon position-relative">
                                                        <span class="input-icon-addon fs-12">
                                                            <i class="ti ti-search"></i>
                                                        </span>
                                                        <input type="text" class="form-control form-control-md" placeholder="Search">
                                                    </div>
                                                </div>
                                                <ul class="mb-3">
                                                    <li class="mb-1">
                                                        <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                            <input class="form-check-input m-0 me-2" type="checkbox">
                                                            <span class="avatar avatar-xs rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-01.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr. Mick Thompson
                                                        </label>
                                                    </li>
                                                    <li class="mb-1">
                                                        <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                            <input class="form-check-input m-0 me-2" type="checkbox">
                                                            <span class="avatar avatar-xs rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-02.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr. Sarah Johnson
                                                        </label>
                                                    </li>
                                                    <li class="mb-1">
                                                        <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                            <input class="form-check-input m-0 me-2" type="checkbox">
                                                            <span class="avatar avatar-xs rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-03.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr. Emily Carter
                                                        </label>
                                                    </li>
                                                    <li class="mb-1">
                                                        <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                            <input class="form-check-input m-0 me-2" type="checkbox">
                                                            <span class="avatar avatar-xs rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-04.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr. David Lee
                                                        </label>
                                                    </li>
                                                    <li class="mb-0">
                                                        <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                            <input class="form-check-input m-0 me-2" type="checkbox">
                                                            <span class="avatar avatar-xs rounded-circle me-2"><img src="{{URL::asset('build/img/doctors/doctor-05.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Dr. Anna Kim
                                                        </label>
                                                    </li>
                                                </ul>
                                                <div class="row g-2">
                                                    <div class="col-6">
                                                        <a href="javascript:void(0);" class="btn btn-outline-white w-100 close-filter">Cancel</a>
                                                    </div>
                                                    <div class="col-6">
                                                        <a href="javascript:void(0);" class="btn btn-primary w-100">Select</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <label class="form-label">Designation</label>
                                            <a href="javascript:void(0);" class="link-primary mb-1">Reset</a>
                                        </div>
                                        <div class="dropdown">
                                            <a href="javascript:void(0);" class="dropdown-toggle btn bg-white  d-flex align-items-center justify-content-start fs-13 p-2 fw-normal border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                                Select <i class="ti ti-chevron-down ms-auto"></i>
                                            </a>
                                            <div class="dropdown-menu shadow-lg w-100 dropdown-info p-3">
                                                <div class="mb-3">
                                                    <div class="input-icon-start input-icon position-relative">
                                                        <span class="input-icon-addon fs-12">
                                                            <i class="ti ti-search"></i>
                                                        </span>
                                                        <input type="text" class="form-control form-control-md" placeholder="Search">
                                                    </div>
                                                </div>
                                                <ul class="mb-3">
                                                    <li class="mb-1">
                                                        <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                            <input class="form-check-input m-0 me-2" type="checkbox">
                                                            Cardiologist
                                                        </label>
                                                    </li>
                                                    <li class="mb-1">
                                                        <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                            <input class="form-check-input m-0 me-2" type="checkbox">
                                                            Orthopedic Surgeon
                                                        </label>
                                                    </li>
                                                    <li class="mb-1">
                                                        <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                            <input class="form-check-input m-0 me-2" type="checkbox">
                                                            Pediatrician
                                                        </label>
                                                    </li>
                                                    <li class="mb-1">
                                                        <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                            <input class="form-check-input m-0 me-2" type="checkbox">
                                                            Gynecologist
                                                        </label>
                                                    </li>
                                                    <li class="mb-1">
                                                        <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                            <input class="form-check-input m-0 me-2" type="checkbox">
                                                            Psychiatrist
                                                        </label>
                                                    </li>
                                                    <li class="mb-1">
                                                        <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                            <input class="form-check-input m-0 me-2" type="checkbox">
                                                            Neurosurgeon
                                                        </label>
                                                    </li>
                                                    <li class="mb-1">
                                                        <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                            <input class="form-check-input m-0 me-2" type="checkbox">
                                                            Oncologist
                                                        </label>
                                                    </li>
                                                    <li class="mb-1">
                                                        <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                            <input class="form-check-input m-0 me-2" type="checkbox">
                                                            Pulmonologist
                                                        </label>
                                                    </li>
                                                    <li class="mb-1">
                                                        <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                            <input class="form-check-input m-0 me-2" type="checkbox">
                                                            Urologist
                                                        </label>
                                                    </li>
                                                    <li class="mb-1">
                                                        <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                            <input class="form-check-input m-0 me-2" type="checkbox">
                                                            Dermatologist
                                                        </label>
                                                    </li>
                                                </ul>
                                                <div class="row g-2">
                                                    <div class="col-6">
                                                        <a href="javascript:void(0);" class="btn btn-outline-white w-100 close-filter">Cancel</a>
                                                    </div>
                                                    <div class="col-6">
                                                        <a href="javascript:void(0);" class="btn btn-primary w-100">Select</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <label class="form-label">Mode</label>
                                            <a href="javascript:void(0);" class="link-primary mb-1">Reset</a>
                                        </div>
                                        <div class="dropdown">
                                            <a href="javascript:void(0);" class="dropdown-toggle btn bg-white  d-flex align-items-center justify-content-start fs-13 p-2 fw-normal border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                                Select <i class="ti ti-chevron-down ms-auto"></i>
                                            </a>
                                            <div class="dropdown-menu shadow-lg w-100 dropdown-info p-3">
                                                <div class="mb-3">
                                                    <div class="input-icon-start input-icon position-relative">
                                                        <span class="input-icon-addon fs-12">
                                                            <i class="ti ti-search"></i>
                                                        </span>
                                                        <input type="text" class="form-control form-control-md" placeholder="Search">
                                                    </div>
                                                </div>
                                                <ul class="mb-3">
                                                    <li class="mb-1">
                                                        <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                            <input class="form-check-input m-0 me-2" type="checkbox">
                                                            In Person
                                                        </label>
                                                    </li>
                                                    <li class="mb-0">
                                                        <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                            <input class="form-check-input m-0 me-2" type="checkbox">
                                                            Online
                                                        </label>
                                                    </li>
                                                </ul>
                                                <div class="row g-2">
                                                    <div class="col-6">
                                                        <a href="javascript:void(0);" class="btn btn-outline-white w-100 close-filter">Cancel</a>
                                                    </div>
                                                    <div class="col-6">
                                                        <a href="javascript:void(0);" class="btn btn-primary w-100">Select</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label mb-1 text-dark fs-14 fw-medium">Date<span class="text-danger">*</span></label>
                                        <div class="input-icon-end position-relative">  
                                            <input type="text" class="form-control bookingrange" placeholder="dd/mm/yyyy">
                                            <span class="input-icon-addon">
                                                <i class="ti ti-calendar"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <label class="form-label">Status</label>
                                            <a href="javascript:void(0);" class="link-primary mb-1">Reset</a>
                                        </div>
                                        <div class="dropdown">
                                            <a href="javascript:void(0);" class="dropdown-toggle btn bg-white  d-flex align-items-center justify-content-start fs-13 p-2 fw-normal border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                                Select <i class="ti ti-chevron-down ms-auto"></i>
                                            </a>
                                            <div class="dropdown-menu shadow-lg w-100 dropdown-info p-3">
                                                <div class="mb-3">
                                                    <div class="input-icon-start input-icon position-relative">
                                                        <span class="input-icon-addon fs-12">
                                                            <i class="ti ti-search"></i>
                                                        </span>
                                                        <input type="text" class="form-control form-control-md" placeholder="Search">
                                                    </div>
                                                </div>
                                                <ul class="mb-3">
                                                    <li class="mb-1">
                                                        <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                            <input class="form-check-input m-0 me-2" type="checkbox">
                                                            Checked Out
                                                        </label>
                                                    </li>
                                                    <li class="mb-0">
                                                        <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                            <input class="form-check-input m-0 me-2" type="checkbox">
                                                            Checked In
                                                        </label>
                                                    </li>
                                                    <li class="mb-0">
                                                        <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                            <input class="form-check-input m-0 me-2" type="checkbox">
                                                            Cancelled
                                                        </label>
                                                    </li>
                                                    <li class="mb-0">
                                                        <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                            <input class="form-check-input m-0 me-2" type="checkbox">
                                                            Schedule
                                                        </label>
                                                    </li>
                                                    <li class="mb-0">
                                                        <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                            <input class="form-check-input m-0 me-2" type="checkbox">
                                                            Confirmed
                                                        </label>
                                                    </li>
                                                </ul>
                                                <div class="row g-2">
                                                    <div class="col-6">
                                                        <a href="javascript:void(0);" class="btn btn-outline-white w-100 close-filter">Cancel</a>
                                                    </div>
                                                    <div class="col-6">
                                                        <a href="javascript:void(0);" class="btn btn-primary w-100">Select</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="filter-footer d-flex align-items-center justify-content-end border-top">
                                    <a href="javascript:void(0);" class="btn btn-light btn-md me-2 fw-medium" id="close-filter">Close</a>
                                    <button type="submit" class="btn btn-primary btn-md fw-medium">Filter</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="dropdown">
                        <a href="javascript:void(0);" class="dropdown-toggle btn bg-white btn-md d-inline-flex align-items-center fw-normal rounded border text-dark px-2 py-1 fs-14" data-bs-toggle="dropdown">
                            <span  class="me-1"> Sort By : </span>  Recent
                        </a>
                        <ul class="dropdown-menu  dropdown-menu-end p-2">
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1">Recent</a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1">Oldest</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <!--  End Filter -->

            <!-- start Card -->
            <div class="card mb-0">
                <div class="card-body">
                    <div id="calendar"></div>
                </div>
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