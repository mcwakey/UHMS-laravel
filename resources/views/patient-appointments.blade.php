<?php $page = 'patient-appointments'; ?>
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
                    <h4 class="fw-bold mb-0"> Appointment </h4>
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
                    <div class="bg-white border shadow-sm rounded px-1 pb-0 text-center d-flex align-items-center justify-content-center">
                        <a href="{{url('patient-appointments')}}" class="bg-light rounded p-1 d-flex align-items-center justify-content-center"> <i class="ti ti-list fs-14 text-dark"></i></a>
                        <a href="{{url('patient-appointment-details')}}" class="bg-white rounded p-1 d-flex align-items-center justify-content-center"> <i class="ti ti-calendar-event fs-14 text-body"></i> </a>
                    </div>

                    <a href="javascript:void(0);" class="btn btn-primary ms-2 fs-13 btn-md" data-bs-toggle="offcanvas" data-bs-target="#new_appointment"><i class="ti ti-plus me-1"></i> New Appointment </a>
                </div>
            </div>
            <!-- End Page Header -->

            <!--  Start Filter -->
            <div class=" d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="search-set mb-3">
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <div class="table-search d-flex align-items-center mb-0">
                                <div class="search-input">
                                    <a href="javascript:void(0);" class="btn-searchset"></a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex right-content align-items-center flex-wrap mb-3">
                        <div class="input-icon-start position-relative">
                            <span class="input-icon-addon text-dark">
                                <i class="ti ti-calendar-event"></i>
                            </span>
                            <input type="text" class="form-control form-control-sm bookingrange">
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
                                            <label class="form-label mb-1">Doctor</label>
                                            <a href="javascript:void(0);" class="link-primary mb-1">Reset</a>
                                        </div>
                                        <select class="select2" multiple="multiple">
                                            <option value="m-1" selected>Dr. Mick Thompson</option>
                                            <option value="m-2">Dr. Sarah Johnson</option>
                                            <option value="m-3">Dr. Emily Carter</option>
                                            <option value="m-4">Dr. David Lee</option>
                                            <option value="m-5">Dr. Anna Kim</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <label class="form-label">Designation</label>
                                            <a href="javascript:void(0);" class="link-primary mb-1">Reset</a>
                                        </div>
                                        <select class="select2" multiple="multiple">
                                            <option value="m-1" selected>Cardiologist</option>
                                            <option value="m-2">Orthopedic Surgeon</option>
                                            <option value="m-3">Pediatrician</option>
                                            <option value="m-4">Gynecologist</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <label class="form-label">Department</label>
                                            <a href="javascript:void(0);" class="link-primary mb-1">Reset</a>
                                        </div>
                                        <select class="select2" multiple="multiple">
                                            <option value="m-1" selected>Cardiology</option>
                                            <option value="m-2">Orthopedics</option>
                                            <option value="m-3">Pediatrics</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label mb-1 text-dark fs-14 fw-medium">Date<span class="text-danger">*</span></label>
                                        <div class="input-icon-end position-relative">  
                                            <input type="text" class="form-control datetimepicker" placeholder="dd/mm/yyyy">
                                            <span class="input-icon-addon">
                                                <i class="ti ti-calendar"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <label class="form-label">Amount</label>
                                            <a href="javascript:void(0);" class="link-primary mb-1">Reset</a>
                                        </div>
                                        <select class="select2" multiple="multiple">
                                            <option value="m-1" selected>$501 - $1000</option>
                                            <option value="m-2">$501 - $1100</option>
                                            <option value="m-3">$701 - $1200</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <label class="form-label">Status</label>
                                            <a href="javascript:void(0);" class="link-primary mb-1">Reset</a>
                                        </div>
                                        <select class="select2" multiple="multiple">
                                            <option value="m-1" selected>Available</option>
                                            <option value="m-2">Unavailable</option>
                                        </select>
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
                                <a href="javascript:void(0);" class="dropdown-item rounded-1">Recently Added</a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1">Ascending</a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1">Desending</a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1">Last Month</a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1">Last 7 Days</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <!--  End Filter -->

            <!--  Start Table -->
            <div class="table-responsive">
                <table class="table table-nowrap datatable">
                    <thead class="thead-light">
                        <tr>
                            <th class="no-sort">
                                Date & Time
                            </th>
                            <th>Doctor Name</th>
                            <th>Mode</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>30 Apr 2025 - 09:30 AM</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="{{url('patients-doctor-details')}}" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/doctors/doctor-01.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="{{url('patients-doctor-details')}}" class="text-dark fw-semibold">Dr.Alberto Ripley <span class="text-body fs-13 fw-normal d-block">Cardiologist</span>  </a>
                                </div>
                            </td>
                            <td>
                                In-person
                            </td>
                            <td> <span class="badge badge-soft-primary rounded text-primary fw-medium fs-13">Checked Out</span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="offcanvas" data-bs-target="#edit_appointment">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="offcanvas" data-bs-target="#view_details">View</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        
                        <tr>
                            <td>15 Apr 2025 - 11:20 AM</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="{{url('patients-doctor-details')}}" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/doctors/doctor-02.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="{{url('patients-doctor-details')}}" class="text-dark fw-semibold">Dr.Susan Babin<span class="text-body fs-13 fw-normal d-block"> Orthopedic Surgeon</span>  </a>
                                </div>
                            </td>
                            <td>
                                Online
                            </td>
                            <td> <span class="badge badge-soft-warning rounded text-warning fw-medium fs-13">Checked In</span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="offcanvas" data-bs-target="#edit_appointment">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center"  data-bs-toggle="offcanvas" data-bs-target="#view_details">View</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>

                        <tr>
                            <td>02 Apr 2025 - 08:15 AM</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="{{url('patients-doctor-details')}}" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/doctors/doctor-03.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="{{url('patients-doctor-details')}}" class="text-dark fw-semibold">Dr.Carol Lam <span class="text-body fs-13 fw-normal d-block"> Pediatrician </span>  </a>
                                </div>
                            </td>
                            <td>
                                In-Person
                            </td>
                            <td> <span class="badge badge-soft-danger rounded text-danger fw-medium fs-13">Cancelled</span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="offcanvas" data-bs-target="#edit_appointment">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="offcanvas" data-bs-target="#view_details">View</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>

                        <tr>
                            <td>27 Mar 2025 - 02:00 PM</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="{{url('patients-doctor-details')}}" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/doctors/doctor-04.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="{{url('patients-doctor-details')}}" class="text-dark fw-semibold">Dr.Marsha Noland <span class="text-body fs-13 fw-normal d-block"> Gynecologist </span>  </a>
                                </div>
                            </td>
                            <td>
                                30 Apr 2025
                            </td>
                            <td> <span class="badge badge-soft-info rounded text-info fw-medium fs-13">Schedule</span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="offcanvas" data-bs-target="#edit_appointment">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="offcanvas" data-bs-target="#view_details">View</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>


                        <tr>
                            <td>12 Mar 2025 - 05:40 PM</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="{{url('patients-doctor-details')}}" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/doctors/doctor-04.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="{{url('patients-doctor-details')}}" class="text-dark fw-semibold">Dr.Irma Armstrong <span class="text-body fs-13 fw-regular d-block"> Psychiatrist </span>  </a>
                                </div>
                            </td>
                            <td>
                                Online
                            </td>
                            <td> <span class="badge badge-soft-success rounded text-success fw-medium fs-13">Confirmed</span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="offcanvas" data-bs-target="#edit_appointment">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="offcanvas" data-bs-target="#view_details">View</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>

                        <tr>
                            <td>24 Feb 2025 - 09:20 AM</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="{{url('patients-doctor-details')}}" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/doctors/doctor-05.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="{{url('patients-doctor-details')}}" class="text-dark fw-semibold">Dr.Ezra Belcher <span class="text-body fs-13 fw-regular d-block"> Neurosurgeon </span>  </a>
                                </div>
                            </td>
                            <td>
                                In-Person
                            </td>
                            <td> <span class="badge badge-soft-danger rounded text-danger fw-medium fs-13">Cancelled</span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="offcanvas" data-bs-target="#edit_appointment">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="offcanvas" data-bs-target="#view_details">View</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>

                        <tr>
                            <td>16 Feb 2025 - 11:40 AM</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="{{url('patients-doctor-details')}}" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/doctors/doctor-06.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="{{url('patients-doctor-details')}}" class="text-dark fw-semibold">Dr.Glen Lentz <span class="text-body fs-13 fw-regular d-block">Oncologist</span>  </a>
                                </div>
                            </td>
                            <td>
                                Online
                            </td>
                            <td> <span class="badge badge-soft-success rounded text-success fw-medium fs-13">Confirmed</span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="offcanvas" data-bs-target="#edit_appointment">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="offcanvas" data-bs-target="#view_details">View</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>

                        <tr>
                            <td>01 Feb 2025 - 04:00 PM</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="{{url('patients-doctor-details')}}" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/doctors/doctor-07.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="{{url('patients-doctor-details')}}" class="text-dark fw-semibold">Dr.Bernard Griffith <span class="text-body fs-13 fw-regular d-block"> Pulmonologist </span>  </a>
                                </div>
                            </td>
                            <td>
                                Online
                            </td>
                            <td> <span class="badge badge-soft-primary rounded text-primary fw-medium fs-13">Checked Out</span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="offcanvas" data-bs-target="#edit_appointment">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="offcanvas" data-bs-target="#view_details">View</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>

                        <tr>
                            <td>25 Jan 2025 - 03:10 PM</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="{{url('patients-doctor-details')}}" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/doctors/doctor-08.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="{{url('patients-doctor-details')}}" class="text-dark fw-semibold">Dr.John Elsass <span class="text-body fs-13 fw-regular d-block">Urologist</span>  </a>
                                </div>
                            </td>
                            <td>
                                Online
                            </td>
                            <td> <span class="badge badge-soft-info rounded text-info fw-medium fs-13">Schedule</span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="offcanvas" data-bs-target="#edit_appointment">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="offcanvas" data-bs-target="#view_details">View</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>

                        <tr>
                            <td>12 Jan 2025 - 03:10 PM</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="{{url('patients-doctor-details')}}" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/doctors/doctor-10.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="{{url('patients-doctor-details')}}" class="text-dark fw-semibold">Dr.John Albert <span class="text-body fs-13 fw-regular d-block">Cardiologist</span>  </a>
                                </div>
                            </td>
                            <td>
                                In-Person	
                            </td>
                            <td> <span class="badge badge-soft-danger rounded text-danger fw-medium fs-13">cancelled</span> </td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="offcanvas" data-bs-target="#edit_appointment">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="offcanvas" data-bs-target="#view_details">View</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!--  End Table -->
            
        </div>
        <!-- End Content -->

        @component('components.footer')
        @endcomponent

    </div>

    <!-- ========================
        End Page Content
    ========================= -->

@endsection    