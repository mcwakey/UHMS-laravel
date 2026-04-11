<?php $page = 'patients-grid'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- ========================
        Start Page Content
    ========================= -->

    <div class="page-wrapper">

        <!-- Start Content -->
        <div class="content">

            <!-- Start Page Header -->
            <div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-4">
                <div class="flex-grow-1">
                    <h4 class="fw-bold mb-0">Patient Grid <span class="badge badge-soft-primary fw-medium border py-1 px-2 border-primary fs-13 ms-1">Total Patients : 565</span></h4>
                </div>
                <div class="text-end d-flex">

                    <!-- start filter -->
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
                                                <ul class="mb-3">
                                                    <li class="mb-1">
                                                        <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                            <input class="form-check-input m-0 me-2" type="checkbox">
                                                            Available
                                                        </label>
                                                    </li>
                                                    <li class="mb-0">
                                                        <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                            <input class="form-check-input m-0 me-2" type="checkbox">
                                                            Unavailable
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
                    <!-- end filter -->
                    
                    <div class="bg-white border shadow-sm rounded px-1 pb-0 text-center d-flex align-items-center justify-content-center">
                        <a href="{{url('patients')}}" class="bg-white rounded p-1 d-flex align-items-center justify-content-center"> <i class="ti ti-list fs-14 text-dark"></i></a>
                        <a href="{{url('patients-grid')}}" class="bg-light rounded p-1 d-flex align-items-center justify-content-center"> <i class="ti ti-layout-grid fs-14 text-body"></i> </a>
                    </div>

                    <a href="{{url('create-patient')}}" class="btn btn-primary ms-2 fs-13 btn-md"><i class="ti ti-plus me-1"></i>New Patient</a>
                </div>
            </div>
            <!-- End Page Header -->

            <!-- row start -->
            <div class="row">

                <div class="col-xl-4 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center">
                                    <a href="{{url('patient-details')}}" class="avatar avatar-lg me-2">
                                        <img src="{{URL::asset('build/img/users/user-08.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="{{url('patient-details')}}" class="text-dark fw-semibold">Alberto Ripley <span class="text-body fs-13 fw-normal d-block"> 26, Male </span>  </a>
                                </div>
                                <a href="javascript:void(0);" class="shadow-sm fs-14 d-inline-flex border rounded-2 p-1" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="{{url('edit-patient')}}" class="dropdown-item d-flex align-items-center">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                    <li>
                                        <a href="{{url('appointments')}}" class="dropdown-item d-flex align-items-center">Appointment</a>
                                    </li>
                                </ul>
                            </div>
                            <p class="mb-2 text-truncate fs-13 d-flex align-items-center"><i class="ti ti-calendar me-1 text-dark"></i>Last Appointment : Mon, 30 Apr 2025</p>
                            <p class="mb-0 text-truncate fs-13 d-flex align-items-center"><i class="ti ti-location-pin me-1 text-dark"></i>Green Square, New York, USA</p>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center">
                                    <a href="{{url('patient-details')}}" class="avatar avatar-lg me-2">
                                        <img src="{{URL::asset('build/img/users/user-16.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="{{url('patient-details')}}" class="text-dark fw-semibold">Susan Babin <span class="text-body fs-13 fw-normal d-block"> 21, Female </span></a>
                                </div>
                                <a href="javascript:void(0);" class="shadow-sm fs-14 d-inline-flex border rounded-2 p-1" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="{{url('edit-patient')}}" class="dropdown-item d-flex align-items-center">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                    <li>
                                        <a href="{{url('appointments')}}" class="dropdown-item d-flex align-items-center">Appointment</a>
                                    </li>
                                </ul>
                            </div>
                            <p class="mb-2 text-truncate fs-13 d-flex align-items-center"><i class="ti ti-calendar me-1 text-dark"></i>Last Appointment : Wed, 15 Apr 2025</p>
                            <p class="mb-0 text-truncate fs-13 d-flex align-items-center"><i class="ti ti-location-pin me-1 text-dark"></i>Lakeview Drive, Chicago, USA</p>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center">
                                    <a href="{{url('patient-details')}}" class="avatar avatar-lg me-2">
                                        <img src="{{URL::asset('build/img/users/user-06.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="{{url('patient-details')}}" class="text-dark fw-semibold">Carol Lam <span class="text-body fs-13 fw-normal d-block"> 28, Female </span></a>
                                </div>
                                <a href="javascript:void(0);" class="shadow-sm fs-14 d-inline-flex border rounded-2 p-1" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="{{url('edit-patient')}}" class="dropdown-item d-flex align-items-center">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                    <li>
                                        <a href="{{url('appointments')}}" class="dropdown-item d-flex align-items-center">Appointment</a>
                                    </li>
                                </ul>
                            </div>
                            <p class="mb-2 text-truncate fs-13 d-flex align-items-center"><i class="ti ti-calendar me-1 text-dark"></i>Last Appointment : Tue, 02 Apr 2025</p>
                            <p class="mb-0 text-truncate fs-13 d-flex align-items-center"><i class="ti ti-location-pin me-1 text-dark"></i>Ocean Avenue, Miami, USA</p>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center">
                                    <a href="{{url('patient-details')}}" class="avatar avatar-lg me-2">
                                        <img src="{{URL::asset('build/img/users/user-25.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="{{url('patient-details')}}" class="text-dark fw-semibold">Marsha Noland <span class="text-body fs-13 fw-normal d-block"> 25, Female </span></a>
                                </div>
                                <a href="javascript:void(0);" class="shadow-sm fs-14 d-inline-flex border rounded-2 p-1" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="{{url('edit-patient')}}" class="dropdown-item d-flex align-items-center">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                    <li>
                                        <a href="{{url('appointments')}}" class="dropdown-item d-flex align-items-center">Appointment</a>
                                    </li>
                                </ul>
                            </div>
                            <p class="mb-2 text-truncate fs-13 d-flex align-items-center"><i class="ti ti-calendar me-1 text-dark"></i>Last Appointment : Thu, 27 Mar 2025</p>
                            <p class="mb-0 text-truncate fs-13 d-flex align-items-center"><i class="ti ti-location-pin me-1 text-dark"></i>Elm Road, Austin, USA</p>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center">
                                    <a href="{{url('patient-details')}}" class="avatar avatar-lg me-2">
                                        <img src="{{URL::asset('build/img/users/user-39.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="{{url('patient-details')}}" class="text-dark fw-semibold">Irma Armstrong <span class="text-body fs-13 fw-normal d-block"> 32, Female </span></a>
                                </div>
                                <a href="javascript:void(0);" class="shadow-sm fs-14 d-inline-flex border rounded-2 p-1" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="{{url('edit-patient')}}" class="dropdown-item d-flex align-items-center">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                    <li>
                                        <a href="{{url('appointments')}}" class="dropdown-item d-flex align-items-center">Appointment</a>
                                    </li>
                                </ul>
                            </div>
                            <p class="mb-2 text-truncate fs-13 d-flex align-items-center"><i class="ti ti-calendar me-1 text-dark"></i>Last Appointment : Thu, 12 Mar 2025</p>
                            <p class="mb-0 text-truncate fs-13 d-flex align-items-center"><i class="ti ti-location-pin me-1 text-dark"></i>Elm Road, Austin, USA</p>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center">
                                    <a href="{{url('patient-details')}}" class="avatar avatar-lg me-2">
                                        <img src="{{URL::asset('build/img/users/user-14.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="{{url('patient-details')}}" class="text-dark fw-semibold">Jesus Adams <span class="text-body fs-13 fw-normal d-block"> 27, Male </span></a>
                                </div>
                                <a href="javascript:void(0);" class="shadow-sm fs-14 d-inline-flex border rounded-2 p-1" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="{{url('edit-patient')}}" class="dropdown-item d-flex align-items-center">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                    <li>
                                        <a href="{{url('appointments')}}" class="dropdown-item d-flex align-items-center">Appointment</a>
                                    </li>
                                </ul>
                            </div>
                            <p class="mb-2 text-truncate fs-13 d-flex align-items-center"><i class="ti ti-calendar me-1 text-dark"></i>Last Appointment : Fri, 05 Mar 2025</p>
                            <p class="mb-0 text-truncate fs-13 d-flex align-items-center"><i class="ti ti-location-pin me-1 text-dark"></i>Maple Street, San Francisco, USA</p>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center">
                                    <a href="{{url('patient-details')}}" class="avatar avatar-lg me-2">
                                        <img src="{{URL::asset('build/img/profiles/avatar-27.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="{{url('patient-details')}}" class="text-dark fw-semibold">Ezra Belcher <span class="text-body fs-13 fw-normal d-block"> 28, Male</span></a>
                                </div>
                                <a href="javascript:void(0);" class="shadow-sm fs-14 d-inline-flex border rounded-2 p-1" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="{{url('edit-patient')}}" class="dropdown-item d-flex align-items-center">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                    <li>
                                        <a href="{{url('appointments')}}" class="dropdown-item d-flex align-items-center">Appointment</a>
                                    </li>
                                </ul>
                            </div>
                            <p class="mb-2 text-truncate fs-13 d-flex align-items-center"><i class="ti ti-calendar me-1 text-dark"></i>Last Appointment : Sat, 24 Feb 2025</p>
                            <p class="mb-0 text-truncate fs-13 d-flex align-items-center"><i class="ti ti-location-pin me-1 text-dark"></i>Pine Valley, Seattle, USA</p>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center">
                                    <a href="{{url('patient-details')}}" class="avatar avatar-lg me-2">
                                        <img src="{{URL::asset('build/img/users/user-05.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="{{url('patient-details')}}" class="text-dark fw-semibold">Glen Lentz <span class="text-body fs-13 fw-normal d-block"> 22, Male </span></a>
                                </div>
                                <a href="javascript:void(0);" class="shadow-sm fs-14 d-inline-flex border rounded-2 p-1" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="{{url('edit-patient')}}" class="dropdown-item d-flex align-items-center">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                    <li>
                                        <a href="{{url('appointments')}}" class="dropdown-item d-flex align-items-center">Appointment</a>
                                    </li>
                                </ul>
                            </div>
                            <p class="mb-2 text-truncate fs-13 d-flex align-items-center"><i class="ti ti-calendar me-1 text-dark"></i>Last Appointment : Sat, 16 Feb 2025</p>
                            <p class="mb-0 text-truncate fs-13 d-flex align-items-center"><i class="ti ti-location-pin me-1 text-dark"></i>Pine Valley, Seattle, USA</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-4 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center">
                                    <a href="{{url('patient-details')}}" class="avatar avatar-lg me-2">
                                        <img src="{{URL::asset('build/img/users/user-07.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="{{url('patient-details')}}" class="text-dark fw-semibold">Bernard Griffith <span class="text-body fs-13 fw-normal d-block"> 34, Male </span></a>
                                </div>
                                <a href="javascript:void(0);" class="shadow-sm fs-14 d-inline-flex border rounded-2 p-1" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="{{url('edit-patient')}}" class="dropdown-item d-flex align-items-center">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                    <li>
                                        <a href="{{url('appointments')}}" class="dropdown-item d-flex align-items-center">Appointment</a>
                                    </li>
                                </ul>
                            </div>
                            <p class="mb-2 text-truncate fs-13 d-flex align-items-center"><i class="ti ti-calendar me-1 text-dark"></i>Last Appointment : Tue, 01 Feb 2025</p>
                            <p class="mb-0 text-truncate fs-13 d-flex align-items-center"><i class="ti ti-location-pin me-1 text-dark"></i>River Walk, Houston, USA</p>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center">
                                    <a href="{{url('patient-details')}}" class="avatar avatar-lg me-2">
                                        <img src="{{URL::asset('build/img/users/user-17.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="{{url('patient-details')}}" class="text-dark fw-semibold">John Elsass <span class="text-body fs-13 fw-normal d-block"> 23, Male </span></a>
                                </div>
                                <a href="javascript:void(0);" class="shadow-sm fs-14 d-inline-flex border rounded-2 p-1" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="{{url('edit-patient')}}" class="dropdown-item d-flex align-items-center">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                    <li>
                                        <a href="{{url('appointments')}}" class="dropdown-item d-flex align-items-center">Appointment</a>
                                    </li>
                                </ul>
                            </div>
                            <p class="mb-2 text-truncate fs-13 d-flex align-items-center"><i class="ti ti-calendar me-1 text-dark"></i>Last Appointment : Mon, 25 Jan 2025</p>
                            <p class="mb-0 text-truncate fs-13 d-flex align-items-center"><i class="ti ti-location-pin me-1 text-dark"></i>Forest Hill, Denver, USA</p>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center">
                                    <a href="{{url('patient-details')}}" class="avatar avatar-lg me-2">
                                        <img src="{{URL::asset('build/img/users/user-38.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="{{url('patient-details')}}" class="text-dark fw-semibold">Martin Lisa <span class="text-body fs-13 fw-normal d-block"> 26, Female </span></a>
                                </div>
                                <a href="javascript:void(0);" class="shadow-sm fs-14 d-inline-flex border rounded-2 p-1" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="{{url('edit-patient')}}" class="dropdown-item d-flex align-items-center">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                    <li>
                                        <a href="{{url('appointments')}}" class="dropdown-item d-flex align-items-center">Appointment</a>
                                    </li>
                                </ul>
                            </div>
                            <p class="mb-2 text-truncate fs-13 d-flex align-items-center"><i class="ti ti-calendar me-1 text-dark"></i>Last Appointment : Thu, 22 Jan 2025</p>
                            <p class="mb-0 text-truncate fs-13 d-flex align-items-center"><i class="ti ti-location-pin me-1 text-dark"></i>Garden Circle, Orlando, USA</p>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center">
                                    <a href="{{url('patient-details')}}" class="avatar avatar-lg me-2">
                                        <img src="{{URL::asset('build/img/users/user-37.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="{{url('patient-details')}}" class="text-dark fw-semibold">Ava Mitchell <span class="text-body fs-13 fw-normal d-block"> 25, Female </span></a>
                                </div>
                                <a href="javascript:void(0);" class="shadow-sm fs-14 d-inline-flex border rounded-2 p-1" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="{{url('edit-patient')}}" class="dropdown-item d-flex align-items-center">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                    <li>
                                        <a href="{{url('appointments')}}" class="dropdown-item d-flex align-items-center">Appointment</a>
                                    </li>
                                </ul>
                            </div>
                            <p class="mb-2 text-truncate fs-13 d-flex align-items-center"><i class="ti ti-calendar me-1 text-dark"></i>Last Appointment : Sat, 18 Jan 2025</p>
                            <p class="mb-0 text-truncate fs-13 d-flex align-items-center"><i class="ti ti-location-pin me-1 text-dark"></i>Crystal Court, Atlanta, USA</p>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center">
                                    <a href="{{url('patient-details')}}" class="avatar avatar-lg me-2">
                                        <img src="{{URL::asset('build/img/users/user-10.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="{{url('patient-details')}}" class="text-dark fw-semibold">Noah Davis <span class="text-body fs-13 fw-normal d-block"> 32, Male </span></a>
                                </div>
                                <a href="javascript:void(0);" class="shadow-sm fs-14 d-inline-flex border rounded-2 p-1" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="{{url('edit-patient')}}" class="dropdown-item d-flex align-items-center">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                    <li>
                                        <a href="{{url('appointments')}}" class="dropdown-item d-flex align-items-center">Appointment</a>
                                    </li>
                                </ul>
                            </div>
                            <p class="mb-2 text-truncate fs-13 d-flex align-items-center"><i class="ti ti-calendar me-1 text-dark"></i>Last Appointment : Wed, 15 Jan 2025</p>
                            <p class="mb-0 text-truncate fs-13 d-flex align-items-center"><i class="ti ti-location-pin me-1 text-dark"></i>Oakwood Street, Phoenix, USA</p>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center">
                                    <a href="{{url('patient-details')}}" class="avatar avatar-lg me-2">
                                        <img src="{{URL::asset('build/img/users/user-34.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="{{url('patient-details')}}" class="text-dark fw-semibold">Emily Ross <span class="text-body fs-13 fw-normal d-block"> 29, Female </span></a>
                                </div>
                                <a href="javascript:void(0);" class="shadow-sm fs-14 d-inline-flex border rounded-2 p-1" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="{{url('edit-patient')}}" class="dropdown-item d-flex align-items-center">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                    <li>
                                        <a href="{{url('appointments')}}" class="dropdown-item d-flex align-items-center">Appointment</a>
                                    </li>
                                </ul>
                            </div>
                            <p class="mb-2 text-truncate fs-13 d-flex align-items-center"><i class="ti ti-calendar me-1 text-dark"></i>Last Appointment : Fri, 10 Jan 2025</p>
                            <p class="mb-0 text-truncate fs-13 d-flex align-items-center"><i class="ti ti-location-pin me-1 text-dark"></i>Hilltop Lane, Dallas, USA</p>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center">
                                    <a href="{{url('patient-details')}}" class="avatar avatar-lg me-2">
                                        <img src="{{URL::asset('build/img/users/user-31.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="{{url('patient-details')}}" class="text-dark fw-semibold">Ryan Anderson <span class="text-body fs-13 fw-normal d-block"> 30, Male </span></a>
                                </div>
                                <a href="javascript:void(0);" class="shadow-sm fs-14 d-inline-flex border rounded-2 p-1" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="{{url('edit-patient')}}" class="dropdown-item d-flex align-items-center">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                    <li>
                                        <a href="{{url('appointments')}}" class="dropdown-item d-flex align-items-center">Appointment</a>
                                    </li>
                                </ul>
                            </div>
                            <p class="mb-2 text-truncate fs-13 d-flex align-items-center"><i class="ti ti-calendar me-1 text-dark"></i>Last Appointment : Tue, 04 Jan 2025</p>
                            <p class="mb-0 text-truncate fs-13 d-flex align-items-center"><i class="ti ti-location-pin me-1 text-dark"></i>Hilltop Lane, Dallas, USA</p>
                        </div>
                    </div>
                </div>

            </div>
            <!-- row end -->

            <div class="text-center">
                <a href="javascript:void(0);" class="btn btn-outline-white bg-white">Load More<i class="ti ti-loader-2 ms-1"></i></a>
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