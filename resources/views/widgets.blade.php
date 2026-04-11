<?php $page = 'widgets'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- ========================
        Start Page Content
    ========================= -->

    <div class="page-wrapper">

        <!-- Start Content -->
        <div class="content">

            <!-- Page Header -->
            <div class="mb-4">
                <h4 class="mb-0 fw-bold">Widgets</h4>
            </div>
            <!-- End Page Header -->
            
            <!-- start row -->
            <div class="row">
                <div class="col-xl-3 col-md-6">
                    <div class="position-relative border card rounded-2 shadow-sm">
                        <img src="{{URL::asset('./build/img/bg/bg-01.svg')}}" alt="img" class="position-absolute start-0 top-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2 justify-content-between">
                                <span class="avatar bg-primary rounded-circle"><i class="ti ti-calendar-heart fs-24"></i></span>
                                <div class="text-end">
                                <span class="badge px-2 py-1 fs-12 fw-medium d-inline-flex mb-1 bg-success">+95%</span>
                                <p class="fs-13 mb-0">in last 7 Days </p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <p class="mb-1">Doctors</p>
                                    <h3 class="fw-bold mb-0">247</h3>
                                </div>
                                <div>
                                    <div id="s-col" class="chart-set"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- end col -->
                <div class="col-xl-3 col-md-6">
                    <div class="position-relative border card rounded-2 shadow-sm">
                        <img src="{{URL::asset('./build/img/bg/bg-02.svg')}}" alt="img" class="position-absolute start-0 top-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2 justify-content-between">
                                <span class="avatar bg-danger rounded-circle"><i class="ti ti-calendar-heart fs-24"></i></span>
                                <div class="text-end">
                                <span class="badge px-2 py-1 fs-12 fw-medium d-inline-flex mb-1 bg-success">+25%</span>
                                <p class="fs-13 mb-0">in last 7 Days </p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <p class="mb-1">Patients</p>
                                    <h3 class="fw-bold mb-0">4178</h3>
                                </div>
                                <div>
                                    <div id="s-col-2" class="chart-set"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- end col -->
                <div class="col-xl-3 col-md-6">
                    <div class="position-relative border card rounded-2 shadow-sm">
                        <img src="{{URL::asset('./build/img/bg/bg-03.svg')}}" alt="img" class="position-absolute start-0 top-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2 justify-content-between">
                                <span class="avatar bg-info rounded-circle"><i class="ti ti-calendar-heart fs-24"></i></span>
                                <div class="text-end">
                                <span class="badge px-2 py-1 fs-12 fw-medium d-inline-flex mb-1 bg-danger">-15%</span>
                                <p class="fs-13 mb-0">in last 7 Days </p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <p class="mb-1">Appointment</p>
                                    <h3 class="fw-bold mb-0">12178</h3>
                                </div>
                                <div>
                                    <div id="s-col-3" class="chart-set"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- end col -->
                <div class="col-xl-3 col-md-6">
                    <div class="position-relative border card rounded-2 shadow-sm">
                        <img src="{{URL::asset('./build/img/bg/bg-04.svg')}}" alt="img" class="position-absolute start-0 top-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2 justify-content-between">
                                <span class="avatar bg-success rounded-circle"><i class="ti ti-calendar-heart fs-24"></i></span>
                                <div class="text-end">
                                <span class="badge px-2 py-1 fs-12 fw-medium d-inline-flex mb-1 bg-success">+25%</span>
                                <p class="fs-13 mb-0">in last 7 Days </p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between overflow-hidden">
                                <div>
                                    <p class="mb-1">Revenue</p>
                                    <h3 class="fw-bold mb-0 text-truncate">$55,1240</h3>
                                </div>
                                <div>
                                    <div id="s-col-4" class="chart-set"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- end col -->
            </div>
                <!-- end row -->

            <!-- start row -->
            <div class="row">
                <!-- col start -->
                <div class="col-xl-4 d-flex">
                    <div class="card shadow-sm flex-fill w-100">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="fw-bold mb-0">Top 3 Departments</h5> 
                        <div class="dropdown">
                            <a href="javascript:void(0);" class="btn btn-sm px-2 border shadow-sm btn-outline-white d-inline-flex align-items-center"  data-bs-toggle="dropdown">
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
                        <div id="circle-chart" class="chart-set"></div>  
                        <div class="d-flex align-items-center flex-wrap justify-content-center gap-2 mt-3">
                            <p class="d-flex align-items-center mb-0 fs-13"><i class="ti ti-circle-filled text-info fs-10 me-1"></i><span class="text-dark fw-semibold me-1">214</span> Cardiology</p>
                            <p class="d-flex align-items-center mb-0 fs-13"><i class="ti ti-circle-filled text-purple fs-10 me-1"></i><span class="text-dark fw-semibold me-1">150</span> Dental</p>
                            <p class="d-flex align-items-center mb-0 fs-13"><i class="ti ti-circle-filled text-primary fs-10 me-1"></i><span class="text-dark fw-semibold me-1">121</span> Neurolgy</p>
                        </div>
                    </div>                        
                    </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-xl-4 col-lg-6 d-flex">
                <div class="card shadow-sm flex-fill w-100">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="fw-bold mb-0">Doctors Schedule</h5> 
                        <a href="{{url('doctor-schedule')}}" class="btn fw-normal btn-outline-white">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="row g-2 mb-4">
                            <div class="col d-flex border-end">
                                <div class="text-center flex-fill">
                                    <p class="mb-1">Available</p>
                                    <h3 class="fw-bold mb-0">48</h3>
                                </div>
                            </div>
                            <div class="col d-flex border-end">
                                <div class="text-center flex-fill">
                                    <p class="mb-1">Unavailable</p>
                                    <h3 class="fw-bold mb-0">28</h3>
                                </div>
                            </div>
                            <div class="col d-flex">
                                <div class="text-center flex-fill">
                                    <p class="mb-1">Leave</p>
                                    <h3 class="fw-bold mb-0">12</h3>
                                </div>
                            </div>
                        </div>
                        <div class="overflow-auto">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center">
                                    <a href="{{url('doctor-details')}}" class="avatar flex-shrink-0">
                                        <img src="{{URL::asset('build/img/doctors/doctor-02.jpg')}}" class="rounded-circle" alt="img">
                                    </a>
                                    <div class="ms-2 flex-shrink-0">
                                        <div>
                                            <h6 class="fw-semibold fs-14 text-truncate mb-1"><a href="{{url('doctor-details')}}">Dr. Sarah Johnson</a></h6>
                                            <p class="fs-13">Orthopedic Surgeon</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-shrink-0 ms-2">
                                    <a href="#" class="btn btn-primary btn-sm py-1 flex-shrink-0">Book Now</a> 
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center">
                                    <a href="{{url('doctor-details')}}" class="avatar flex-shrink-0">
                                        <img src="{{URL::asset('build/img/doctors/doctor-03.jpg')}}" class="rounded-circle" alt="img">
                                    </a>
                                    <div class="ms-2 flex-shrink-0">
                                        <div>
                                            <h6 class="fw-semibold fs-14 text-truncate mb-1"><a href="{{url('doctor-details')}}">Dr. Emily Carter</a></h6>
                                            <p class="fs-13">Pediatrician</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-shrink-0 ms-2">
                                    <a href="#" class="btn btn-primary btn-sm py-1">Book Now</a> 
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center">
                                    <a href="{{url('doctor-details')}}" class="avatar flex-shrink-0">
                                        <img src="{{URL::asset('build/img/doctors/doctor-04.jpg')}}" class="rounded-circle" alt="img">
                                    </a>
                                    <div class="ms-2 flex-shrink-0">
                                        <div>
                                            <h6 class="fw-semibold fs-14 text-truncate mb-1"><a href="{{url('doctor-details')}}">Dr. David Lee</a></h6>
                                            <p class="fs-13">Gynecologist</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-shrink-0 ms-2">
                                    <a href="#" class="btn btn-primary btn-sm py-1">Book Now</a> 
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-0">
                                <div class="d-flex align-items-center">
                                    <a href="{{url('doctor-details')}}" class="avatar flex-shrink-0">
                                        <img src="{{URL::asset('build/img/doctors/doctor-14.jpg')}}" class="rounded-circle" alt="img">
                                    </a>
                                    <div class="ms-2 flex-shrink-0">
                                        <div>
                                            <h6 class="fw-semibold fs-14 text-truncate mb-1"><a href="{{url('doctor-details')}}">Dr. Michael Smith</a></h6>
                                            <p class="fs-13">Cardiologist</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-shrink-0 ms-2">
                                    <a href="#" class="btn btn-primary btn-sm py-1">Book Now</a> 
                                </div>
                            </div>
                        </div>                            
                    </div>
                </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-xl-4 col-lg-6 d-flex">
                <div class="card shadow-sm flex-fill w-100">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="fw-bold mb-0">Income By Treatment</h5> 
                        <div class="dropdown">
                            <a href="javascript:void(0);" class="btn btn-sm px-2 border shadow-sm btn-outline-white d-inline-flex align-items-center"  data-bs-toggle="dropdown">
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
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <p class="fw-semibold mb-1 text-dark">Cardiology</p>
                                <p class="mb-0">4,556 Apointments</p>
                            </div>
                            <h6 class="fw-bold mb-0">$5,985</h6>
                        </div>
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <p class="fw-semibold mb-1 text-dark">Radiology</p>
                                <p class="mb-0">4,125 Apointments</p>
                            </div>
                            <h6 class="fw-bold mb-0">$5,194</h6>
                        </div>
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <p class="fw-semibold mb-1 text-dark">Dental Surgery</p>
                                <p class="mb-0">1,796 Apointments</p>
                            </div>
                            <h6 class="fw-bold mb-0">$2,716</h6>
                        </div>
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <p class="fw-semibold mb-1 text-dark">Orthopaedics</p>
                                <p class="mb-0">3,827 Apointments</p>
                            </div>
                            <h6 class="fw-bold mb-0">$4,682</h6>
                        </div>
                        <div class="d-flex align-items-center justify-content-between mb-0">
                            <div>
                                <p class="fw-semibold mb-1 text-dark">General Medicine</p>
                                <p class="mb-0">9,894 Apointments</p>
                            </div>
                            <h6 class="fw-bold mb-0">$9,450</h6>
                        </div>
                    </div>
                </div> 
                </div> 
                <!-- col end -->     
            </div>
            <!-- end row -->

            <!-- row start -->
            <div class="row">
                <div class="col-12 d-flex">
                    <div class="card shadow-sm flex-fill w-100">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="fw-bold mb-0">All Appointments</h5> 
                            <a href="{{url('appointments')}}" class="btn fw-normal btn-outline-white">View All</a>
                        </div>
                        <div class="card-body">
                            <!-- Table start -->
                            <div class="table-responsive table-nowrap">
                                <table class="table border">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Doctor</th>
                                            <th>Patient</th>
                                            <th>Date & Time</th>
                                            <th>Mode</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <a href="{{url('doctor-details')}}" class="avatar me-2">
                                                        <img src="{{URL::asset('build/img/doctors/doctor-06.jpg')}}" alt="img" class="rounded-circle">
                                                    </a>
                                                    <div>
                                                        <h6 class="fs-14 mb-1"><a href="{{url('doctor-details')}}" class="fw-semibold">Dr. John Smith</a></h6>
                                                        <p class="mb-0 fs-13">Neurosurgeon</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <a href="{{url('patient-details')}}" class="avatar me-2">
                                                        <img src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" alt="img" class="rounded-circle">
                                                    </a>
                                                    <div>
                                                        <h6 class="fs-14 mb-1"><a href="{{url('patient-details')}}" class="fw-medium">Jesus Adams</a></h6>
                                                        <p class="mb-0 fs-13">+1 41254 45214</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>28 May 2025 - 11:15 AM</td>
                                            <td>Online</td>
                                            <td><span class="badge fs-13 py-1 badge-soft-success border border-success rounded text-success fw-medium">Confirmed</span></td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <a href="{{url('doctor-details')}}" class="avatar me-2">
                                                        <img src="{{URL::asset('build/img/doctors/doctor-07.jpg')}}" alt="img" class="rounded-circle">
                                                    </a>
                                                    <div>
                                                        <h6 class="fs-14 mb-1"><a href="{{url('doctor-details')}}" class="fw-semibold">Dr. Lisa White</a></h6>
                                                        <p class="mb-0 fs-13">Oncologist</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <a href="{{url('patient-details')}}" class="avatar me-2">
                                                        <img src="{{URL::asset('build/img/profiles/avatar-27.jpg')}}" alt="img" class="rounded-circle">
                                                    </a>
                                                    <div>
                                                        <h6 class="fs-14 mb-1"><a href="{{url('patient-details')}}" class="fw-medium">Ezra Belcher</a></h6>
                                                        <p class="mb-0 fs-13">+1 65895 41247</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>29 May 2025 - 11:30 AM</td>
                                            <td>In-Person</td>
                                            <td><span class="badge fs-13 py-1 badge-soft-danger border border-danger rounded fw-medium">Cancelled</span></td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <a href="{{url('doctor-details')}}" class="avatar me-2">
                                                        <img src="{{URL::asset('build/img/doctors/doctor-10.jpg')}}" alt="img" class="rounded-circle">
                                                    </a>
                                                    <div>
                                                        <h6 class="fs-14 mb-1"><a href="{{url('doctor-details')}}" class="fw-semibold">Dr. Patricia Brown</a></h6>
                                                        <p class="mb-0 fs-13">Pulmonologist</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <a href="{{url('patient-details')}}" class="avatar me-2">
                                                        <img src="{{URL::asset('build/img/profiles/avatar-20.jpg')}}" alt="img" class="rounded-circle">
                                                    </a>
                                                    <div>
                                                        <h6 class="fs-14 mb-1"><a href="{{url('patient-details')}}" class="fw-medium">Glen Lentz</a></h6>
                                                        <p class="mb-0 fs-13">+1 62458 45845</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>30 May 2025 - 09:30 AM </td>
                                            <td>Online</td>
                                            <td><span class="badge fs-13 py-1 badge-soft-success border border-success rounded text-success fw-medium">Confirmed</span></td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <a href="{{url('doctor-details')}}" class="avatar me-2">
                                                        <img src="{{URL::asset('build/img/doctors/doctor-11.jpg')}}" alt="img" class="rounded-circle">
                                                    </a>
                                                    <div>
                                                        <h6 class="fs-14 mb-1"><a href="{{url('doctor-details')}}" class="fw-semibold">Dr. Rachel Green</a></h6>
                                                        <p class="mb-0 fs-13">Urologist</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <a href="{{url('patient-details')}}" class="avatar me-2">
                                                        <img src="{{URL::asset('build/img/profiles/avatar-06.jpg')}}" alt="img" class="rounded-circle">
                                                    </a>
                                                    <div>
                                                        <h6 class="fs-14 mb-1"><a href="{{url('patient-details')}}" class="fw-medium">Bernard Griffith</a></h6>
                                                        <p class="mb-0 fs-13">+1 61422 45214</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>30 May 2025 - 10:00 AM</td>
                                            <td>Online</td>
                                            <td><span class="badge fs-13 py-1 badge-soft-secondary border border-secondary rounded fw-medium">Checked Out</span></td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <a href="{{url('doctor-details')}}" class="avatar me-2">
                                                        <img src="{{URL::asset('build/img/doctors/doctor-14.jpg')}}" alt="img" class="rounded-circle">
                                                    </a>
                                                    <div>
                                                        <h6 class="fs-14 mb-1"><a href="{{url('doctor-details')}}" class="fw-semibold">Dr. Michael Smith</a></h6>
                                                        <p class="mb-0 fs-13">Cardiologist</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <a href="{{url('patient-details')}}" class="avatar me-2">
                                                        <img src="{{URL::asset('build/img/profiles/avatar-25.jpg')}}" alt="img" class="rounded-circle">
                                                    </a>
                                                    <div>
                                                        <h6 class="fs-14 mb-1"><a href="{{url('patient-details')}}" class="fw-medium">John Elsass</a></h6>
                                                        <p class="mb-0 fs-13">+1 47851 26371</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>30 May 2025 - 11:00 AM</td>
                                            <td>Online</td>
                                            <td><span class="badge fs-13 py-1 badge-soft-info border border-info rounded fw-medium">Schedule</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <!-- Table end -->
                        </div>
                    </div> 
                </div> 
            </div>
            <!-- row end -->

            <!-- row start -->
            <div class="row">
                <!-- col start -->
                <div class="col-xl-4 d-flex">
                    <div class="card shadow-sm flex-fill w-100">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="fw-bold mb-0">Top 5 Patients</h5> 
                            <a href="{{url('patients')}}" class="btn fw-normal btn-outline-white">View All</a>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center">
                                    <a href="{{url('patient-details')}}" class="avatar me-2 flex-shrink-0">
                                        <img src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" alt="img" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="fs-14 mb-1 text-truncate"><a href="{{url('patient-details')}}" class="fw-medium">Jesus Adams</a></h6>
                                        <p class="mb-0 fs-13 text-truncate">Total Paid : $6589</p>
                                    </div>
                                </div>
                                <span class="badge fw-medium badge-soft-primary border border-primary flex-shrink-0">80 Appointments</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center">
                                    <a href="{{url('patient-details')}}" class="avatar me-2 flex-shrink-0">
                                        <img src="{{URL::asset('build/img/profiles/avatar-27.jpg')}}" alt="img" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="fs-14 mb-1 text-truncate"><a href="{{url('patient-details')}}" class="fw-medium">Ezra Belcher</a></h6>
                                        <p class="mb-0 fs-13 text-truncate">Total Paid : $5632</p>
                                    </div>
                                </div>
                                <span class="badge fw-medium badge-soft-primary border border-primary flex-shrink-0">60 Appointments</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center">
                                    <a href="{{url('patient-details')}}" class="avatar me-2 flex-shrink-0">
                                        <img src="{{URL::asset('build/img/profiles/avatar-20.jpg')}}" alt="img" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="fs-14 mb-1 text-truncate"><a href="{{url('patient-details')}}" class="fw-medium">Glen Lentz</a></h6>
                                        <p class="mb-0 fs-13 text-truncate">Total Paid : $4125</p>
                                    </div>
                                </div>
                                <span class="badge fw-medium badge-soft-primary border border-primary flex-shrink-0">40 Appointments</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center">
                                    <a href="{{url('patient-details')}}" class="avatar me-2 flex-shrink-0">
                                        <img src="{{URL::asset('build/img/profiles/avatar-06.jpg')}}" alt="img" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="fs-14 mb-1 text-truncate"><a href="{{url('patient-details')}}" class="fw-medium">Bernard Griffith</a></h6>
                                        <p class="mb-0 fs-13 text-truncate">Total Paid : $3140</p>
                                    </div>
                                </div>
                                <span class="badge fw-medium badge-soft-primary border border-primary flex-shrink-0">25 Appointments</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-0">
                                <div class="d-flex align-items-center">
                                    <a href="{{url('patient-details')}}" class="avatar me-2 flex-shrink-0">
                                        <img src="{{URL::asset('build/img/profiles/avatar-25.jpg')}}" alt="img" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="fs-14 mb-1 text-truncate"><a href="{{url('patient-details')}}" class="fw-medium">John Elsass</a></h6>
                                        <p class="mb-0 fs-13 text-truncate">Total Paid : $2654</p>
                                    </div>
                                </div>
                                <span class="badge fw-medium badge-soft-primary border border-primary flex-shrink-0">25 Appointments</span>
                            </div>
                        </div>
                    </div> 
                </div>
                <!-- col end -->
                    
                <!-- col start -->
                <div class="col-xl-4 col-lg-6 d-flex">
                    <div class="card shadow-sm flex-fill w-100">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="fw-bold mb-0">Recent Transactions</h5> 
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="btn btn-sm px-2 border shadow-sm btn-outline-white d-inline-flex align-items-center"  data-bs-toggle="dropdown">
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
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar me-2 flex-shrink-0">
                                        <img src="{{URL::asset('build/img/icons/stripe.svg')}}" alt="img" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="fs-14 mb-1 text-truncate"><a href="javascript:void(0);" class="fw-semibold">General Check-up</a></h6>
                                        <p class="mb-0 fs-13 text-truncate"><a href="javascript:void(0);" class="link-primary">#INV5889</a></p>
                                    </div>
                                </div>
                                <span class="badge fw-medium bg-success flex-shrink-0">+ $234</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar me-2 flex-shrink-0">
                                        <img src="{{URL::asset('build/img/icons/paypal.svg')}}" alt="img" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="fs-14 mb-1 text-truncate"><a href="javascript:void(0);" class="fw-semibold">Online Consultation</a></h6>
                                        <p class="mb-0 fs-13 text-truncate"><a href="javascript:void(0);" class="link-primary">#INV7874</a></p>
                                    </div>
                                </div>
                                <span class="badge fw-medium bg-success flex-shrink-0">+ $234</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar me-2 flex-shrink-0">
                                        <img src="{{URL::asset('build/img/icons/stripe.svg')}}" alt="img" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="fs-14 mb-1 text-truncate"><a href="javascript:void(0);" class="fw-semibold">Purchase Product</a></h6>
                                        <p class="mb-0 fs-13 text-truncate"><a href="javascript:void(0);" class="link-primary">#INV4458</a></p>
                                    </div>
                                </div>
                                <span class="badge fw-medium bg-danger flex-shrink-0">- $69</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar me-2 flex-shrink-0">
                                        <img src="{{URL::asset('build/img/icons/paypal.svg')}}" alt="img" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="fs-14 mb-1 text-truncate"><a href="javascript:void(0);" class="fw-semibold">Online Consultation</a></h6>
                                        <p class="mb-0 fs-13 text-truncate"><a href="javascript:void(0);" class="link-primary">#INV5456</a></p>
                                    </div>
                                </div>
                                <span class="badge fw-medium bg-success flex-shrink-0">+ $234</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-0">
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar me-2 flex-shrink-0">
                                        <img src="{{URL::asset('build/img/icons/stripe.svg')}}" alt="img" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="fs-14 mb-1 text-truncate"><a href="javascript:void(0);" class="fw-semibold">Online Consultation</a></h6>
                                        <p class="mb-0 fs-13 text-truncate"><a href="javascript:void(0);" class="link-primary">#INV4557</a></p>
                                    </div>
                                </div>
                                <span class="badge fw-medium bg-success flex-shrink-0">+ $234</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-xl-4 col-lg-6 d-flex">
                    <div class="card shadow-sm flex-fill w-100">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="fw-bold mb-0">Leave Requests</h5> 
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="btn btn-sm px-2 border shadow-sm btn-outline-white d-inline-flex align-items-center"  data-bs-toggle="dropdown">
                                    Today <i class="ti ti-chevron-down ms-1"></i>
                                </a>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="#">Today</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#">This Week</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#">This Month</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3">
                                <div class="d-flex align-items-center">
                                    <a href="{{url('doctor-details')}}" class="avatar flex-shrink-0">
                                        <img src="{{URL::asset('build/img/profiles/avatar-16.jpg')}}" class="rounded-circle" alt="img">
                                    </a>
                                    <div class="ms-2">
                                        <div>
                                            <h6 class="fw-semibold text-truncate mb-1 fs-14"><a href="{{url('doctor-details')}}">James Allaire</a></h6>
                                            <p class="fs-13 mb-0 text-truncate">4 Days - Personal Reason</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="d-inline-flex bg-soft-danger text-danger p-2 rounded-circle"><i class="ti ti-x fw-bold"></i></a>
                                    <a href="javascript:void(0);" class="d-inline-flex ms-2 text-success p-2 bg-soft-success rounded-circle"><i class="ti ti-check fw-bold"></i></a>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <div class="d-flex align-items-center">
                                    <a href="{{url('doctor-details')}}" class="avatar flex-shrink-0">
                                        <img src="{{URL::asset('build/img/profiles/avatar-21.jpg')}}" class="rounded-circle" alt="img">
                                    </a>
                                    <div class="ms-2">
                                        <div>
                                            <h6 class="fw-semibold text-truncate mb-1 fs-14"><a href="{{url('doctor-details')}}">Esther Schmidt</a></h6>
                                            <p class="fs-13 mb-0 text-truncate">2 Days - Going to Hospital</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="d-inline-flex bg-soft-danger text-danger p-2 rounded-circle"><i class="ti ti-x fw-bold"></i></a>
                                    <a href="javascript:void(0);" class="d-inline-flex ms-2 text-success p-2 bg-soft-success rounded-circle"><i class="ti ti-check fw-bold"></i></a>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <div class="d-flex align-items-center">
                                    <a href="{{url('doctor-details')}}" class="avatar flex-shrink-0">
                                        <img src="{{URL::asset('build/img/doctors/doctor-03.jpg')}}" class="rounded-circle" alt="img">
                                    </a>
                                    <div class="ms-2">
                                        <div>
                                            <h6 class="fw-semibold text-truncate mb-1 fs-14"><a href="{{url('doctor-details')}}">Valerie Padgett</a></h6>
                                            <p class="fs-13 mb-0 text-truncate">1 Day - Changing Account</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="d-inline-flex bg-soft-danger text-danger p-2 rounded-circle"><i class="ti ti-x fw-bold"></i></a>
                                    <a href="javascript:void(0);" class="d-inline-flex ms-2 text-success p-2 bg-soft-success rounded-circle"><i class="ti ti-check fw-bold"></i></a>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <div class="d-flex align-items-center">
                                    <a href="{{url('doctor-details')}}" class="avatar flex-shrink-0">
                                        <img src="{{URL::asset('build/img/doctors/doctor-02.jpg')}}" class="rounded-circle" alt="img">
                                    </a>
                                    <div class="ms-2">
                                        <div>
                                            <h6 class="fw-semibold text-truncate mb-1 fs-14"><a href="{{url('doctor-details')}}">Diane Nash</a></h6>
                                            <p class="fs-13 mb-0 text-truncate">1 Day - Not Well</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="d-inline-flex bg-soft-danger text-danger p-2 rounded-circle"><i class="ti ti-x fw-bold"></i></a>
                                    <a href="javascript:void(0);" class="d-inline-flex ms-2 text-success p-2 bg-soft-success rounded-circle"><i class="ti ti-check fw-bold"></i></a>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mb-0">
                                <div class="d-flex align-items-center">
                                    <a href="{{url('doctor-details')}}" class="avatar flex-shrink-0">
                                        <img src="{{URL::asset('build/img/doctors/doctor-09.jpg')}}" class="rounded-circle" alt="img">
                                    </a>
                                    <div class="ms-2">
                                        <div>
                                            <h6 class="fw-semibold text-truncate mb-1 fs-14"><a href="{{url('doctor-details')}}">Sally Cavazos</a></h6>
                                            <p class="fs-13 mb-0 text-truncate">2 Days - Going to Checkup</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="d-inline-flex bg-soft-danger text-danger p-2 rounded-circle"><i class="ti ti-x fw-bold"></i></a>
                                    <a href="javascript:void(0);" class="d-inline-flex ms-2 text-success p-2 bg-soft-success rounded-circle"><i class="ti ti-check fw-bold"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- col end -->

            </div>
            <!-- row end -->

            <!-- row start -->
                <div class="row">

                <!-- col start -->
                <div class="col-xl-4 d-flex">
                    <div class="card shadow-sm flex-fill w-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div>
                                    <p class="mb-1">Total Appointments</p>
                                    <div class="d-flex align-items-center gap-1">
                                        <h3 class="fw-bold mb-0">658</h3>
                                        <span class="badge fw-medium bg-success flex-shrink-0">+95%</span>
                                    </div>
                                </div>
                                <span class="avatar border border-primary text-primary rounded-2 flex-shrink-0"><i class="ti ti-calendar-heart fs-20"></i></span>
                            </div>
                            <div class="d-flex align-items-end">
                                <div id="s-col-5" class="chart-set"></div>
                                <span class="badge fw-medium badge-soft-success flex-shrink-0 ms-2">+21% <i class="ti ti-arrow-up ms-1"></i></span>
                                <p class="ms-1 fs-13 text-truncate">in last 7 Days </p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-xl-4 d-flex">
                    <div class="card shadow-sm flex-fill w-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div>
                                    <p class="mb-1">Online Consultations</p>
                                    <div class="d-flex align-items-center gap-1">
                                        <h3 class="fw-bold mb-0">125</h3>
                                        <span class="badge fw-medium bg-danger flex-shrink-0">-15%</span>
                                    </div>
                                </div>
                                <span class="avatar border border-danger text-danger rounded-2 flex-shrink-0"><i class="ti ti-users fs-20"></i></span>
                            </div>
                            <div class="d-flex align-items-end">
                                <div id="s-col-6" class="chart-set"></div>
                                <span class="badge fw-medium badge-soft-danger flex-shrink-0 ms-2">+21% <i class="ti ti-arrow-down ms-1"></i></span>
                                <p class="ms-1 fs-13 text-truncate">in last 7 Days </p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-xl-4 d-flex">
                    <div class="card shadow-sm flex-fill w-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div>
                                    <p class="mb-1">Cancelled Appointments</p>
                                    <div class="d-flex align-items-center gap-1">
                                        <h3 class="fw-bold mb-0">35</h3>
                                        <span class="badge fw-medium bg-success flex-shrink-0">+45%</span>
                                    </div>
                                </div>
                                <span class="avatar border border-success text-success rounded-2 flex-shrink-0"><i class="ti ti-versions fs-20"></i></span>
                            </div>
                            <div class="d-flex align-items-end">
                                <div id="s-col-7" class="chart-set"></div>
                                <span class="badge fw-medium badge-soft-success flex-shrink-0 ms-2">+31% <i class="ti ti-arrow-up ms-1"></i></span>
                                <p class="ms-1 fs-13 text-truncate">in last 7 Days </p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- col end -->

                </div>
            <!-- row end -->

            <!-- row start -->
            <div class="row row-cols-1 row-cols-xl-6 row-cols-md-3 row-cols-sm-2">

                <!-- col start -->
                    <div class="col">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <span class="avatar bg-primary rounded-2 fs-20 d-inline-flex mb-2"><i class="ti ti-user"></i></span>
                            <p class="mb-1 text-truncate">Total Patient</p>
                            <h3 class="fw-bold mb-2">658</h3>
                            <p class="mb-0 text-success text-truncate">+31% Last Week</p>
                        </div>
                    </div>
                    </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <span class="avatar bg-secondary rounded-2 fs-20 d-inline-flex mb-2"><i class="ti ti-video"></i></span>
                            <p class="mb-1 text-truncate">Video Consultation</p>
                            <h3 class="fw-bold mb-2">256</h3>
                            <p class="mb-0 text-danger text-truncate">-21% Last Week</p>
                        </div>
                    </div>
                    </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <span class="avatar bg-success rounded-2 fs-20 d-inline-flex mb-2"><i class="ti ti-calendar-up"></i></span>
                            <p class="mb-1 text-truncate">Rescheduled</p>
                            <h3 class="fw-bold mb-2">141</h3>
                            <p class="mb-0 text-success text-truncate">+64% Last Week</p>
                        </div>
                    </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <span class="avatar bg-danger rounded-2 fs-20 d-inline-flex mb-2"><i class="ti ti-checklist"></i></span>
                            <p class="mb-1 text-truncate">Pre Visit Bookings</p>
                            <h3 class="fw-bold mb-2">524</h3>
                            <p class="mb-0 text-success text-truncate">+38% Last Week</p>
                        </div>
                    </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <span class="avatar bg-info rounded-2 fs-20 d-inline-flex mb-2"><i class="ti ti-calendar-share"></i></span>
                            <p class="mb-1 text-truncate">Walkin Bookings</p>
                            <h3 class="fw-bold mb-2">21</h3>
                            <p class="mb-0 text-success text-truncate">+95% Last Week</p>
                        </div>
                    </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <span class="avatar bg-soft-success text-success rounded-2 fs-20 d-inline-flex mb-2"><i class="ti ti-carousel-vertical"></i></span>
                            <p class="mb-1 text-truncate">Follow Ups</p>
                            <h3 class="fw-bold mb-2">451</h3>
                            <p class="mb-0 text-success text-truncate">+76% Last Week</p>
                        </div>
                    </div>
                </div>
                <!-- col end -->

            </div>
            <!-- row start -->

            <!-- row start -->
            <div class="row">
                <div class="col-12 d-flex">
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
                                            <th>Patient</th>
                                            <th>Date & Time</th>
                                            <th>Mode</th>
                                            <th>Status</th>
                                            <th>Consultation Fees</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <a href="{{url('patient-details')}}" class="avatar me-2">
                                                        <img src="{{URL::asset('build/img/profiles/avatar-06.jpg')}}" alt="img" class="rounded-circle">
                                                    </a>
                                                    <div>
                                                        <h6 class="fs-14 mb-1"><a href="{{url('patient-details')}}" class="fw-medium">Alberto Ripley</a></h6>
                                                        <p class="mb-0 fs-13">+1 56556 54565</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>27 May 2025 - 09:30 AM</td>
                                            <td>Online</td>
                                            <td><span class="badge bg-success fw-medium">Checked Out</span></td>
                                            <td class="fw-semibold text-dark">$400</td>
                                            <td>
                                                <a href="javascript:void(0);" class="shadow-sm fs-14 d-inline-flex border rounded-2 p-1 me-1">
                                                    <i class="ti ti-calendar-plus"></i>
                                                </a>
                                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="shadow-sm fs-14 d-inline-flex border rounded-2 p-1">
                                                    <i class="ti ti-dots-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu p-2">
                                                    <li>
                                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_appointment"><i class="ti ti-edit me-2"></i>Edit</a>
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
                                                    <a href="{{url('patient-details')}}" class="avatar me-2">
                                                        <img src="{{URL::asset('build/img/profiles/avatar-12.jpg')}}" alt="img" class="rounded-circle">
                                                    </a>
                                                    <div>
                                                        <h6 class="fs-14 mb-1"><a href="{{url('patient-details')}}" class="fw-medium">Susan Babin</a></h6>
                                                        <p class="mb-0 fs-13">+1 65658 95654</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>26 May 2025 - 10:15 AM</td>
                                            <td>Online</td>
                                            <td><span class="badge bg-warning fw-medium">Checked in</span></td>
                                            <td class="fw-semibold text-dark">$370</td>
                                            <td>
                                                <a href="javascript:void(0);" class="shadow-sm fs-14 d-inline-flex border rounded-2 p-1 me-1">
                                                    <i class="ti ti-calendar-plus"></i>
                                                </a>
                                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="shadow-sm fs-14 d-inline-flex border rounded-2 p-1">
                                                    <i class="ti ti-dots-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu p-2">
                                                    <li>
                                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_appointment"><i class="ti ti-edit me-2"></i>Edit</a>
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
                                                    <a href="{{url('patient-details')}}" class="avatar me-2">
                                                        <img src="{{URL::asset('build/img/profiles/avatar-08.jpg')}}" alt="img" class="rounded-circle">
                                                    </a>
                                                    <div>
                                                        <h6 class="fs-14 mb-1"><a href="{{url('patient-details')}}" class="fw-medium">Carol Lam</a></h6>
                                                        <p class="mb-0 fs-13">+1 55654 56647</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>25 May 2025 - 02:40 PM</td>
                                            <td>In-Person</td>
                                            <td><span class="badge bg-danger fw-medium">Cancelled</span></td>
                                            <td class="fw-semibold text-dark">$450</td>
                                            <td>
                                                <a href="javascript:void(0);" class="shadow-sm fs-14 d-inline-flex border rounded-2 p-1 me-1">
                                                    <i class="ti ti-calendar-plus"></i>
                                                </a>
                                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="shadow-sm fs-14 d-inline-flex border rounded-2 p-1">
                                                    <i class="ti ti-dots-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu p-2">
                                                    <li>
                                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_appointment"><i class="ti ti-edit me-2"></i>Edit</a>
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
                                                    <a href="{{url('patient-details')}}" class="avatar me-2">
                                                        <img src="{{URL::asset('build/img/profiles/avatar-22.jpg')}}" alt="img" class="rounded-circle">
                                                    </a>
                                                    <div>
                                                        <h6 class="fs-14 mb-1"><a href="{{url('patient-details')}}" class="fw-medium">Marsha Noland</a></h6>
                                                        <p class="mb-0 fs-13">+1 65668 54558</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>24 May 2025 - 11:30 AM</td>
                                            <td>In-Person</td>
                                            <td><span class="badge bg-info fw-medium">Schedule</span></td>
                                            <td class="fw-semibold text-dark">$310</td>
                                            <td>
                                                <a href="javascript:void(0);" class="shadow-sm fs-14 d-inline-flex border rounded-2 p-1 me-1">
                                                    <i class="ti ti-calendar-plus"></i>
                                                </a>
                                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="shadow-sm fs-14 d-inline-flex border rounded-2 p-1">
                                                    <i class="ti ti-dots-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu p-2">
                                                    <li>
                                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_appointment"><i class="ti ti-edit me-2"></i>Edit</a>
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
                                                    <a href="{{url('patient-details')}}" class="avatar me-2">
                                                        <img src="{{URL::asset('build/img/profiles/avatar-25.jpg')}}" alt="img" class="rounded-circle">
                                                    </a>
                                                    <div>
                                                        <h6 class="fs-14 mb-1"><a href="{{url('patient-details')}}" class="fw-medium">John Elsass</a></h6>
                                                        <p class="mb-0 fs-13">47851263</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>23 May 2025 - 04:10 PM</td>
                                            <td>Online</td>
                                            <td><span class="badge bg-info fw-medium">Schedule</span></td>
                                            <td class="fw-semibold text-dark">$400</td>
                                            <td>
                                                <a href="javascript:void(0);" class="shadow-sm fs-14 d-inline-flex border rounded-2 p-1 me-1">
                                                    <i class="ti ti-calendar-plus"></i>
                                                </a>
                                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="shadow-sm fs-14 d-inline-flex border rounded-2 p-1">
                                                    <i class="ti ti-dots-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu p-2">
                                                    <li>
                                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_appointment"><i class="ti ti-edit me-2"></i>Edit</a>
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
                </div> 
            </div>
            <!-- row end -->

            <!-- row start -->
            <div class="row">

                <!-- col start -->
                <div class="col-xl-4 d-flex">
                    <div class="card shadow-sm flex-fill w-100">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="fw-bold mb-0">Availability</h5> 
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="btn btn-sm px-2 border shadow-sm btn-outline-white d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                    Trustcare Clinic <i class="ti ti-chevron-down ms-1"></i>
                                </a>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0);">CureWell Medical Hub</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0);">Trustcare Clinic</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0);">NovaCare Medical</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0);">Greeny Medical Clinic</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-2 border-bottom pb-2">
                                <p class="text-dark fw-semibold mb-0">Mon</p>
                                <p class="mb-0 d-inline-flex align-items-center"><i class="ti ti-clock me-1"></i>11:00 PM - 12:30 PM</p>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-2 border-bottom pb-2">
                                <p class="text-dark fw-semibold mb-0">Tue</p>
                                <p class="mb-0 d-inline-flex align-items-center"><i class="ti ti-clock me-1"></i>11:00 PM - 12:30 PM</p>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-2 border-bottom pb-2">
                                <p class="text-dark fw-semibold mb-0">Wed</p>
                                <p class="mb-0 d-inline-flex align-items-center"><i class="ti ti-clock me-1"></i>11:00 PM - 12:30 PM</p>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-2 border-bottom pb-2">
                                <p class="text-dark fw-semibold mb-0">Thu</p>
                                <p class="mb-0 d-inline-flex align-items-center"><i class="ti ti-clock me-1"></i>11:00 PM - 12:30 PM</p>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-2 border-bottom pb-2">
                                <p class="text-dark fw-semibold mb-0">Fri</p>
                                <p class="mb-0 d-inline-flex align-items-center"><i class="ti ti-clock me-1"></i>11:00 PM - 12:30 PM</p>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-2 border-bottom pb-2">
                                <p class="text-dark fw-semibold mb-0">Sat</p>
                                <p class="mb-0 d-inline-flex align-items-center"><i class="ti ti-clock me-1"></i>11:00 PM - 12:30 PM</p>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-2 pb-2">
                                <p class="text-dark fw-semibold mb-0">Sun</p>
                                <p class="mb-0 d-inline-flex align-items-center text-danger"><i class="ti ti-clock me-1"></i>Closed</p>
                            </div>
                            <a href="javascript:void(0);" class="btn btn-light w-100 mt-2 fs-13">Edit Availability</a>
                        </div>
                    </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-xl-4 col-lg-6 d-flex">
                    <div class="card shadow-sm flex-fill w-100">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="fw-bold mb-0">Appointment Statistics</h5> 
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
                        <div class="card-body">
                            <div id="circle-chart-2" class="chart-set"></div> 
                            <div class="d-flex align-items-center flex-wrap justify-content-center gap-2 mt-3">
                                <div class="text-center">
                                    <p class="d-flex align-items-center mb-1 fs-13"><i class="ti ti-circle-filled text-success fs-10 me-1"></i>Completed</p>
                                    <h5 class="fw-bold mb-0">260</h5>
                                </div>
                                <div class="text-center">
                                    <p class="d-flex align-items-center mb-1 fs-13"><i class="ti ti-circle-filled text-warning fs-10 me-1"></i>Pending</p>
                                    <h5 class="fw-bold mb-0">21</h5>
                                </div>
                                <div class="text-center">
                                    <p class="d-flex align-items-center mb-1 fs-13"><i class="ti ti-circle-filled text-danger fs-10 me-1"></i>Cancelled</p>
                                    <h5 class="fw-bold mb-0">50</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-xl-4 col-lg-6 d-flex">
                    <div class="card shadow-sm flex-fill w-100">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="fw-bold mb-0">Top Patients</h5> 
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
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <div class="d-flex align-items-center">
                                    <a href="{{url('patient-details')}}" class="avatar me-2 flex-shrink-0">
                                        <img src="{{URL::asset('build/img/profiles/avatar-06.jpg')}}" alt="img" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="fs-14 mb-1 text-truncate"><a href="{{url('patient-details')}}" class="fw-medium">Alberto Ripley</a></h6>
                                        <p class="mb-0 fs-13 text-truncate">+1 56556 54565</p>
                                    </div>
                                </div>
                                <span class="badge fw-medium badge-soft-primary border border-primary flex-shrink-0">20 Appointments</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <div class="d-flex align-items-center">
                                    <a href="{{url('patient-details')}}" class="avatar me-2 flex-shrink-0">
                                        <img src="{{URL::asset('build/img/profiles/avatar-12.jpg')}}" alt="img" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="fs-14 mb-1 text-truncate"><a href="{{url('patient-details')}}" class="fw-medium">Susan Babin</a></h6>
                                        <p class="mb-0 fs-13 text-truncate">+1 65658 95654</p>
                                    </div>
                                </div>
                                <span class="badge fw-medium badge-soft-primary border border-primary flex-shrink-0">18 Appointments</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <div class="d-flex align-items-center">
                                    <a href="{{url('patient-details')}}" class="avatar me-2 flex-shrink-0">
                                        <img src="{{URL::asset('build/img/profiles/avatar-08.jpg')}}" alt="img" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="fs-14 mb-1 text-truncate"><a href="{{url('patient-details')}}" class="fw-medium">Carol Lam</a></h6>
                                        <p class="mb-0 fs-13 text-truncate">+1 55654 56647</p>
                                    </div>
                                </div>
                                <span class="badge fw-medium badge-soft-primary border border-primary flex-shrink-0">16 Appointments</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <div class="d-flex align-items-center">
                                    <a href="{{url('patient-details')}}" class="avatar me-2 flex-shrink-0">
                                        <img src="{{URL::asset('build/img/profiles/avatar-22.jpg')}}" alt="img" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="fs-14 mb-1 text-truncate"><a href="{{url('patient-details')}}" class="fw-medium">Marsha Noland</a></h6>
                                        <p class="mb-0 fs-13 text-truncate">+1 65668 54558</p>
                                    </div>
                                </div>
                                <span class="badge fw-medium badge-soft-primary border border-primary flex-shrink-0">14 Appointments</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-0">
                                <div class="d-flex align-items-center">
                                    <a href="{{url('patient-details')}}" class="avatar me-2 flex-shrink-0">
                                        <img src="{{URL::asset('build/img/profiles/avatar-17.jpg')}}" alt="img" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="fs-14 mb-1 text-truncate"><a href="{{url('patient-details')}}" class="fw-medium">Irma Armstrong</a></h6>
                                        <p class="mb-0 fs-13 text-truncate">+1 45214 66568</p>
                                    </div>
                                </div>
                                <span class="badge fw-medium badge-soft-primary border border-primary flex-shrink-0">12 Appointments</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- col end -->

            </div>
            <!-- row end -->

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
                                    <a href="{{url('doctor-details')}}" class="avatar me-2 flex-shrink-0">
                                        <img src="{{URL::asset('build/img/doctors/doctor-01.jpg')}}" alt="img" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="fs-14 mb-1 text-truncate"><a href="{{url('doctor-details')}}" class="fw-semibold">Dr. Mick Thompson</a></h6>
                                        <p class="mb-0 fs-13 text-truncate">Cardiologist</p>
                                    </div>
                                </div>
                                <span class="badge fw-medium badge-soft-danger border border-danger flex-shrink-0">20 Bookings</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="d-flex align-items-center">
                                    <a href="{{url('doctor-details')}}" class="avatar me-2 flex-shrink-0">
                                        <img src="{{URL::asset('build/img/doctors/doctor-02.jpg')}}" alt="img" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="fs-14 mb-1 text-truncate"><a href="{{url('doctor-details')}}" class="fw-semibold">Dr. Sarah Johnson</a></h6>
                                        <p class="mb-0 fs-13 text-truncate">Orthopedic Surgeon</p>
                                    </div>
                                </div>
                                <span class="badge fw-medium badge-soft-danger border border-danger flex-shrink-0">15 Bookings</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="d-flex align-items-center">
                                    <a href="{{url('doctor-details')}}" class="avatar me-2 flex-shrink-0">
                                        <img src="{{URL::asset('build/img/doctors/doctor-03.jpg')}}" alt="img" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="fs-14 mb-1 text-truncate"><a href="{{url('doctor-details')}}" class="fw-semibold">Dr. Emily Carter</a></h6>
                                        <p class="mb-0 fs-13 text-truncate">Pediatrician</p>
                                    </div>
                                </div>
                                <span class="badge fw-medium badge-soft-danger border border-danger flex-shrink-0">12 Bookings</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="d-flex align-items-center">
                                    <a href="{{url('doctor-details')}}" class="avatar me-2 flex-shrink-0">
                                        <img src="{{URL::asset('build/img/doctors/doctor-04.jpg')}}" alt="img" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="fs-14 mb-1 text-truncate"><a href="{{url('doctor-details')}}" class="fw-semibold">Dr. David Lee</a></h6>
                                        <p class="mb-0 fs-13 text-truncate">Gynecologist</p>
                                    </div>
                                </div>
                                <span class="badge fw-medium badge-soft-danger border border-danger flex-shrink-0">08 Bookings</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-0">
                                <div class="d-flex align-items-center">
                                    <a href="{{url('doctor-details')}}" class="avatar me-2 flex-shrink-0">
                                        <img src="{{URL::asset('build/img/doctors/doctor-05.jpg')}}" alt="img" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="fs-14 mb-1 text-truncate"><a href="{{url('doctor-details')}}" class="fw-semibold">Dr. Anna Kim</a></h6>
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
                                            <i class="ti ti-file-description fs-20"></i>
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
                                            <i class="ti ti-file-description fs-20"></i>
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
                                            <i class="ti ti-file-description fs-20"></i>
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
                                            <i class="ti ti-file-description fs-20"></i>
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
                                            <i class="ti ti-file-description fs-20"></i>
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
                                        <tr class="border-white">
                                            <td class="ps-0">
                                                <div class="d-flex align-items-center">
                                                    <a href="{{url('doctor-details')}}" class="avatar me-2">
                                                        <img src="{{URL::asset('build/img/doctors/doctor-06.jpg')}}" alt="img" class="rounded-circle">
                                                    </a>
                                                    <div>
                                                        <h6 class="fs-14 mb-1"><a href="{{url('doctor-details')}}" class="fw-semibold">Dr. John Smith</a></h6>
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
                                        <tr class="border-white">
                                            <td class="ps-0">
                                                <div class="d-flex align-items-center">
                                                    <a href="{{url('doctor-details')}}" class="avatar me-2">
                                                        <img src="{{URL::asset('build/img/doctors/doctor-07.jpg')}}" alt="img" class="rounded-circle">
                                                    </a>
                                                    <div>
                                                        <h6 class="fs-14 mb-1"><a href="{{url('doctor-details')}}" class="fw-semibold">Dr. Lisa White</a></h6>
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
                                        <tr class="border-white">
                                            <td class="ps-0">
                                                <div class="d-flex align-items-center">
                                                    <a href="{{url('doctor-details')}}" class="avatar me-2">
                                                        <img src="{{URL::asset('build/img/doctors/doctor-08.jpg')}}" alt="img" class="rounded-circle">
                                                    </a>
                                                    <div>
                                                        <h6 class="fs-14 mb-1"><a href="{{url('doctor-details')}}" class="fw-semibold">Dr. Patricia Brown</a></h6>
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
                                        <tr class="border-white">
                                            <td class="ps-0">
                                                <div class="d-flex align-items-center">
                                                    <a href="{{url('doctor-details')}}" class="avatar me-2">
                                                        <img src="{{URL::asset('build/img/doctors/doctor-09.jpg')}}" alt="img" class="rounded-circle">
                                                    </a>
                                                    <div>
                                                        <h6 class="fs-14 mb-1"><a href="{{url('doctor-details')}}" class="fw-semibold">Dr. Rachel Green</a></h6>
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
                                        <tr class="border-white">
                                            <td class="ps-0">
                                                <div class="d-flex align-items-center">
                                                    <a href="{{url('doctor-details')}}" class="avatar me-2">
                                                        <img src="{{URL::asset('build/img/doctors/doctor-10.jpg')}}" alt="img" class="rounded-circle">
                                                    </a>
                                                    <div>
                                                        <h6 class="fs-14 mb-1"><a href="{{url('doctor-details')}}" class="fw-semibold">Dr. Michael Smith</a></h6>
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
                                            <a href="{{url('doctor-details')}}" class="avatar me-2">
                                                <img src="{{URL::asset('build/img/doctors/doctor-01.jpg')}}" alt="img" class="rounded-circle">
                                            </a>
                                            <div>
                                                <h6 class="fs-14 mb-1"><a href="{{url('doctor-details')}}" class="fw-semibold">Dr. Mick Thompson</a></h6>
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
                                                <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_appointment"><i class="ti ti-edit me-2"></i>Edit</a>
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
                                            <a href="{{url('doctor-details')}}" class="avatar me-2">
                                                <img src="{{URL::asset('build/img/doctors/doctor-09.jpg')}}" alt="img" class="rounded-circle">
                                            </a>
                                            <div>
                                                <h6 class="fs-14 mb-1"><a href="{{url('doctor-details')}}" class="fw-semibold">Dr. Sarah Johnson</a></h6>
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
                                                <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_appointment"><i class="ti ti-edit me-2"></i>Edit</a>
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
                                            <a href="{{url('doctor-details')}}" class="avatar me-2">
                                                <img src="{{URL::asset('build/img/doctors/doctor-03.jpg')}}" alt="img" class="rounded-circle">
                                            </a>
                                            <div>
                                                <h6 class="fs-14 mb-1"><a href="{{url('doctor-details')}}" class="fw-semibold">Dr. Emily Carter</a></h6>
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
                                                <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_appointment"><i class="ti ti-edit me-2"></i>Edit</a>
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
                                            <a href="{{url('doctor-details')}}" class="avatar me-2">
                                                <img src="{{URL::asset('build/img/doctors/doctor-04.jpg')}}" alt="img" class="rounded-circle">
                                            </a>
                                            <div>
                                                <h6 class="fs-14 mb-1"><a href="{{url('doctor-details')}}" class="fw-semibold">Dr. David Lee</a></h6>
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
                                                <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_appointment"><i class="ti ti-edit me-2"></i>Edit</a>
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
                                            <a href="{{url('doctor-details')}}" class="avatar me-2">
                                                <img src="{{URL::asset('build/img/doctors/doctor-05.jpg')}}" alt="img" class="rounded-circle">
                                            </a>
                                            <div>
                                                <h6 class="fs-14 mb-1"><a href="{{url('doctor-details')}}" class="fw-semibold">Dr. Anna Kim</a></h6>
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
                                                <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_appointment"><i class="ti ti-edit me-2"></i>Edit</a>
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

            <!-- row start -->
            <div class="row">
                <div class="col-xl-4 col-md-6">
                    <div class="card">
                        <div class="card-body d-flex align-items-center flex-sm-nowrap flex-wrap row-gap-3">
                            <div class="me-3 doctor-profile-img"><a href="{{url('doctor-details')}}"><img src="{{URL::asset('build/img/doctors/doctor-01.jpg')}}" class="rounded" alt=""></a></div>
                            <div class="flex-fill">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <h6 class="mb-0 fs-14 fw-semibold text-truncate"><a href="{{url('doctor-details')}}">Dr. Mick Thompson</a></h6>
                                    <div class="action-item">
                                        <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                            <i class="ti ti-dots-vertical"></i>
                                        </a>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="offcanvas" data-bs-target="#edit_doctor"><i class="ti ti-edit me-2"></i>Edit</a>
                                            </li>
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash me-2"></i>Delete</a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <span class="d-block mb-2 fs-13">Cardiologist</span>
                                <p class="mb-2 fs-13 text-truncate">Available : Mon, 20 Jan 2025</p>
                                <div class="d-flex align-items-center justify-content-between">
                                    <h6 class="text-primary fs-14 fw-semibold mb-0"><span class="text-muted fs-13 fw-normal">Starts From : </span> $499</h6>
                                    <a href="#" class="avatar avatar-xs border text-muted fs-14"><i class="ti ti-calendar-cog"></i></a>
                                </div>
                            </div>
                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div><!-- end col -->

                <div class="col-xl-4 col-md-6">
                    <div class="card">
                        <div class="card-body d-flex align-items-center flex-sm-nowrap flex-wrap row-gap-3">
                            <div class="me-3 doctor-profile-img"><a href="{{url('doctor-details')}}"><img src="{{URL::asset('build/img/doctors/doctor-02.jpg')}}" class="rounded" alt=""></a></div>
                            <div class="flex-fill">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <h6 class="mb-0 fs-14 fw-semibold text-truncate"><a href="{{url('doctor-details')}}">Dr. Sarah Johnson</a></h6>
                                    <div class="action-item">
                                        <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                            <i class="ti ti-dots-vertical"></i>
                                        </a>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="offcanvas" data-bs-target="#edit_doctor"><i class="ti ti-edit me-2"></i>Edit</a>
                                            </li>
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash me-2"></i>Delete</a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <span class="d-block mb-2 fs-13">Orthopedic Surgeon</span>
                                <p class="mb-2 fs-13 text-truncate">Available : Wed, 22 Jan 2025</p>
                                <div class="d-flex align-items-center justify-content-between">
                                    <h6 class="text-primary fw-semibold fs-14 mb-0"><span class="text-muted fs-13 fw-normal">Starts From : </span> $450</h6>
                                    <a href="#" class="avatar avatar-xs border text-muted fs-14"><i class="ti ti-calendar-cog"></i></a>
                                </div>
                            </div>
                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div><!-- end col -->

                <div class="col-xl-4 col-md-6">
                    <div class="card">
                        <div class="card-body d-flex align-items-center flex-sm-nowrap flex-wrap row-gap-3">
                            <div class="me-3 doctor-profile-img"><a href="{{url('doctor-details')}}"><img src="{{URL::asset('build/img/doctors/doctor-03.jpg')}}" class="rounded" alt=""></a></div>
                            <div class="flex-fill">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <h6 class="mb-0 fs-14 fw-semibold text-truncate"><a href="{{url('doctor-details')}}">Dr. Emily Carter</a></h6>
                                    <div class="action-item">
                                        <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                            <i class="ti ti-dots-vertical"></i>
                                        </a>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="offcanvas" data-bs-target="#edit_doctor"><i class="ti ti-edit me-2"></i>Edit</a>
                                            </li>
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash me-2"></i>Delete</a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <span class="d-block mb-2 fs-13">Pediatrician</span>
                                <p class="mb-2 fs-13 text-truncate">Available : Fri, 24 Jan 2025</p>
                                <div class="d-flex align-items-center justify-content-between">
                                    <h6 class="text-primary fw-semibold fs-14 mb-0"><span class="text-muted fs-13 fw-normal">Starts From : </span> $300</h6>
                                    <a href="#" class="avatar avatar-xs border text-muted fs-14"><i class="ti ti-calendar-cog"></i></a>
                                </div>
                            </div>
                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div><!-- end col -->
            </div>
            <!-- row end -->

            <!-- row start -->
            <div class="row">

                <!-- col start -->
                <div class="col-xl-3 col-md-6 d-flex">
                    <div class="card shadow-sm flex-fill w-100">
                        <img src="{{URL::asset('./build/img/bg/widget-bg-01.svg')}}" alt="img" class="position-absolute end-0 bottom-0">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <p class="mb-2">Total Present</p>
                                    <div class="d-flex align-items-center">
                                        <p class="mb-0 text-truncate"><span class="fs-16 text-dark fw-bold">180</span> / 200</p>
                                        <span class="badge fw-medium bg-soft-success text-success flex-shrink-0 ms-2">+10.6%<i class="ti ti-arrow-up-right ms-1"></i></span>
                                    </div>
                                </div>
                                <span class="avatar avatar-sm bg-soft-primary text-primary border border-primary rounded-circle flex-shrink-0"><i class="ti ti-user-check fs-14"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-xl-3 col-md-6 d-flex">
                    <div class="card shadow-sm flex-fill w-100">
                        <img src="{{URL::asset('./build/img/bg/widget-bg-02.svg')}}" alt="img" class="position-absolute end-0 bottom-0">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <p class="mb-2">Planned Leaves</p>
                                    <div class="d-flex align-items-center">
                                        <p class="mb-0"><span class="fs-16 text-dark fw-bold">10</span></p>
                                        <span class="badge fw-medium bg-soft-success text-success flex-shrink-0 ms-2">+8.95%<i class="ti ti-arrow-up-right ms-1"></i></span>
                                    </div>
                                </div>
                                <span class="avatar avatar-sm bg-soft-success text-success border border-success rounded-circle flex-shrink-0"><i class="ti ti-user-x fs-14"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-xl-3 col-md-6 d-flex">
                    <div class="card shadow-sm flex-fill w-100">
                        <img src="{{URL::asset('./build/img/bg/widget-bg-03.svg')}}" alt="img" class="position-absolute end-0 bottom-0">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <p class="mb-2">Unplanned Leaves</p>
                                    <div class="d-flex align-items-center">
                                        <p class="mb-0"><span class="fs-16 text-dark fw-bold">50</span></p>
                                        <span class="badge fw-medium bg-soft-success text-success flex-shrink-0 ms-2">+3.78%<i class="ti ti-arrow-up-right ms-1"></i></span>
                                    </div>
                                </div>
                                <span class="avatar avatar-sm bg-soft-warning text-warning border border-warning rounded-circle flex-shrink-0"><i class="ti ti-user-exclamation fs-14"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-xl-3 col-md-6 d-flex">
                    <div class="card shadow-sm flex-fill w-100">
                        <img src="{{URL::asset('./build/img/bg/widget-bg-04.svg')}}" alt="img" class="position-absolute end-0 bottom-0">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <p class="mb-2">Pending Requests</p>
                                    <div class="d-flex align-items-center">
                                        <p class="mb-0"><span class="fs-16 text-dark fw-bold">15</span></p>
                                        <span class="badge fw-medium bg-soft-success text-success flex-shrink-0 ms-2">+7.65%<i class="ti ti-arrow-up-right ms-1"></i></span>
                                    </div>
                                </div>
                                <span class="avatar avatar-sm bg-soft-danger text-danger border border-danger rounded-circle flex-shrink-0"><i class="ti ti-user-question fs-14"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- col end -->

            </div>
            <!-- row end -->

            <!-- row start -->
            <div class="row">

                <!-- col start -->
                <div class="col-xl-3 col-md-6 d-flex">
                    <div class="card shadow-sm flex-fill w-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <div>
                                    <p class="mb-1 text-truncate">Total Clinic Income</p>
                                    <h6 class="mb-0 fw-bold">$18,750</h6>
                                </div>
                                <span class="avatar avatar-lg bg-soft-primary text-primary rounded-circle flex-shrink-0"><i class="ti ti-currency-dollar fs-24"></i></span>
                            </div>
                            <div class="progress mb-2 progress-sm">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: 75%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <p class="mb-0 fs-13"><span class="text-success"><i class="ti ti-arrow-up-right me-1"></i>5.62%</span> from last month</p>
                        </div>
                    </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-xl-3 col-md-6 d-flex">
                    <div class="card shadow-sm flex-fill w-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <div>
                                    <p class="mb-1 text-truncate">Doctor Fees Collected</p>
                                    <h6 class="mb-0 fw-bold">$7,000</h6>
                                </div>
                                <span class="avatar avatar-lg bg-soft-success text-success rounded-circle flex-shrink-0"><i class="ti ti-stethoscope fs-24"></i></span>
                            </div>
                            <div class="progress mb-2 progress-sm">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 75%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <p class="mb-0 fs-13"><span class="text-success"><i class="ti ti-arrow-up-right me-1"></i>11.4%</span> from last month</p>
                        </div>
                    </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-xl-3 col-md-6 d-flex">
                    <div class="card shadow-sm flex-fill w-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <div>
                                    <p class="mb-1 text-truncate">Medicine Sales</p>
                                    <h6 class="mb-0 fw-bold">$6,250</h6>
                                </div>
                                <span class="avatar avatar-lg bg-soft-warning text-warning rounded-circle flex-shrink-0"><i class="ti ti-pill fs-24"></i></span>
                            </div>
                            <div class="progress mb-2 progress-sm">
                                <div class="progress-bar bg-warning" role="progressbar" style="width: 75%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <p class="mb-0 fs-13"><span class="text-success"><i class="ti ti-arrow-up-right me-1"></i>8.52%</span> from last month</p>
                        </div>
                    </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-xl-3 col-md-6 d-flex">
                    <div class="card shadow-sm flex-fill w-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <div>
                                    <p class="mb-1 text-truncate">Lab Revenue</p>
                                    <h6 class="mb-0 fw-bold">$5,500</h6>
                                </div>
                                <span class="avatar avatar-lg bg-soft-danger text-danger rounded-circle flex-shrink-0"><i class="ti ti-flask fs-24"></i></span>
                            </div>
                            <div class="progress mb-2 progress-sm">
                                <div class="progress-bar bg-danger" role="progressbar" style="width: 25%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <p class="mb-0 fs-13"><span class="text-danger"><i class="ti ti-arrow-down-right me-1"></i>7.45%</span> from last month</p>
                        </div>
                    </div>
                </div>
                <!-- col end -->

            </div>     
            <!-- row end -->

            <!-- row start -->
            <div class="row">

                <!-- col start -->
                <div class="col-xl-3 col-md-6 d-flex">                       
                    <div class="card shadow-sm flex-fill w-100 z-0">
                        <img src="{{URL::asset('./build/img/bg/widget-bg-05.svg')}}" alt="img" class="position-absolute start-0 top-0 z-n1">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-end">
                                <div>
                                    <p class="mb-1 text-truncate">Total Expenses</p>
                                    <h6 class="mb-2 fw-bold">$13,500</h6>
                                    <p class="mb-0 fs-13 text-truncate"><span class="text-success"><i class="ti ti-arrow-up-right me-1"></i>5.62%</span> from last month</p>
                                </div>
                                <span class="avatar avatar-md widget-icon bg-soft-primary border border-primary text-primary rounded-2 flex-shrink-0"><i class="ti ti-currency-dollar fs-14"></i></span>
                            </div>                                
                        </div>
                    </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-xl-3 col-md-6 d-flex">                       
                    <div class="card shadow-sm flex-fill w-100 z-0">
                        <img src="{{URL::asset('./build/img/bg/widget-bg-06.svg')}}" alt="img" class="position-absolute start-0 top-0 z-n1">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-end">
                                <div>
                                    <p class="mb-1 text-truncate">Doctor Payouts</p>
                                    <h6 class="mb-2 fw-bold">$4,500</h6>
                                    <p class="mb-0 fs-13 text-truncate"><span class="text-success"><i class="ti ti-arrow-up-right me-1"></i>11.4%</span> from last month</p>
                                </div>
                                <span class="avatar avatar-md widget-icon bg-soft-success border border-success text-success rounded-2 flex-shrink-0"><i class="ti ti-stethoscope fs-14"></i></span>
                            </div>                                
                        </div>
                    </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-xl-3 col-md-6 d-flex">                       
                    <div class="card shadow-sm flex-fill w-100 z-0">
                        <img src="{{URL::asset('./build/img/bg/widget-bg-07.svg')}}" alt="img" class="position-absolute start-0 top-0 z-n1">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-end">
                                <div>
                                    <p class="mb-1 text-truncate">Staff Salaries</p>
                                    <h6 class="mb-2 fw-bold">$3,200</h6>
                                    <p class="mb-0 fs-13 text-truncate"><span class="text-success"><i class="ti ti-arrow-up-right me-1"></i>8.52%</span> from last month</p>
                                </div>
                                <span class="avatar avatar-md widget-icon bg-soft-warning border border-warning text-warning rounded-2 flex-shrink-0"><i class="ti ti-pill fs-14"></i></span>
                            </div>                                
                        </div>
                    </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-xl-3 col-md-6 d-flex">                       
                    <div class="card shadow-sm flex-fill w-100 z-0">
                        <img src="{{URL::asset('./build/img/bg/widget-bg-08.svg')}}" alt="img" class="position-absolute start-0 top-0 z-n1">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-end">
                                <div>
                                    <p class="mb-1 text-truncate">Utilities & Rent</p>
                                    <h6 class="mb-2 fw-bold">$2,000</h6>
                                    <p class="mb-0 fs-13 text-truncate"><span class="text-danger"><i class="ti ti-arrow-down-right me-1"></i>7.45%</span> from last month</p>
                                </div>
                                <span class="avatar avatar-md widget-icon bg-soft-danger border border-danger text-danger rounded-2 flex-shrink-0"><i class="ti ti-flask fs-14"></i></span>
                            </div>                                
                        </div>
                    </div>
                </div>
                <!-- col end -->

            </div>
            <!-- row end -->

            <!-- row start -->
            <div class="row">
                
                <!-- col start -->
                <div class="col-xl-3 col-md-6 d-flex">                       
                    <div class="card shadow-sm flex-fill w-100 z-0">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <p class="mb-1 text-truncate">Total Income</p>
                                    <h6 class="mb-0 fw-bold">$125,150</h6>
                                </div>
                                <span class="avatar avatar-lg bg-primary rounded-circle flex-shrink-0"><i class="ti ti-arrow-up-right-circle fs-24"></i></span>
                            </div>  
                            <span class="mb-0 fs-13 d-block bg-light rounded-2 p-2 text-center"><span class="text-success"><i class="ti ti-arrow-up-right me-1"></i>5.62%</span> from last month</span>                              
                        </div>
                    </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-xl-3 col-md-6 d-flex">                       
                    <div class="card shadow-sm flex-fill w-100 z-0">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <p class="mb-1 text-truncate">Total Expenses</p>
                                    <h6 class="mb-0 fw-bold">$91,800</h6>
                                </div>
                                <span class="avatar avatar-lg bg-success rounded-circle flex-shrink-0"><i class="ti ti-arrow-down-right-circle fs-24"></i></span>
                            </div>  
                            <span class="mb-0 fs-13 d-block bg-light rounded-2 p-2 text-center"><span class="text-success"><i class="ti ti-arrow-up-right me-1"></i>11.4%</span> from last month</span>                              
                        </div>
                    </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-xl-3 col-md-6 d-flex">                       
                    <div class="card shadow-sm flex-fill w-100 z-0">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <p class="mb-1 text-truncate">Net Profit</p>
                                    <h6 class="mb-0 fw-bold">$91,800</h6>
                                </div>
                                <span class="avatar avatar-lg bg-warning rounded-circle flex-shrink-0"><i class="ti ti-currency-dollar fs-24"></i></span>
                            </div>  
                            <span class="mb-0 fs-13 d-block bg-light rounded-2 p-2 text-center"><span class="text-success"><i class="ti ti-arrow-up-right me-1"></i>8.52%</span> from last month</span>                              
                        </div>
                    </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-xl-3 col-md-6 d-flex">                       
                    <div class="card shadow-sm flex-fill w-100 z-0">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <p class="mb-1 text-truncate">Profit Margin (%)</p>
                                    <h6 class="mb-0 fw-bold">26.6%</h6>
                                </div>
                                <span class="avatar avatar-lg bg-danger rounded-circle flex-shrink-0"><i class="ti ti-cash fs-24"></i></span>
                            </div>  
                            <span class="mb-0 fs-13 d-block bg-light rounded-2 p-2 text-center"><span class="text-danger"><i class="ti ti-arrow-down-right me-1"></i>7.45%</span> from last month</span>                              
                        </div>
                    </div>
                </div>
                <!-- col end -->

            </div>
            <!-- row end -->

            <!-- row start -->
            <div class="row">

                <!-- col start -->
                <div class="col-xl-3 col-md-6 d-flex">                       
                    <div class="card shadow-sm flex-fill w-100 z-0">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="avatar avatar-sm bg-soft-primary border border-primary text-primary rounded-2 flex-shrink-0"><i class="ti ti-calendar-time fs-14"></i></span>
                                <span class="badge fw-medium bg-soft-success text-success flex-shrink-0 ms-2">+10%<i class="ti ti-arrow-up-right ms-1"></i></span>
                            </div>  
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="mb-1 text-truncate">Total Appointments</p>
                                    <h6 class="mb-0 fw-bold">850</h6>
                                </div>
                                <div id="s-col-11" class="chart-set"></div>
                            </div>  
                        </div>
                    </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-xl-3 col-md-6 d-flex">                       
                    <div class="card shadow-sm flex-fill w-100 z-0">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="avatar avatar-sm bg-soft-success border border-success text-success rounded-2 flex-shrink-0"><i class="ti ti-calendar-up fs-14"></i></span>
                                <span class="badge fw-medium bg-soft-success text-success flex-shrink-0 ms-2">+11.5%<i class="ti ti-arrow-up-right ms-1"></i></span>
                            </div>  
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="mb-1 text-truncate">Completed</p>
                                    <h6 class="mb-0 fw-bold">720</h6>
                                </div>
                                <div id="s-col-12" class="chart-set"></div>
                            </div>  
                        </div>
                    </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-xl-3 col-md-6 d-flex">                       
                    <div class="card shadow-sm flex-fill w-100 z-0">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="avatar avatar-sm bg-soft-warning border border-warning text-warning rounded-2 flex-shrink-0"><i class="ti ti-calendar-down fs-14"></i></span>
                                <span class="badge fw-medium bg-soft-success text-success flex-shrink-0 ms-2">+8.43%<i class="ti ti-arrow-up-right ms-1"></i></span>
                            </div>  
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="mb-1 text-truncate">Cancelled</p>
                                    <h6 class="mb-0 fw-bold">65</h6>
                                </div>
                                <div id="s-col-13" class="chart-set"></div>
                            </div>  
                        </div>
                    </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-xl-3 col-md-6 d-flex">                       
                    <div class="card shadow-sm flex-fill w-100 z-0">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="avatar avatar-sm bg-soft-danger border border-danger text-danger rounded-2 flex-shrink-0"><i class="ti ti-calendar-repeat fs-14"></i></span>
                                <span class="badge fw-medium bg-soft-success text-success flex-shrink-0 ms-2">+6.58%<i class="ti ti-arrow-up-right ms-1"></i></span>
                            </div>  
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="mb-1 text-truncate">Rescheduled</p>
                                    <h6 class="mb-0 fw-bold">40</h6>
                                </div>
                                <div id="s-col-14" class="chart-set"></div>
                            </div>  
                        </div>
                    </div>
                </div>
                <!-- col end -->

            </div>
            <!-- row end -->

            <!-- row start-->
            <div class="row">
                
                <!-- col start -->
                <div class="col-xl-3 col-md-6 d-flex">                       
                    <div class="card shadow-sm flex-fill w-100 z-0">
                        <img src="{{URL::asset('./build/img/bg/widget-bg-09.svg')}}" alt="img" class="position-absolute end-0 bottom-0 z-n1">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <p class="mb-1 text-truncate">Total Patients</p>
                                    <h6 class="mb-0 fw-bold">1,240</h6>
                                </div>
                                <span class="avatar avatar-lg bg-primary rounded-circle flex-shrink-0"><i class="ti ti-users fs-24"></i></span>
                            </div>  
                            <span class="mb-0 fs-13 d-block border-top rounded-2 pt-2"><span class="text-success"><i class="ti ti-arrow-up-right me-1"></i>5.62%</span> from last month</span>                              
                        </div>
                    </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-xl-3 col-md-6 d-flex">                       
                    <div class="card shadow-sm flex-fill w-100 z-0">
                        <img src="{{URL::asset('./build/img/bg/widget-bg-10.svg')}}" alt="img" class="position-absolute end-0 bottom-0 z-n1">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <p class="mb-1 text-truncate">New Patients</p>
                                    <h6 class="mb-0 fw-bold">210</h6>
                                </div>
                                <span class="avatar avatar-lg bg-success rounded-circle flex-shrink-0"><i class="ti ti-users-plus fs-24"></i></span>
                            </div>  
                            <span class="mb-0 fs-13 d-block border-top rounded-2 pt-2"><span class="text-success"><i class="ti ti-arrow-up-right me-1"></i>11.4%</span> from last month</span>                              
                        </div>
                    </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-xl-3 col-md-6 d-flex">                       
                    <div class="card shadow-sm flex-fill w-100 z-0">
                        <img src="{{URL::asset('./build/img/bg/widget-bg-11.svg')}}" alt="img" class="position-absolute end-0 bottom-0 z-n1">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <p class="mb-1 text-truncate">Appointment Booked</p>
                                    <h6 class="mb-0 fw-bold">500</h6>
                                </div>
                                <span class="avatar avatar-lg bg-warning rounded-circle flex-shrink-0"><i class="ti ti-bookmarks fs-24"></i></span>
                            </div>  
                            <span class="mb-0 fs-13 d-block border-top rounded-2 pt-2"><span class="text-success"><i class="ti ti-arrow-up-right me-1"></i>8.52%</span> from last month</span>                              
                        </div>
                    </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-xl-3 col-md-6 d-flex">                       
                    <div class="card shadow-sm flex-fill w-100 z-0">
                        <img src="{{URL::asset('./build/img/bg/widget-bg-12.svg')}}" alt="img" class="position-absolute end-0 bottom-0 z-n1">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <p class="mb-1 text-truncate">Returning Patients</p>
                                    <h6 class="mb-0 fw-bold">380</h6>
                                </div>
                                <span class="avatar avatar-lg bg-danger rounded-circle flex-shrink-0"><i class="ti ti-users-minus fs-24"></i></span>
                            </div>  
                            <span class="mb-0 fs-13 d-block border-top rounded-2 pt-2"><span class="text-danger"><i class="ti ti-arrow-down-right me-1"></i>7.45%</span> from last month</span>                              
                        </div>
                    </div>
                </div>
                <!-- col end -->

            </div>
                <!-- row end -->

                <!-- row start -->
            <div class="row">

                <!-- col start -->
                <div class="col-xl-3 col-md-6 d-flex">                       
                    <div class="card shadow-sm flex-fill w-100 z-0">
                        <img src="{{URL::asset('./build/img/bg/widget-bg-05.svg')}}" alt="img" class="position-absolute start-0 top-0 z-n1">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <p class="mb-1 text-truncate">Total Visits</p>
                                    <h6 class="mb-0 fw-bold">750</h6>
                                </div>
                                <span class="avatar bg-soft-primary border border-primary text-primary rounded-2 flex-shrink-0"><i class="ti ti-user-down fs-24"></i></span>
                            </div>  
                            <div class="chart-set" id="s-col-15"></div>
                        </div>
                    </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-xl-3 col-md-6 d-flex">                       
                    <div class="card shadow-sm flex-fill w-100 z-0">
                        <img src="{{URL::asset('./build/img/bg/widget-bg-06.svg')}}" alt="img" class="position-absolute start-0 top-0 z-n1">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <p class="mb-1 text-truncate">Completed</p>
                                    <h6 class="mb-0 fw-bold">625</h6>
                                </div>
                                <span class="avatar bg-soft-success border border-success text-success rounded-2 flex-shrink-0"><i class="ti ti-user-check fs-24"></i></span>
                            </div>  
                            <div class="chart-set" id="s-col-16"></div>
                        </div>
                    </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-xl-3 col-md-6 d-flex">                       
                    <div class="card shadow-sm flex-fill w-100 z-0">
                        <img src="{{URL::asset('./build/img/bg/widget-bg-07.svg')}}" alt="img" class="position-absolute start-0 top-0 z-n1">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <p class="mb-1 text-truncate">No-Shows</p>
                                    <h6 class="mb-0 fw-bold">50</h6>
                                </div>
                                <span class="avatar bg-soft-warning border border-warning text-warning rounded-2 flex-shrink-0"><i class="ti ti-user-cancel fs-24"></i></span>
                            </div>  
                            <div class="chart-set" id="s-col-17"></div>
                        </div>
                    </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-xl-3 col-md-6 d-flex">                       
                    <div class="card shadow-sm flex-fill w-100 z-0">
                        <img src="{{URL::asset('./build/img/bg/widget-bg-08.svg')}}" alt="img" class="position-absolute start-0 top-0 z-n1">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <p class="mb-1 text-truncate">Follow-ups</p>
                                    <h6 class="mb-0 fw-bold">75</h6>
                                </div>
                                <span class="avatar bg-soft-danger border border-danger text-danger rounded-2 flex-shrink-0"><i class="ti ti-user-exclamation fs-24"></i></span>
                            </div>  
                            <div class="chart-set" id="s-col-18"></div>
                        </div>
                    </div>
                </div>
                <!-- col end -->

            </div>
            <!-- row end -->

            <!-- row start -->
            <div class="row">

                <div class="col-md-4">
                    <div class="card blog-item">
                        <div class="card-body p-0">
                            <div class="position-relative rounded-top overflow-hidden">
                                <a href="{{url('blog-details')}}" class="blog-img"><img src="{{URL::asset('build/img/blogs/blog-img-01.jpg')}}" alt="img" class="img-fluid rounded-top"></a>
                                <a href="javascript:void(0);" class="btn btn-sm fs-16 d-inline-flex align-items-center justify-content-center p-2 bg-white rounded-2 blog-delete"><i class="ti ti-trash"></i></a>
                                <a href="{{url('edit-blog')}}" class="btn btn-sm fs-16 d-inline-flex align-items-center justify-content-center p-2 bg-white rounded-2 blog-edit"><i class="ti ti-edit"></i></a>
                            </div>
                            <div class="p-3">
                                <span class="badge badge-soft-primary border border-primary fw-medium fs-13 py-1 px-2 mb-3">Preventive Care</span>
                                <h6 class="fw-bold"><a href="{{url('blog-details')}}">Health First: Your Guide to Better Living</a></h6>
                                <p class="truncate-2-lines mb-0">Preventive care refers to medical services that help you avoid illness, detect health issues early, and maintain overall wellness</p>
                            </div>
                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div><!-- end col -->

                <div class="col-md-4">
                    <div class="card blog-item">
                        <div class="card-body p-0">
                            <div class="position-relative rounded-top overflow-hidden">
                                <a href="{{url('blog-details')}}" class="blog-img"><img src="{{URL::asset('build/img/blogs/blog-img-02.jpg')}}" alt="img" class="img-fluid rounded-top"></a>
                                <a href="javascript:void(0);" class="btn fs-16 btn-sm d-inline-flex align-items-center justify-content-center p-2 bg-white rounded-2 blog-delete"><i class="ti ti-trash"></i></a>
                                <a href="{{url('edit-blog')}}" class="btn btn-sm fs-16 d-inline-flex align-items-center justify-content-center p-2 bg-white rounded-2 blog-edit"><i class="ti ti-edit"></i></a>
                            </div>
                            <div class="p-3">
                                <span class="badge badge-soft-primary fw-medium border border-primary fs-13 py-1 px-2 mb-3">Nutrition</span>
                                <h6 class="fw-bold"><a href="{{url('blog-details')}}">Balance Your Plate, Boost Your Life</a></h6>
                                <p class="truncate-2-lines mb-0">Fuel your body, move with purpose—wellness starts with what you eat and how you live</p>
                            </div>
                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div><!-- end col -->

                <div class="col-md-4">
                    <div class="card blog-item">
                        <div class="card-body p-0">
                            <div class="position-relative rounded-top overflow-hidden">
                                <a href="{{url('blog-details')}}" class="blog-img"><img src="{{URL::asset('build/img/blogs/blog-img-04.jpg')}}" alt="img" class="img-fluid rounded-top"></a>
                                <a href="javascript:void(0);" class="btn btn-sm fs-16 d-inline-flex align-items-center justify-content-center p-2 bg-white rounded-2 blog-delete"><i class="ti ti-trash"></i></a>
                                <a href="{{url('edit-blog')}}" class="btn btn-sm fs-16 d-inline-flex align-items-center justify-content-center p-2 bg-white rounded-2 blog-edit"><i class="ti ti-edit"></i></a>
                            </div>
                            <div class="p-3">
                                <span class="badge badge-soft-primary fw-medium border border-primary fs-13 py-1 px-2 mb-3">Health Tips</span>
                                <h6 class="fw-bold"><a href="{{url('blog-details')}}">Small Daily Habits, Big Health Benefits</a></h6>
                                <p class="truncate-2-lines mb-0">Add a fruit or veggie to every meal.Don’t skip breakfast—it fuels your day.Go to bed and wake up at the same time daily</p>
                            </div>
                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div><!-- end col -->

                
            </div>
            <!-- row end -->

            <!-- start row -->
            <div class="row row-gap-3 mb-3">
                <div class="col-md-6 col-lg">
                    <a href="{{URL::asset('build/img/gallery/gallery-img-06.jpg')}}" class="image-popup">
                        <img src="{{URL::asset('build/img/gallery/gallery-06.jpg')}}" alt="img" class="img-fluid w-100">
                    </a>
                </div><!-- end col -->
                <div class="col-md-6 col-lg">
                    <a href="{{URL::asset('build/img/gallery/gallery-img-07.jpg')}}" class="image-popup">
                        <img src="{{URL::asset('build/img/gallery/gallery-07.jpg')}}" alt="img" class="img-fluid w-100">
                    </a>
                </div><!-- end col -->
                <div class="col-md-6 col-lg">
                    <a href="{{URL::asset('build/img/gallery/gallery-img-08.jpg')}}" class="image-popup">
                        <img src="{{URL::asset('build/img/gallery/gallery-08.jpg')}}" alt="img" class="img-fluid w-100">
                    </a>
                </div><!-- end col -->
                <div class="col-md-6 col-lg">
                    <a href="{{URL::asset('build/img/gallery/gallery-img-04.jpg')}}" class="image-popup">
                        <img src="{{URL::asset('build/img/gallery/gallery-04.jpg')}}" alt="img" class="img-fluid w-100">
                    </a>
                </div><!-- end col -->
                <div class="col-md-6 col-lg">
                    <a href="{{URL::asset('build/img/gallery/gallery-img-10.jpg')}}" class="image-popup">
                        <img src="{{URL::asset('build/img/gallery/gallery-10.jpg')}}" alt="img" class="img-fluid w-100">
                    </a>
                </div><!-- end col -->
            </div>
            <!-- end row -->

            <!-- card start -->
            <div class="card shadow-sm flex-fill w-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="fw-bold mb-0">Appointment Statistics</h5> 
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
                    <div class="row row-gap-3">
                        <div class="col-md-3 col-sm-6">
                            <div class="bg-light border p-2 text-center rounded-2">
                                <p class="mb-1 text-truncate"><i class="ti ti-point-filled me-1 text-primary"></i>All Appointments</p>
                                <h5 class="fw-bold mb-0">6314</h5>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="bg-light border p-2 text-center rounded-2">
                                <p class="mb-1"><i class="ti ti-point-filled me-1 text-danger"></i>Cancelled</p>
                                <h5 class="fw-bold mb-0">456</h5>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="bg-light border p-2 text-center rounded-2">
                                <p class="mb-1"><i class="ti ti-point-filled me-1 text-warning"></i>Reschedule</p>
                                <h5 class="fw-bold mb-0">745</h5>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="bg-light border p-2 text-center rounded-2">
                                <p class="mb-1"><i class="ti ti-point-filled me-1 text-success"></i>Completed</p>
                                <h5 class="fw-bold mb-0">4578</h5>
                            </div>
                        </div>
                    </div>
                    <div class="chart-set" id="s-col-19"></div>
                </div>
            </div>
            <!-- card end -->

            <!-- card start -->
            <div class="card shadow-sm flex-fill w-100 mb-0">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="fw-bold mb-0">Appointments</h5> 
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
                    <div class="d-flex align-items-center justify-content-center gap-2 mb-1 flex-wrap">
                        <p class="mb-0 d-inline-flex align-items-center"><i class="ti ti-point-filled me-1 fs-18 text-primary"></i>Total Appointments</p>
                        <p class="mb-0 d-inline-flex align-items-center"><i class="ti ti-point-filled me-1 fs-18 text-success"></i>Completed Appointments</p>
                    </div>
                    <div class="chart-set" id="s-col-20"></div>
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