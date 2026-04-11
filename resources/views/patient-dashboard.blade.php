<?php $page = 'patient-dashboard'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- ========================
        Start Page Content
    ========================= -->

    <div class="page-wrapper">

        <!-- Start Content -->
        <div class="content pb-0">

            <!-- Page Header -->
            <div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
                <div>
                    <h4 class="fw-bold mb-0">Patient Dashboard</h4>
                </div>
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <a href="javascript:void(0);" class="btn btn-primary d-inline-flex align-items-center" data-bs-toggle="offcanvas" data-bs-target="#new_appointment"><i class="ti ti-plus me-1"></i>New Appointment</a>
                </div>
            </div>
            <!-- End Page Header -->

            <!-- row start -->
            <div class="row">

                <!-- col start -->
                <div class="col-xl-3 col-md-6 d-flex">
                    <div class="card flex-fill w-100 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-4">
                            <span class="avatar bg-primary rounded-circle fs-20 d-inline-flex flex-shrink-0"><i class="ti ti-calendar-heart"></i></span>
                            <div class="ms-2">
                                <p class="mb-1 text-truncate">Total Appointments</p>
                                <h3 class="fw-bold mb-0">24</h3>
                            </div>
                            </div>
                            <div class="d-flex align-items-center">
                            <span class="badge fw-medium bg-success flex-shrink-0 me-2">+95%</span>
                            <p class="fs-13 mb-0">in last 7 Days </p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-xl-3 col-md-6 d-flex">
                    <div class="card flex-fill w-100 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-4">
                            <span class="avatar bg-danger rounded-circle fs-20 d-inline-flex flex-shrink-0"><i class="ti ti-users"></i></span>
                            <div class="ms-2">
                                <p class="mb-1 text-truncate">Online Consultations</p>
                                <h3 class="fw-bold mb-0">36</h3>
                            </div>
                            </div>
                            <div class="d-flex align-items-center">
                            <span class="badge fw-medium bg-danger flex-shrink-0 me-2">-15%</span>
                            <p class="fs-13 mb-0">in last 7 Days</p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-xl-3 col-md-6 d-flex">
                    <div class="card flex-fill w-100 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <p class="mb-1 text-truncate">Blood Pressure</p>
                                <span class="badge fw-medium bg-success flex-shrink-0 me-2">+95%</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <h3 class="fw-bold mb-0 me-1">89</h3>
                                <p class="mb-0">g/dl</p>
                            </div>
                            </div>
                            <div id="s-col-8" class="chart-set"></div>
                        </div>
                    </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-xl-3 col-md-6 d-flex">
                    <div class="card flex-fill w-100 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <p class="mb-1 text-truncate">Heart Rate</p>
                                <span class="badge fw-medium bg-success flex-shrink-0 me-2">+95%</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <h3 class="fw-bold mb-0 me-1">87</h3>
                                <p class="mb-0">bpm</p>
                            </div>
                            </div>
                            <div id="s-col-9" class="chart-set"></div>
                        </div>
                    </div>
                </div>
                <!-- col end -->

            </div>
            <!-- row start -->

            <!-- row start -->
            <div class="row">
                
                <!-- col start -->
                <div class="col-xl-4 col-lg-6 d-flex">
                    <div class="card shadow-sm flex-fill w-100">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="fw-bold mb-0">My Doctors</h5> 
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar me-2 flex-shrink-0">
                                        <img src="{{URL::asset('build/img/doctors/doctor-01.jpg')}}" alt="img" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="fs-14 mb-1 text-truncate"><a href="javascript:void(0);" class="fw-semibold">Dr. Mick Thompson</a></h6>
                                        <p class="mb-0 fs-13 text-truncate">Cardiologist</p>
                                    </div>
                                </div>
                                <span class="badge fw-medium badge-soft-danger border border-danger flex-shrink-0">20 Bookings</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar me-2 flex-shrink-0">
                                        <img src="{{URL::asset('build/img/doctors/doctor-02.jpg')}}" alt="img" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="fs-14 mb-1 text-truncate"><a href="javascript:void(0);" class="fw-semibold">Dr. Sarah Johnson</a></h6>
                                        <p class="mb-0 fs-13 text-truncate">Orthopedic Surgeon</p>
                                    </div>
                                </div>
                                <span class="badge fw-medium badge-soft-danger border border-danger flex-shrink-0">15 Bookings</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar me-2 flex-shrink-0">
                                        <img src="{{URL::asset('build/img/doctors/doctor-03.jpg')}}" alt="img" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="fs-14 mb-1 text-truncate"><a href="javascript:void(0);" class="fw-semibold">Dr. Emily Carter</a></h6>
                                        <p class="mb-0 fs-13 text-truncate">Pediatrician</p>
                                    </div>
                                </div>
                                <span class="badge fw-medium badge-soft-danger border border-danger flex-shrink-0">12 Bookings</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar me-2 flex-shrink-0">
                                        <img src="{{URL::asset('build/img/doctors/doctor-04.jpg')}}" alt="img" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="fs-14 mb-1 text-truncate"><a href="javascript:void(0);" class="fw-semibold">Dr. David Lee</a></h6>
                                        <p class="mb-0 fs-13 text-truncate">Gynecologist</p>
                                    </div>
                                </div>
                                <span class="badge fw-medium badge-soft-danger border border-danger flex-shrink-0">08 Bookings</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-0">
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar me-2 flex-shrink-0">
                                        <img src="{{URL::asset('build/img/doctors/doctor-05.jpg')}}" alt="img" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="fs-14 mb-1 text-truncate"><a href="javascript:void(0);" class="fw-semibold">Dr. Anna Kim</a></h6>
                                        <p class="mb-0 fs-13 text-truncate">Psychiatrist</p>
                                    </div>
                                </div>
                                <span class="badge fw-medium badge-soft-danger border border-danger flex-shrink-0">06 Bookings</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-xl-4 col-lg-6 d-flex">
                    <div class="card shadow-sm flex-fill w-100">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="fw-bold mb-0">Prescriptions</h5> 
                        </div>
                        <div class="card-body">
                            <div class="overflow-auto">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="d-flex align-items-center flex-shrink-0">
                                        <a href="javascript:void(0);" class="avatar me-2 flex-shrink-0 bg-light rounded-circle text-dark">
                                            <i class="ti ti-file-description text-body fs-20"></i>
                                        </a>
                                        <div>
                                            <h6 class="fs-14 mb-1 text-truncate"><a href="javascript:void(0);" class="fw-semibold">Cardiology Prescription</a></h6>
                                            <p class="mb-0 fs-13 text-truncate">20 Apr 2025</p>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <a href="javascript:void(0);" class="btn btn-outline-white d-inline-flex align-items-center shadow-sm me-2 p-1"><i class="ti ti-eye"></i></a>
                                        <a href="javascript:void(0);" class="btn btn-outline-white d-inline-flex align-items-center shadow-sm p-1"><i class="ti ti-download"></i></a>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="d-flex align-items-center flex-shrink-0">
                                        <a href="javascript:void(0);" class="avatar me-2 flex-shrink-0 bg-light rounded-circle text-dark">
                                            <i class="ti ti-file-description text-body fs-20"></i>
                                        </a>
                                        <div>
                                            <h6 class="fs-14 mb-1 text-truncate"><a href="javascript:void(0);" class="fw-semibold">Dentist Prescription</a></h6>
                                            <p class="mb-0 fs-13 text-truncate">25 Mar 2025</p>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <a href="javascript:void(0);" class="btn btn-outline-white d-inline-flex align-items-center shadow-sm me-2 p-1"><i class="ti ti-eye"></i></a>
                                        <a href="javascript:void(0);" class="btn btn-outline-white d-inline-flex align-items-center shadow-sm p-1"><i class="ti ti-download"></i></a>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="d-flex align-items-center flex-shrink-0">
                                        <a href="javascript:void(0);" class="avatar me-2 flex-shrink-0 bg-light rounded-circle text-dark">
                                            <i class="ti ti-file-description text-body fs-20"></i>
                                        </a>
                                        <div>
                                            <h6 class="fs-14 mb-1 text-truncate"><a href="javascript:void(0);" class="fw-semibold">Dentist Prescription</a></h6>
                                            <p class="mb-0 fs-13 text-truncate">16 Mar 2025</p>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <a href="javascript:void(0);" class="btn btn-outline-white d-inline-flex align-items-center shadow-sm me-2 p-1"><i class="ti ti-eye"></i></a>
                                        <a href="javascript:void(0);" class="btn btn-outline-white d-inline-flex align-items-center shadow-sm p-1"><i class="ti ti-download"></i></a>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="d-flex align-items-center flex-shrink-0">
                                        <a href="javascript:void(0);" class="avatar me-2 flex-shrink-0 bg-light rounded-circle text-dark">
                                            <i class="ti ti-file-description text-body fs-20"></i>
                                        </a>
                                        <div>
                                            <h6 class="fs-14 mb-1 text-truncate"><a href="javascript:void(0);" class="fw-semibold">Dentist Prescription</a></h6>
                                            <p class="mb-0 fs-13 text-truncate">12 Feb 2025</p>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <a href="javascript:void(0);" class="btn btn-outline-white d-inline-flex align-items-center shadow-sm me-2 p-1"><i class="ti ti-eye"></i></a>
                                        <a href="javascript:void(0);" class="btn btn-outline-white d-inline-flex align-items-center shadow-sm p-1"><i class="ti ti-download"></i></a>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between mb-0">
                                    <div class="d-flex align-items-center flex-shrink-0">
                                        <a href="javascript:void(0);" class="avatar me-2 flex-shrink-0 bg-light rounded-circle text-dark">
                                            <i class="ti ti-file-description text-body fs-20"></i>
                                        </a>
                                        <div>
                                            <h6 class="fs-14 mb-1 text-truncate"><a href="javascript:void(0);" class="fw-semibold">Cardiology Prescription</a></h6>
                                            <p class="mb-0 fs-13 text-truncate">04 Jan 2025</p>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <a href="javascript:void(0);" class="btn btn-outline-white d-inline-flex align-items-center shadow-sm me-2 p-1"><i class="ti ti-eye"></i></a>
                                        <a href="javascript:void(0);" class="btn btn-outline-white d-inline-flex align-items-center shadow-sm p-1"><i class="ti ti-download"></i></a>
                                    </div>
                                </div>
                            </div>                                
                        </div>
                    </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-xl-4 d-flex">
                    <div class="card shadow-sm flex-fill w-100">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="fw-bold mb-0">Recent Activity</h5> 
                        </div>
                        <div class="card-body">
                            <div class="recent-activity">
                                <div class="d-flex align-items-center mb-3">
                                    <span><i class="ti ti-point-filled fs-24 text-success"></i></span>
                                    <div class="ms-2">
                                        <p class="mb-1 text-truncate">Appointment with <a href="javascript:void(0);" class="fw-semibold">Primary Care Physician</a></p>
                                        <p class="fs-13 mb-0">24 Mar 2025, 10:55 AM</p>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center mb-3">
                                    <span><i class="ti ti-point-filled fs-24 text-danger"></i></span>
                                    <div class="ms-2">
                                        <p class="mb-1 text-truncate"><a href="javascript:void(0);" class="fw-semibold">Blood Pressure Check</a> (Home Monitoring)</p>
                                        <p class="fs-13 mb-0">24 Apr 2025, 11:00 AM</p>
                                    </div>
                                </div>    
                                <div class="d-flex align-items-center mb-3">
                                    <span><i class="ti ti-point-filled fs-24 text-warning"></i></span>
                                    <div class="ms-2">
                                        <p class="mb-1"><a href="javascript:void(0);" class="fw-semibold">Physical Therapy Session</a> Knee strengthening exercises</p>
                                        <p class="fs-13 mb-0">24 Apr 2025, 11:00 AM</p>
                                    </div>
                                </div>   
                                <div class="d-flex align-items-center mb-0">
                                    <span><i class="ti ti-point-filled fs-24 text-info"></i></span>
                                    <div class="ms-2">
                                        <p class="mb-1"><a href="javascript:void(0);" class="fw-semibold">Discuss dietary changes</a> to manage blood sugar levels and weight</p>
                                        <p class="fs-13 mb-0">24 Apr 2025, 11:00 AM</p>
                                    </div>
                                </div> 
                            </div>                           
                        </div>
                    </div>
                </div>
                <!-- col end -->

            </div>
            <!-- row end -->

            <!-- card start -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="fw-bold mb-0">Vitals</h5> 
                </div>
                <div class="card-body">

                    <!-- row start -->
                    <div class="row row-gap-3 row-cols-1 row-cols-xl-6 row-cols-md-3 row-cols-sm-2">

                        <!-- col start -->
                        <div class="col d-flex">
                            <div class="p-3 border shadow-sm flex-fill w-100 rounded-2">
                                <div class="d-flex align-items-center">
                                    <span class="avatar bg-primary rounded-circle flex-shrink-0"><img src="{{URL::asset('./build/img/icons/weight.svg')}}" alt="img" class="w-auto h-auto"></span>
                                    <div class="ms-1">
                                        <p class="mb-1">Weight</p>
                                        <p class="text-truncate"><span class="fs-18 fw-bold text-dark">100</span> Kg</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- col end -->

                        <!-- col start -->
                        <div class="col d-flex">
                            <div class="p-3 border shadow-sm flex-fill w-100 rounded-2">
                                <div class="d-flex align-items-center">
                                    <span class="avatar bg-primary rounded-circle flex-shrink-0"><img src="{{URL::asset('./build/img/icons/rotate-left.svg')}}" alt="img" class="w-auto h-auto"></span>
                                    <div class="ms-1">
                                        <p class="mb-1">Height</p>
                                        <p class="text-truncate"><span class="fs-18 fw-bold text-dark">154</span> Cm</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- col end -->

                        <!-- col start -->
                        <div class="col d-flex">
                            <div class="p-3 border shadow-sm flex-fill w-100 rounded-2">
                                <div class="d-flex align-items-center">
                                    <span class="avatar bg-primary rounded-circle flex-shrink-0"><img src="{{URL::asset('./build/img/icons/user-cirlce-add.svg')}}" alt="img" class="w-auto h-auto"></span>
                                    <div class="ms-1">
                                        <p class="mb-1">BMI</p>
                                        <p class="text-truncate"><span class="fs-18 fw-bold text-dark">19.2</span> kg/cm</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- col end -->

                        <!-- col start -->
                        <div class="col d-flex">
                            <div class="p-3 border shadow-sm flex-fill w-100 rounded-2">
                                <div class="d-flex align-items-center">
                                    <span class="avatar bg-primary rounded-circle flex-shrink-0"><img src="{{URL::asset('./build/img/icons/driver-2.svg')}}" alt="img" class="w-auto h-auto"></span>
                                    <div class="ms-1">
                                        <p class="mb-1">Pulse</p>
                                        <p class="text-truncate"><span class="fs-18 fw-bold text-dark">97%</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- col end -->

                        <!-- col start -->
                        <div class="col d-flex">
                            <div class="p-3 border shadow-sm flex-fill w-100 rounded-2">
                                <div class="d-flex align-items-center">
                                    <span class="avatar bg-primary rounded-circle flex-shrink-0"><img src="{{URL::asset('./build/img/icons/wind.svg')}}" alt="img" class="w-auto h-auto"></span>
                                    <div class="ms-1">
                                        <p class="mb-1">SPO2</p>
                                        <p class="text-truncate"><span class="fs-18 fw-bold text-dark">98%</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- col end -->

                        <!-- col start -->
                        <div class="col d-flex">
                            <div class="p-3 border shadow-sm flex-fill w-100 rounded-2">
                                <div class="d-flex align-items-center">
                                    <span class="avatar bg-primary rounded-circle flex-shrink-0"><img src="{{URL::asset('./build/img/icons/sun.svg')}}" alt="img" class="w-auto h-auto"></span>
                                    <div class="ms-1">
                                        <p class="mb-1 text-truncate">Temprature</p>
                                        <p class="text-truncate"><span class="fs-18 fw-bold text-dark">101</span> C</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- col end -->
    
                    </div>
                    <!-- row end -->

                </div>
            </div>
            <!-- card end -->

            <!-- row start -->
            <div class="row">

                <!-- col start -->
                <div class="col-lg-6 d-flex">
                    <div class="card shadow-sm flex-fill w-100">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="fw-bold mb-0">Consultation By Department</h5> 
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="btn btn-sm px-2 border shadow-sm btn-outline-white d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                    Monthly <i class="ti ti-chevron-down ms-1"></i>
                                </a>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="#">Monthly</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#">Weekly</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#">Yearly</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-body pb-0">
                            <div id="s-col-10" class="chart-set"></div>
                        </div>
                    </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-lg-6 d-flex">
                    <div class="card shadow-sm flex-fill w-100">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="fw-bold mb-0">Recent Transactions</h5> 
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="btn btn-sm px-2 border shadow-sm btn-outline-white d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                    Weekly <i class="ti ti-chevron-down ms-1"></i>
                                </a>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="#">Monthly</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#">Weekly</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#">Yearly</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-body">

                            <!-- Table start -->
                            <div class="table-responsive table-nowrap">
                                <table class="table">
                                    <tbody>
                                        <tr class="border">
                                            <td class="ps-0">
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="avatar me-2">
                                                        <img src="{{URL::asset('build/img/doctors/doctor-06.jpg')}}" alt="img" class="rounded-circle">
                                                    </a>
                                                    <div>
                                                        <h6 class="fs-14 mb-1"><a href="javascript:void(0);" class="fw-semibold">Dr. John Smith</a></h6>
                                                        <p class="mb-0 fs-13">Neurosurgeon</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <h6 class="fs-14 fw-semibold">Consultation Fees</h6>
                                                <p class="fs-13">$450</p>
                                            </td>
                                            <td class="pe-0 text-end"><span class="badge fs-13 py-1 badge-soft-success border border-success rounded text-success fw-medium">Success</span></td>
                                        </tr>
                                        <tr class="border">
                                            <td class="ps-0">
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="avatar me-2">
                                                        <img src="{{URL::asset('build/img/doctors/doctor-07.jpg')}}" alt="img" class="rounded-circle">
                                                    </a>
                                                    <div>
                                                        <h6 class="fs-14 mb-1"><a href="javascript:void(0);" class="fw-semibold">Dr. Lisa White</a></h6>
                                                        <p class="mb-0 fs-13">Oncologist</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <h6 class="fs-14 fw-semibold">Consultation Fees</h6>
                                                <p class="fs-13">$350</p>
                                            </td>
                                            <td class="pe-0 text-end"><span class="badge fs-13 py-1 badge-soft-success border border-success rounded text-success fw-medium">Success</span></td>
                                        </tr>
                                        <tr class="border">
                                            <td class="ps-0">
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="avatar me-2">
                                                        <img src="{{URL::asset('build/img/doctors/doctor-08.jpg')}}" alt="img" class="rounded-circle">
                                                    </a>
                                                    <div>
                                                        <h6 class="fs-14 mb-1"><a href="javascript:void(0);" class="fw-semibold">Dr. Patricia Brown</a></h6>
                                                        <p class="mb-0 fs-13">Pulmonologist</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <h6 class="fs-14 fw-semibold">Consultation Fees</h6>
                                                <p class="fs-13">$400</p>
                                            </td>
                                            <td class="pe-0 text-end"><span class="badge fs-13 py-1 badge-soft-danger border border-danger rounded text-danger fw-medium">Failed</span></td>
                                        </tr>
                                        <tr class="border">
                                            <td class="ps-0">
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="avatar me-2">
                                                        <img src="{{URL::asset('build/img/doctors/doctor-09.jpg')}}" alt="img" class="rounded-circle">
                                                    </a>
                                                    <div>
                                                        <h6 class="fs-14 mb-1"><a href="javascript:void(0);" class="fw-semibold">Dr. Rachel Green</a></h6>
                                                        <p class="mb-0 fs-13">Urologist</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <h6 class="fs-14 fw-semibold">Consultation Fees</h6>
                                                <p class="fs-13">$550</p>
                                            </td>
                                            <td class="pe-0 text-end"><span class="badge fs-13 py-1 badge-soft-success border border-success rounded text-success fw-medium">Success</span></td>
                                        </tr>
                                        <tr class="border">
                                            <td class="ps-0">
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="avatar me-2">
                                                        <img src="{{URL::asset('build/img/doctors/doctor-10.jpg')}}" alt="img" class="rounded-circle">
                                                    </a>
                                                    <div>
                                                        <h6 class="fs-14 mb-1"><a href="javascript:void(0);" class="fw-semibold">Dr. Michael Smith</a></h6>
                                                        <p class="mb-0 fs-13">Cardiologist</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <h6 class="fs-14 fw-semibold">Consultation Fees</h6>
                                                <p class="fs-13">$600</p>
                                            </td>
                                            <td class="pe-0 text-end"><span class="badge fs-13 py-1 badge-soft-success border border-success rounded text-success fw-medium">Success</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <!-- Table end -->

                        </div>
                    </div>
                </div>
                <!-- col end -->

            </div>
            <!-- row end -->

            <!-- card start -->
            <div class="card shadow-sm flex-fill w-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="fw-bold mb-0">Recent Appointments</h5> 
                    <div class="dropdown">
                        <a href="javascript:void(0);" class="btn btn-sm px-2 border shadow-sm btn-outline-white d-inline-flex align-items-center" data-bs-toggle="dropdown">
                            Weekly <i class="ti ti-chevron-down ms-1"></i>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="#">Monthly</a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#">Weekly</a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#">Yearly</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Table start -->
                    <div class="table-responsive table-nowrap">
                        <table class="table border">
                            <thead class="thead-light">
                                <tr>
                                    <th>Name & Designation</th>
                                    <th>Date & Time</th>
                                    <th>Consultation Fees</th>
                                    <th>Mode</th>
                                    <th>Status</th>                                        
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="javascript:void(0);" class="avatar me-2">
                                                <img src="{{URL::asset('build/img/doctors/doctor-01.jpg')}}" alt="img" class="rounded-circle">
                                            </a>
                                            <div>
                                                <h6 class="fs-14 mb-1"><a href="javascript:void(0);" class="fw-semibold">Dr. Mick Thompson</a></h6>
                                                <p class="mb-0 fs-13">Cardiologist</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>27 May 2025 - 09:30 AM</td>
                                    <td class="fw-semibold text-dark">$400</td>
                                    <td>Online</td>
                                    <td><span class="badge bg-success fw-medium">Checked Out</span></td>                                        
                                    <td>
                                        <a href="javascript:void(0);" class="shadow-sm fs-14 d-inline-flex border rounded-2 p-1 me-1">
                                            <i class="ti ti-calendar-plus"></i>
                                        </a>
                                        <a href="javascript:void(0);" data-bs-toggle="dropdown" class="shadow-sm fs-14 d-inline-flex border rounded-2 p-1">
                                            <i class="ti ti-dots-vertical"></i>
                                        </a>
                                        <ul class="dropdown-menu p-2">
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="offcanvas" data-bs-target="#edit_appointment"><i class="ti ti-edit me-2"></i>Edit</a>
                                            </li>
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash me-2"></i>Delete</a>
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="javascript:void(0);" class="avatar me-2">
                                                <img src="{{URL::asset('build/img/doctors/doctor-09.jpg')}}" alt="img" class="rounded-circle">
                                            </a>
                                            <div>
                                                <h6 class="fs-14 mb-1"><a href="javascript:void(0);" class="fw-semibold">Dr. Sarah Johnson</a></h6>
                                                <p class="mb-0 fs-13">Orthopedic Surgeon</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>26 May 2025 - 10:15 AM</td>
                                    <td class="fw-semibold text-dark">$370</td>
                                    <td>Online</td>
                                    <td><span class="badge bg-warning fw-medium">Checked in</span></td>                                        
                                    <td>
                                        <a href="javascript:void(0);" class="shadow-sm fs-14 d-inline-flex border rounded-2 p-1 me-1">
                                            <i class="ti ti-calendar-plus"></i>
                                        </a>
                                        <a href="javascript:void(0);" data-bs-toggle="dropdown" class="shadow-sm fs-14 d-inline-flex border rounded-2 p-1">
                                            <i class="ti ti-dots-vertical"></i>
                                        </a>
                                        <ul class="dropdown-menu p-2">
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="offcanvas" data-bs-target="#edit_appointment"><i class="ti ti-edit me-2"></i>Edit</a>
                                            </li>
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash me-2"></i>Delete</a>
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="javascript:void(0);" class="avatar me-2">
                                                <img src="{{URL::asset('build/img/doctors/doctor-03.jpg')}}" alt="img" class="rounded-circle">
                                            </a>
                                            <div>
                                                <h6 class="fs-14 mb-1"><a href="javascript:void(0);" class="fw-semibold">Dr. Emily Carter</a></h6>
                                                <p class="mb-0 fs-13">Pediatrician</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>25 May 2025 - 02:40 PM</td>
                                    <td class="fw-semibold text-dark">$450</td>
                                    <td>In-Person</td>
                                    <td><span class="badge bg-danger fw-medium">Cancelled</span></td>                                        
                                    <td>
                                        <a href="javascript:void(0);" class="shadow-sm fs-14 d-inline-flex border rounded-2 p-1 me-1">
                                            <i class="ti ti-calendar-plus"></i>
                                        </a>
                                        <a href="javascript:void(0);" data-bs-toggle="dropdown" class="shadow-sm fs-14 d-inline-flex border rounded-2 p-1">
                                            <i class="ti ti-dots-vertical"></i>
                                        </a>
                                        <ul class="dropdown-menu p-2">
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="offcanvas" data-bs-target="#edit_appointment"><i class="ti ti-edit me-2"></i>Edit</a>
                                            </li>
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash me-2"></i>Delete</a>
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="javascript:void(0);" class="avatar me-2">
                                                <img src="{{URL::asset('build/img/doctors/doctor-04.jpg')}}" alt="img" class="rounded-circle">
                                            </a>
                                            <div>
                                                <h6 class="fs-14 mb-1"><a href="javascript:void(0);" class="fw-semibold">Dr. David Lee</a></h6>
                                                <p class="mb-0 fs-13">Gynecologist</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>24 May 2025 - 11:30 AM</td>
                                    <td class="fw-semibold text-dark">$310</td>
                                    <td>In-Person</td>
                                    <td><span class="badge bg-info fw-medium">Schedule</span></td>                                        
                                    <td>
                                        <a href="javascript:void(0);" class="shadow-sm fs-14 d-inline-flex border rounded-2 p-1 me-1">
                                            <i class="ti ti-calendar-plus"></i>
                                        </a>
                                        <a href="javascript:void(0);" data-bs-toggle="dropdown" class="shadow-sm fs-14 d-inline-flex border rounded-2 p-1">
                                            <i class="ti ti-dots-vertical"></i>
                                        </a>
                                        <ul class="dropdown-menu p-2">
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="offcanvas" data-bs-target="#edit_appointment"><i class="ti ti-edit me-2"></i>Edit</a>
                                            </li>
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash me-2"></i>Delete</a>
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="javascript:void(0);" class="avatar me-2">
                                                <img src="{{URL::asset('build/img/doctors/doctor-05.jpg')}}" alt="img" class="rounded-circle">
                                            </a>
                                            <div>
                                                <h6 class="fs-14 mb-1"><a href="javascript:void(0);" class="fw-semibold">Dr. Anna Kim</a></h6>
                                                <p class="mb-0 fs-13">Psychiatrist</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>23 May 2025 - 04:10 PM</td>
                                    <td class="fw-semibold text-dark">$400</td>
                                    <td>Online</td>
                                    <td><span class="badge bg-info fw-medium">Schedule</span></td>                                        
                                    <td>
                                        <a href="javascript:void(0);" class="shadow-sm fs-14 d-inline-flex border rounded-2 p-1 me-1">
                                            <i class="ti ti-calendar-plus"></i>
                                        </a>
                                        <a href="javascript:void(0);" data-bs-toggle="dropdown" class="shadow-sm fs-14 d-inline-flex border rounded-2 p-1">
                                            <i class="ti ti-dots-vertical"></i>
                                        </a>
                                        <ul class="dropdown-menu p-2">
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="offcanvas" data-bs-target="#edit_appointment"><i class="ti ti-edit me-2"></i>Edit</a>
                                            </li>
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash me-2"></i>Delete</a>
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Table end -->
                </div>
            </div>
            <!-- card end -->
                            
        </div>
        <!-- End Content -->

        @component('components.footer')
        @endcomponent

    </div>

    <!-- ========================
        End Page Content
    ========================= -->

@endsection    