<?php $page = 'doctor-schedule'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- ========================
        Start Page Content
    ========================= -->

    <div class="page-wrapper">

        <!-- Start Content -->
        <div class="content">

            <!-- Start Page Header -->
            <div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
                <div class="flex-grow-1">
                    <h4 class="fw-bold mb-0"> Doctor Schedule<span class="badge badge-soft-primary fs-13 fw-medium ms-2">Total Doctors : 565</span></h4>
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
                </div>
            </div>
            <!-- End Page Header -->

            <div class=" d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                <div class="search-set mb-3">
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <div class="table-search d-flex align-items-center mb-0">
                            <div class="search-input">
                                <a href="javascript:void(0);" class="btn-searchset"></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex table-dropdown mb-3 pb-1 right-content align-items-center flex-wrap row-gap-3">
                    <div class="dropdown me-2">
                        <a href="javascript:void(0);" class="btn btn-white bg-white fs-14 py-1 border d-inline-flex text-dark align-items-center" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                            <i class="ti ti-filter text-gray-5 me-1"></i>Filters
                        </a>
                        <div class="dropdown-menu dropdown-lg dropdown-menu-end filter-dropdown p-0" id="filter-dropdown">
                            <div class="d-flex align-items-center justify-content-between border-bottom filter-header">
                                <h4 class="mb-0">Filter</h4>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="link-danger text-decoration-underline">Clear All</a>
                                </div>
                            </div>
                            <form action="#">
                                <div class="filter-body pb-0">
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <label class="form-label">Doctor</label>
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
                                    <a href="javascript:void(0);" class="btn btn-light btn-md me-2" id="close-filter">Close</a>
                                    <button type="submit" class="btn btn-primary btn-md">Filter</button>
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

            <div class="table-responsive">
                <table class="table table-nowrap datatable">
                    <thead class="thead-light">
                        <tr>
                            <th>Doctor</th>
                            <th>Department</th>
                            <th>Phone</th>
                            <th>Availability</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="{{url('doctor-details')}}" class="avatar me-2">
                                        <img src="{{URL::asset('build/img/doctors/doctor-01.jpg')}}" alt="Doctor" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="mb-1 fs-14 fw-semibold"><a href="{{url('doctor-details')}}">Dr. Mick Thompson</a></h6>
                                        <span class="fs-13 d-block"> Cardiologist </span>
                                    </div>
                                </div>
                            </td>
                            <td>Cardiology </td>
                            <td>+1 54554 54584</td>
                            <td>
                                <div class="availabity-check d-flex align-items-center gap-2" role="group" aria-label="Basic radio toggle button group">
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio1" checked="">
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio1">M</label>
                                    </div>
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio2" checked>
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio2">T</label>
                                    </div>
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio3">
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio3">W</label>
                                    </div>   
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio4" checked>
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio4">T</label>
                                    </div>   
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio5" checked>
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio5">F</label>
                                    </div>   
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio6">
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio6">S</label>
                                    </div>   
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio7">
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio7">S</label>
                                    </div>   
                                </div>
                            </td>
                            <td>
                                <div class="action-item d-flex align-items-center gap-2">
                                    <a href="{{url('appointment-calendar')}}">
                                        <i class="ti ti-calendar-cog"></i>
                                    </a>
                                    <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#doctor_detil">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="{{url('doctor-details')}}" class="avatar me-2">
                                        <img src="{{URL::asset('build/img/doctors/doctor-02.jpg')}}" alt="Doctor" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="mb-1 fs-14 fw-semibold"><a href="{{url('doctor-details')}}">Dr. Sarah Johnson</a></h6>
                                        <span class="fs-13 d-block"> Orthopedic Surgeon </span>
                                    </div>
                                </div>
                            </td>
                            <td>Orthopedics</td>
                            <td>+1 43554 54584</td>
                            <td>
                                <div class="availabity-check d-flex align-items-center gap-2" role="group" aria-label="Basic radio toggle button group">
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio-2" id="btnradio8" checked="">
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio8">M</label>
                                    </div>
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio-2" id="btnradio9" checked>
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio9">T</label>
                                    </div>
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio-2" id="btnradio10">
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio10">W</label>
                                    </div>   
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio-2" id="btnradio11" checked>
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio11">T</label>
                                    </div>   
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio-2" id="btnradio12" checked>
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio12">F</label>
                                    </div>   
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio-2" id="btnradio13">
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio13">S</label>
                                    </div>   
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio-2" id="btnradio14">
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio14">S</label>
                                    </div>   
                                </div>
                            </td>
                            <td>
                                <div class="action-item d-flex align-items-center gap-2">
                                    <a href="{{url('appointment-calendar')}}">
                                        <i class="ti ti-calendar-cog"></i>
                                    </a>
                                    <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#doctor_detil">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="{{url('doctor-details')}}" class="avatar me-2">
                                        <img src="{{URL::asset('build/img/doctors/doctor-03.jpg')}}" alt="Doctor" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="mb-1 fs-14 fw-semibold"><a href="{{url('doctor-details')}}">Dr. Emily Carter</a></h6>
                                        <span class="fs-13 d-block"> Pediatrician </span>
                                    </div>
                                </div>
                            </td>
                            <td>Pediatrics</td>
                            <td>+1 471254 54585</td>
                            <td>
                                <div class="availabity-check d-flex align-items-center gap-2" role="group" aria-label="Basic radio toggle button group">
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio15" checked="">
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio15">M</label>
                                    </div>
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio16" checked>
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio16">T</label>
                                    </div>
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio17">
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio17">W</label>
                                    </div>   
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio18" checked>
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio18">T</label>
                                    </div>   
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio19" checked>
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio19">F</label>
                                    </div>   
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio20">
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio20">S</label>
                                    </div>   
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio21">
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio21">S</label>
                                    </div>   
                                </div>
                            </td>
                            <td>
                                <div class="action-item d-flex align-items-center gap-2">
                                    <a href="{{url('appointment-calendar')}}">
                                        <i class="ti ti-calendar-cog"></i>
                                    </a>
                                    <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#doctor_detil">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="{{url('doctor-details')}}" class="avatar me-2">
                                        <img src="{{URL::asset('build/img/doctors/doctor-04.jpg')}}" alt="Doctor" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="mb-1 fs-14 fw-semibold"><a href="{{url('doctor-details')}}">Dr. David Lee</a></h6>
                                        <span class="fs-13 d-block">Gynecologist</span>
                                    </div>
                                </div>
                            </td>
                            <td>Gynecology</td>
                            <td>+1 54114 54586</td>
                            <td>
                                <div class="availabity-check d-flex align-items-center gap-2" role="group" aria-label="Basic radio toggle button group">
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio22" checked="">
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio22">M</label>
                                    </div>
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio23" checked>
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio23">T</label>
                                    </div>
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio24">
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio24">W</label>
                                    </div>   
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio25" checked>
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio25">T</label>
                                    </div>   
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio26" checked>
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio26">F</label>
                                    </div>   
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio27">
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio27">S</label>
                                    </div>   
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio28">
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio28">S</label>
                                    </div>   
                                </div>
                            </td>
                            <td>
                                <div class="action-item d-flex align-items-center gap-2">
                                    <a href="{{url('appointment-calendar')}}">
                                        <i class="ti ti-calendar-cog"></i>
                                    </a>
                                    <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#doctor_detil">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="{{url('doctor-details')}}" class="avatar me-2">
                                        <img src="{{URL::asset('build/img/doctors/doctor-05.jpg')}}" alt="Doctor" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="mb-1 fs-14 fw-semibold"><a href="{{url('doctor-details')}}">Dr. Anna Kim</a></h6>
                                        <span class="fs-13 d-block">Psychiatrist</span>
                                    </div>
                                </div>
                            </td>
                            <td>Psychiatry</td>
                            <td>+1 51247 54587</td>
                            <td>
                                <div class="availabity-check d-flex align-items-center gap-2" role="group" aria-label="Basic radio toggle button group">
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio29" checked="">
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio29">M</label>
                                    </div>
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio30" checked>
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio30">T</label>
                                    </div>
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio31">
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio31">W</label>
                                    </div>   
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio32" checked>
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio32">T</label>
                                    </div>   
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio33" checked>
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio33">F</label>
                                    </div>   
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio34">
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio34">S</label>
                                    </div>   
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio35">
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio35">S</label>
                                    </div>   
                                </div>
                            </td>
                            <td>
                                <div class="action-item d-flex align-items-center gap-2">
                                    <a href="{{url('appointment-calendar')}}">
                                        <i class="ti ti-calendar-cog"></i>
                                    </a>
                                    <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#doctor_detil">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="{{url('doctor-details')}}" class="avatar me-2">
                                        <img src="{{URL::asset('build/img/doctors/doctor-06.jpg')}}" alt="Doctor" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="mb-1 fs-14 fw-semibold"><a href="{{url('doctor-details')}}">Dr. John Smith</a></h6>
                                        <span class="fs-13 d-block">Neurosurgeon</span>
                                    </div>
                                </div>
                            </td>
                            <td>Neurology</td>
                            <td>+1 41452 54588</td>
                            <td>
                                <div class="availabity-check d-flex align-items-center gap-2" role="group" aria-label="Basic radio toggle button group">
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio36" checked="">
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio36">M</label>
                                    </div>
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio37" checked>
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio37">T</label>
                                    </div>
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio38">
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio38">W</label>
                                    </div>   
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio39" checked>
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio39">T</label>
                                    </div>   
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio40" checked>
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio40">F</label>
                                    </div>   
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio41">
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio41">S</label>
                                    </div>   
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio42">
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio42">S</label>
                                    </div>   
                                </div>
                            </td>
                            <td>
                                <div class="action-item d-flex align-items-center gap-2">
                                    <a href="{{url('appointment-calendar')}}">
                                        <i class="ti ti-calendar-cog"></i>
                                    </a>
                                    <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#doctor_detil">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="{{url('doctor-details')}}" class="avatar me-2">
                                        <img src="{{URL::asset('build/img/doctors/doctor-07.jpg')}}" alt="Doctor" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="mb-1 fs-14 fw-semibold"><a href="{{url('doctor-details')}}">Dr. Lisa White</a></h6>
                                        <span class="fs-13 d-block">Oncologist</span>
                                    </div>
                                </div>
                            </td>
                            <td>Oncologist</td>
                            <td>+1 51425 54589</td>
                            <td>
                                <div class="availabity-check d-flex align-items-center gap-2" role="group" aria-label="Basic radio toggle button group">
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio43" checked="">
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio43">M</label>
                                    </div>
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio44" checked>
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio44">T</label>
                                    </div>
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio45">
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio45">W</label>
                                    </div>   
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio46" checked>
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio46">T</label>
                                    </div>   
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio47" checked>
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio47">F</label>
                                    </div>   
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio48">
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio48">S</label>
                                    </div>   
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio49">
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio49">S</label>
                                    </div>   
                                </div>
                            </td>
                            <td>
                                <div class="action-item d-flex align-items-center gap-2">
                                    <a href="{{url('appointment-calendar')}}">
                                        <i class="ti ti-calendar-cog"></i>
                                    </a>
                                    <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#doctor_detil">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="{{url('doctor-details')}}" class="avatar me-2">
                                        <img src="{{URL::asset('build/img/doctors/doctor-08.jpg')}}" alt="Doctor" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="mb-1 fs-14 fw-semibold"><a href="{{url('doctor-details')}}">Dr. Patricia Brown</a></h6>
                                        <span class="fs-13 d-block">Pulmonologist</span>
                                    </div>
                                </div>
                            </td>
                            <td>Pulmonologist</td>
                            <td>+1 42565 54590</td>
                            <td>
                                <div class="availabity-check d-flex align-items-center gap-2" role="group" aria-label="Basic radio toggle button group">
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio50" checked="">
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio50">M</label>
                                    </div>
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio51" checked>
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio51">T</label>
                                    </div>
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio52">
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio52">W</label>
                                    </div>   
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio53" checked>
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio53">T</label>
                                    </div>   
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio54" checked>
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio54">F</label>
                                    </div>   
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio55">
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio55">S</label>
                                    </div>   
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio56">
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio56">S</label>
                                    </div>   
                                </div>
                            </td>
                            <td>
                                <div class="action-item d-flex align-items-center gap-2">
                                    <a href="{{url('appointment-calendar')}}">
                                        <i class="ti ti-calendar-cog"></i>
                                    </a>
                                    <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#doctor_detil">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="{{url('doctor-details')}}" class="avatar me-2">
                                        <img src="{{URL::asset('build/img/doctors/doctor-09.jpg')}}" alt="Doctor" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="mb-1 fs-14 fw-semibold"><a href="{{url('doctor-details')}}">Dr. Rachel Green</a></h6>
                                        <span class="fs-13 d-block">Urologist</span>
                                    </div>
                                </div>
                            </td>
                            <td>Urologist</td>
                            <td>+1 45214 54591</td>
                            <td>
                                <div class="availabity-check d-flex align-items-center gap-2" role="group" aria-label="Basic radio toggle button group">
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio57" checked="">
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio57">M</label>
                                    </div>
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio58" checked>
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio58">T</label>
                                    </div>
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio59">
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio59">W</label>
                                    </div>   
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio60" checked>
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio60">T</label>
                                    </div>   
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio61" checked>
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio61">F</label>
                                    </div>   
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio62">
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio62">S</label>
                                    </div>   
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio63">
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio63">S</label>
                                    </div>   
                                </div>
                            </td>
                            <td>
                                <div class="action-item d-flex align-items-center gap-2">
                                    <a href="{{url('appointment-calendar')}}">
                                        <i class="ti ti-calendar-cog"></i>
                                    </a>
                                    <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#doctor_detil">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="{{url('doctor-details')}}" class="avatar me-2">
                                        <img src="{{URL::asset('build/img/doctors/doctor-09.jpg')}}" alt="Doctor" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="mb-1 fs-14 fw-semibold"><a href="{{url('doctor-details')}}">Dr. Michael Smith</a></h6>
                                        <span class="fs-13 d-block">Cardiologist</span>
                                    </div>
                                </div>
                            </td>
                            <td>Cardiologist</td>
                            <td>+1 41245 54592</td>
                            <td>
                                <div class="availabity-check d-flex align-items-center gap-2" role="group" aria-label="Basic radio toggle button group">
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio64" checked="">
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio64">M</label>
                                    </div>
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio65" checked>
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio65">T</label>
                                    </div>
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio66">
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio66">W</label>
                                    </div>   
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio67" checked>
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio67">T</label>
                                    </div>   
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio68" checked>
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio68">F</label>
                                    </div>   
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio69">
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio69">S</label>
                                    </div>   
                                    <div>
                                        <input type="checkbox" class="btn-check" name="btnradio" id="btnradio70">
                                        <label class="btn btn-icon btn-sm rounded-circle btn-light" for="btnradio70">S</label>
                                    </div>   
                                </div>
                            </td>
                            <td>
                                <div class="action-item d-flex align-items-center gap-2">
                                    <a href="{{url('appointment-calendar')}}">
                                        <i class="ti ti-calendar-cog"></i>
                                    </a>
                                    <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#doctor_detil">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
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