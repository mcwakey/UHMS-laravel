<?php $page = 'edit-doctor'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- ========================
        Start Page Content
    ========================= -->

    <div class="page-wrapper">

            <!-- Start Content -->
            <div class="content">

                <div class="row">
                    <div class="col-lg-10 mx-auto">

                        <!-- Start Page Header -->
                        <div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3">
                            <div class="flex-grow-1">
                                <h6 class="fw-bold mb-0 d-flex align-items-center"><a href="{{url('doctors')}}"><i class="ti ti-chevron-left me-1 fs-14"></i>Doctor</a></h6>
                            </div>
                        </div>
                        <!-- End Page Header -->

                        <!-- Start Add Doctor -->

                         <div class="card mb-0">
                            <!-- <div class="card-header">
                                
                            </div> -->
                            <div class="card-body">
                                <div class="border-bottom d-flex align-items-center justify-content-between pb-3 mb-3">
                                    <h5 class="offcanvas-title fs-18 fw-bold">Edit Doctor</h5>
                                </div>
                                <form action="{{url('doctors')}}">
                                    <div class="px-3 py-2 mb-3 feild-head">
                                        <h6 class="fw-bold mb-0">Contact Information</h6>
                                    </div>
                                    <div class="pb-0">

                                        <!-- start row-->
                                        <div class="row">

                                            <div class="col-lg-12">
                                                <div class="mb-3 d-flex align-items-center">
                                                    <label class="form-label">Profile Image</label>
                                                    <div class="drag-upload-btn avatar avatar-xxl rounded-circle bg-light text-muted position-relative overflow-hidden z-1 mb-2 ms-4 p-0">
                                                        <img src="{{URL::asset('build/img/doctors/doctor-01.jpg')}}" class="position-relative z-n1" alt="">
                                                        <input type="file" class="form-control image-sign" multiple="">
                                                        <div class="position-absolute bottom-0 end-0 star-0 w-100 h-25 bg-dark d-flex align-items-center justify-content-center z-n1">
                                                            <a href="javascript:void(0);" class="text-white d-flex align-items-center justify-content-center">
                                                                <i class="ti ti-photo fs-14"></i>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div><!-- end col-->

                                            <div class="col-lg-12">

                                                <div class="row">

                                                    <div class="col-lg-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Name <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control" value="Dr.Mick Thompson">
                                                        </div>
                                                    </div><!-- end col-->

                                                    <div class="col-lg-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Username <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control" value="Andrew">
                                                        </div>
                                                    </div><!-- end col-->

                                                    <div class="col-lg-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control" value="+1 47895 58974">
                                                        </div>
                                                    </div><!-- end col-->

                                                    <div class="col-lg-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control" value="mick@example.com">
                                                        </div>
                                                    </div><!-- end col-->
                                                    
                                                </div>
                                            </div><!-- end col-->

                                            <div class="col-lg-12">
                                                <div class="row">
                                                    <div class="col-lg-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">DOB <span class="text-danger">*</span></label>
                                                            <div class="input-icon-end position-relative">  
                                                                <input type="text" class="form-control datetimepicker" placeholder="dd/mm/yyyy">
                                                                <span class="input-icon-addon">
                                                                    <i class="ti ti-calendar"></i>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Year Of Experience <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control" value="+5 Years">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div><!-- end col-->

                                            <div class="col-lg-12">
                                                <div class="row">
                                                    <div class="col-lg-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Department<span class="text-danger ms-1">*</span></label>
                                                            <select class="select">
                                                                <option>Select</option>
                                                                <option selected>Cardiology </option>
                                                                <option>Orthopedics</option>
                                                                <option>Pediatrics</option>
                                                                <option>Gynecology</option>
                                                                <option>Psychiatry</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Designation <span class="text-danger ms-1">*</span></label>
                                                            <select class="select">
                                                                <option>Select</option>
                                                                <option selected>Cardiologist</option>
                                                                <option>Orthopedic Surgeon</option>
                                                                <option>Pediatrics</option>
                                                                <option>Gynecology</option>
                                                                <option>Psychiatry</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div><!-- end col-->

                                            <div class="col-lg-12">
                                                <div class="row">
                                                    <div class="col-lg-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Medical License Number <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control" value="MGF14578">
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Language Spoken</label>
                                                            <input class="input-tags form-control" id="inputBox" type="text" data-role="tagsinput" name="specialist" value="English,French">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div><!-- end col-->

                                            <div class="col-lg-12">
                                                <div class="row">
                                                    <div class="col-lg-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Blood Group<span class="text-danger ms-1">*</span></label>
                                                            <select class="select">
                                                                <option>Select</option>
                                                                <option selected>O+</option>
                                                                <option>O-</option>
                                                                <option>A+</option>
                                                                <option>A-</option>
                                                                <option>B+</option>
                                                                <option>B-</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Gender <span class="text-danger ms-1">*</span></label>
                                                            <select class="select">
                                                                <option>Select</option>
                                                                <option selected>Male</option>
                                                                <option>Female</option>
                                                                <option>Others</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div><!-- end col-->

                                            <div class="col-lg-12">
                                                <div class="mb-3">
                                                    <label class="form-label">Bio</label>
                                                    <textarea class="form-control" rows="3">Dr.Mick Thompson is a compassionate and experienced internal medicine physician with over 5 years of clinical practice.</textarea>
                                                </div>
                                                <div class="form-check form-switch mb-3">
                                                    <label class="form-check-label" for="switchCheckDefault3">Feature On Website</label>
                                                    <input class="form-check-input" type="checkbox" role="switch" id="switchCheckDefault3">
                                                </div>
                                            </div>

                                        </div>
                                        <!-- end row-->
                                    </div>
                                    <div class="px-3 py-2 mb-3 feild-head">
                                        <h6 class="fw-bold mb-0">Address Information</h6>
                                    </div>
                                    <div class="pb-0">
                                        <div class="row">
                                            <div class="col-lg-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Address 1</label>
                                                    <input type="text" class="form-control" value="2900 Alpha Avenue">
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Address 2 </label>
                                                    <input type="text" class="form-control" value="2900 Alpha Avenue">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Country</label>
                                                    <select class="select">
                                                        <option>Select</option>
                                                        <option selected>United States</option>
                                                        <option>India</option>
                                                        <option>Canada</option>
                                                        <option>Germany</option>
                                                        <option>Brazil</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="mb-3">
                                                    <label class="form-label">City</label>
                                                    <select class="select">
                                                        <option>Select</option>
                                                        <option selected>Los Angeles</option>
                                                        <option>San Francisco</option>
                                                        <option>San Francisco</option>
                                                        <option>San Jose</option>
                                                        <option>Fresno</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-6">
                                                <div class="mb-3">
                                                    <label class="form-label">State</label>
                                                    <select class="select">
                                                        <option>Select</option>
                                                        <option selected>California</option>
                                                        <option>Texas</option>
                                                        <option>New York</option>
                                                        <option>Florida</option>
                                                        <option>Illinois</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Pincode</label>
                                                    <input type="text" class="form-control" value="PA 15650">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="px-3 py-2 mb-3 feild-head">
                                        <h6 class="fw-bold mb-0">Address Information</h6>
                                    </div>
                                    <div>
                                        <ul class="nav nav-pills schedule-tab mb-3" id="pills-tab2" role="tablist">
                                            <li class="nav-item me-1" role="presentation">
                                                <button class="nav-link btn btn-sm btn-icon p-2 d-flex align-items-center justify-content-center w-auto active" data-bs-toggle="pill" data-bs-target="#schedules-8" type="button" role="tab" aria-selected="true">Monday</button>
                                            </li>
                                            <li class="nav-item me-1" role="presentation">
                                                <button class="nav-link btn btn-sm btn-icon p-2 d-flex align-items-center justify-content-center w-auto" data-bs-toggle="pill" data-bs-target="#schedules-9" type="button" role="tab" aria-selected="false" tabindex="-1">Tuesday</button>
                                            </li>
                                            <li class="nav-item me-1" role="presentation">
                                                <button class="nav-link btn btn-sm btn-icon p-2 d-flex align-items-center justify-content-center w-auto" data-bs-toggle="pill" data-bs-target="#schedules-10" type="button" role="tab" aria-selected="false" tabindex="-1">Wednesday</button>
                                            </li>
                                            <li class="nav-item me-1" role="presentation">
                                                <button class="nav-link btn btn-sm btn-icon p-2 d-flex align-items-center justify-content-center w-auto" data-bs-toggle="pill" data-bs-target="#schedules-11" type="button" role="tab" aria-selected="false" tabindex="-1">Thursday</button>
                                            </li>
                                            <li class="nav-item me-1" role="presentation">
                                                <button class="nav-link btn btn-sm btn-icon p-2 d-flex align-items-center justify-content-center w-auto" data-bs-toggle="pill" data-bs-target="#schedules-12" type="button" role="tab" aria-selected="false" tabindex="-1">Friday</button>
                                            </li>
                                            <li class="nav-item me-1" role="presentation">
                                                <button class="nav-link btn btn-sm btn-icon p-2 d-flex align-items-center justify-content-center w-auto" data-bs-toggle="pill" data-bs-target="#schedules-13" type="button" role="tab" aria-selected="false" tabindex="-1">Saturday</button>
                                            </li>
                                            <li class="nav-item me-1" role="presentation">
                                                <button class="nav-link btn btn-sm btn-icon p-2 d-flex align-items-center justify-content-center w-auto" data-bs-toggle="pill" data-bs-target="#schedules-14" type="button" role="tab" aria-selected="false" tabindex="-1">Sunday</button>
                                            </li>
                                        </ul>
                                        <div class="tab-content" id="pills-tabContent2">
                                            <div class="tab-pane fade active show" id="schedules-8" role="tabpanel">
                                                <div class="add-schedule-list">
                                                    <div class="row gx-3">
                                                        <div class="col-lg-5">
                                                            <div class="mb-3">
                                                                <label class="form-label">Session</label>
                                                                <select class="select">
                                                                    <option>Select</option>
                                                                    <option selected>Morning</option>
                                                                    <option>Noon</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-7">
                                                            <div class="row align-items-end gx-3">
                                                                <div class="col-lg-9">
                                                                    <div class="row gx-3">
                                                                        <div class="col-lg-6">
                                                                            <div class="mb-3">
                                                                                <label class="form-label">From</label>
                                                                                <div class="input-icon-end position-relative">  
                                                                                    <input type="text" class="form-control timepicker" placeholder="03 : 05  AM">
                                                                                    <span class="input-icon-addon">
                                                                                        <i class="ti ti-clock-hour-10"></i>
                                                                                    </span>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-lg-6">
                                                                            <div class="mb-3">
                                                                                <label class="form-label">To</label>
                                                                                <div class="input-icon-end position-relative">  
                                                                                    <input type="text" class="form-control timepicker" placeholder="03 : 05  AM">
                                                                                    <span class="input-icon-addon">
                                                                                        <i class="ti ti-clock-hour-10"></i>
                                                                                    </span>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-lg-3">
                                                                    <div class="mb-3">
                                                                        <a href="#" class="add-schedule-btn p-2 bg-light btn-icon text-body rounded d-flex align-items-center justify-content-center">
                                                                            <i class="ti ti-plus fs-16"></i>
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="tab-pane fade" id="schedules-9" role="tabpanel">
                                                <div class="add-schedule-list">
                                                    <div class="row gx-3">
                                                        <div class="col-lg-5">
                                                            <div class="mb-3">
                                                                <label class="form-label">Session</label>
                                                                <select class="select">
                                                                    <option>Select</option>
                                                                    <option>Morning</option>
                                                                    <option>Noon</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-7">
                                                            <div class="row align-items-end gx-3">
                                                                <div class="col-lg-9">
                                                                    <div class="row gx-3">
                                                                        <div class="col-lg-6">
                                                                            <div class="mb-3">
                                                                                <label class="form-label">From</label>
                                                                                <div class="input-icon-end position-relative">  
                                                                                    <input type="text" class="form-control timepicker" placeholder="03 : 05  AM">
                                                                                    <span class="input-icon-addon">
                                                                                        <i class="ti ti-clock-hour-10"></i>
                                                                                    </span>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-lg-6">
                                                                            <div class="mb-3">
                                                                                <label class="form-label">To</label>
                                                                                <div class="input-icon-end position-relative">  
                                                                                    <input type="text" class="form-control timepicker" placeholder="03 : 05  AM">
                                                                                    <span class="input-icon-addon">
                                                                                        <i class="ti ti-clock-hour-10"></i>
                                                                                    </span>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-lg-3">
                                                                    <div class="mb-3">
                                                                        <a href="#" class="add-schedule-btn p-2 bg-light btn-icon text-body rounded d-flex align-items-center justify-content-center">
                                                                            <i class="ti ti-plus fs-16"></i>
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="tab-pane fade" id="schedules-10" role="tabpanel">
                                                <div class="add-schedule-list">
                                                    <div class="row gx-3">
                                                        <div class="col-lg-5">
                                                            <div class="mb-3">
                                                                <label class="form-label">Session</label>
                                                                <select class="select">
                                                                    <option>Select</option>
                                                                    <option>Morning</option>
                                                                    <option>Noon</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-7">
                                                            <div class="row align-items-end gx-3">
                                                                <div class="col-lg-9">
                                                                    <div class="row gx-3">
                                                                        <div class="col-lg-6">
                                                                            <div class="mb-3">
                                                                                <label class="form-label">From</label>
                                                                                <div class="input-icon-end position-relative">  
                                                                                    <input type="text" class="form-control timepicker" placeholder="03 : 05  AM">
                                                                                    <span class="input-icon-addon">
                                                                                        <i class="ti ti-clock-hour-10"></i>
                                                                                    </span>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-lg-6">
                                                                            <div class="mb-3">
                                                                                <label class="form-label">To</label>
                                                                                <div class="input-icon-end position-relative">  
                                                                                    <input type="text" class="form-control timepicker" placeholder="03 : 05  AM">
                                                                                    <span class="input-icon-addon">
                                                                                        <i class="ti ti-clock-hour-10"></i>
                                                                                    </span>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-lg-3">
                                                                    <div class="mb-3">
                                                                        <a href="#" class="add-schedule-btn p-2 bg-light btn-icon text-body rounded d-flex align-items-center justify-content-center">
                                                                            <i class="ti ti-plus fs-16"></i>
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="tab-pane fade" id="schedules-11" role="tabpanel">
                                                <div class="add-schedule-list">
                                                    <div class="row gx-3">
                                                        <div class="col-lg-5">
                                                            <div class="mb-3">
                                                                <label class="form-label">Session</label>
                                                                <select class="select">
                                                                    <option>Select</option>
                                                                    <option>Morning</option>
                                                                    <option>Noon</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-7">
                                                            <div class="row align-items-end gx-3">
                                                                <div class="col-lg-9">
                                                                    <div class="row gx-3">
                                                                        <div class="col-lg-6">
                                                                            <div class="mb-3">
                                                                                <label class="form-label">From</label>
                                                                                <div class="input-icon-end position-relative">  
                                                                                    <input type="text" class="form-control timepicker" placeholder="03 : 05  AM">
                                                                                    <span class="input-icon-addon">
                                                                                        <i class="ti ti-clock-hour-10"></i>
                                                                                    </span>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-lg-6">
                                                                            <div class="mb-3">
                                                                                <label class="form-label">To</label>
                                                                                <div class="input-icon-end position-relative">  
                                                                                    <input type="text" class="form-control timepicker" placeholder="03 : 05  AM">
                                                                                    <span class="input-icon-addon">
                                                                                        <i class="ti ti-clock-hour-10"></i>
                                                                                    </span>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-lg-3">
                                                                    <div class="mb-3">
                                                                        <a href="#" class="add-schedule-btn p-2 bg-light btn-icon text-body rounded d-flex align-items-center justify-content-center">
                                                                            <i class="ti ti-plus fs-16"></i>
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="tab-pane fade" id="schedules-12" role="tabpanel">
                                                <div class="add-schedule-list">
                                                    <div class="row gx-3">
                                                        <div class="col-lg-5">
                                                            <div class="mb-3">
                                                                <label class="form-label">Session</label>
                                                                <select class="select">
                                                                    <option>Select</option>
                                                                    <option>Morning</option>
                                                                    <option>Noon</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-7">
                                                            <div class="row align-items-end gx-3">
                                                                <div class="col-lg-9">
                                                                    <div class="row gx-3">
                                                                        <div class="col-lg-6">
                                                                            <div class="mb-3">
                                                                                <label class="form-label">From</label>
                                                                                <div class="input-icon-end position-relative">  
                                                                                    <input type="text" class="form-control timepicker" placeholder="03 : 05  AM">
                                                                                    <span class="input-icon-addon">
                                                                                        <i class="ti ti-clock-hour-10"></i>
                                                                                    </span>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-lg-6">
                                                                            <div class="mb-3">
                                                                                <label class="form-label">To</label>
                                                                                <div class="input-icon-end position-relative">  
                                                                                    <input type="text" class="form-control timepicker" placeholder="03 : 05  AM">
                                                                                    <span class="input-icon-addon">
                                                                                        <i class="ti ti-clock-hour-10"></i>
                                                                                    </span>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-lg-3">
                                                                    <div class="mb-3">
                                                                        <a href="#" class="add-schedule-btn p-2 bg-light btn-icon text-body rounded d-flex align-items-center justify-content-center">
                                                                            <i class="ti ti-plus fs-16"></i>
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="tab-pane fade" id="schedules-13" role="tabpanel">
                                                <div class="add-schedule-list">
                                                    <div class="row gx-3">
                                                        <div class="col-lg-5">
                                                            <div class="mb-3">
                                                                <label class="form-label">Session</label>
                                                                <select class="select">
                                                                    <option>Select</option>
                                                                    <option>Morning</option>
                                                                    <option>Noon</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-7">
                                                            <div class="row align-items-end gx-3">
                                                                <div class="col-lg-9">
                                                                    <div class="row gx-3">
                                                                        <div class="col-lg-6">
                                                                            <div class="mb-3">
                                                                                <label class="form-label">From</label>
                                                                                <div class="input-icon-end position-relative">  
                                                                                    <input type="text" class="form-control timepicker" placeholder="03 : 05  AM">
                                                                                    <span class="input-icon-addon">
                                                                                        <i class="ti ti-clock-hour-10"></i>
                                                                                    </span>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-lg-6">
                                                                            <div class="mb-3">
                                                                                <label class="form-label">To</label>
                                                                                <div class="input-icon-end position-relative">  
                                                                                    <input type="text" class="form-control timepicker" placeholder="03 : 05  AM">
                                                                                    <span class="input-icon-addon">
                                                                                        <i class="ti ti-clock-hour-10"></i>
                                                                                    </span>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-lg-3">
                                                                    <div class="mb-3">
                                                                        <a href="#" class="add-schedule-btn p-2 bg-light btn-icon text-body rounded d-flex align-items-center justify-content-center">
                                                                            <i class="ti ti-plus fs-16"></i>
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="tab-pane fade" id="schedules-14" role="tabpanel">
                                                <div class="add-schedule-list">
                                                    <div class="row gx-3">
                                                        <div class="col-lg-5">
                                                            <div class="mb-3">
                                                                <label class="form-label">Session</label>
                                                                <select class="select">
                                                                    <option>Select</option>
                                                                    <option>Morning</option>
                                                                    <option>Noon</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-7">
                                                            <div class="row align-items-end gx-3">
                                                                <div class="col-lg-9">
                                                                    <div class="row gx-3">
                                                                        <div class="col-lg-6">
                                                                            <div class="mb-3">
                                                                                <label class="form-label">From</label>
                                                                                <div class="input-icon-end position-relative">  
                                                                                    <input type="text" class="form-control timepicker" placeholder="03 : 05  AM">
                                                                                    <span class="input-icon-addon">
                                                                                        <i class="ti ti-clock-hour-10"></i>
                                                                                    </span>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-lg-6">
                                                                            <div class="mb-3">
                                                                                <label class="form-label">To</label>
                                                                                <div class="input-icon-end position-relative">  
                                                                                    <input type="text" class="form-control timepicker" placeholder="03 : 05  AM">
                                                                                    <span class="input-icon-addon">
                                                                                        <i class="ti ti-clock-hour-10"></i>
                                                                                    </span>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-lg-3">
                                                                    <div class="mb-3">
                                                                        <a href="#" class="add-schedule-btn p-2 bg-light btn-icon text-body rounded d-flex align-items-center justify-content-center">
                                                                            <i class="ti ti-plus fs-16"></i>
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <a href="#" class="btn btn-dark">Apply All</a>
                                        </div>
                                    </div>
                                    <div class="px-3 py-2 mb-3 feild-head">
                                        <h6 class="fw-bold mb-0">Appointment Information</h6>
                                    </div>
                                    <div class="pb-0">
                                        <div class="row">
                                            <div class="col-lg-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Appointment Type</label>
                                                    <select class="select">
                                                        <option>Select</option>
                                                        <option selected>Online Consultation</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-6"></div>
                                            <div class="col-lg-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Accept bookings (in Advance)</label>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control"  value="2">
                                                        <span class="input-group-text bg-transparent text-dark fs-14">Days</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Appointment Duration</label>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control" value="30">
                                                        <span class="input-group-text bg-transparent text-dark fs-14">Mins</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Consultation Charge</label>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control" value="$100">
                                                        <span class="input-group-text bg-transparent text-dark fs-14">$</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Max Bookings Per Slot</label>
                                                    <input type="text" class="form-control" value="200">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch mb-3">
                                                    <label class="form-check-label" for="switchCheckDefault2">Display on Booking Page</label>
                                                    <input class="form-check-input" type="checkbox" role="switch" id="switchCheckDefault2">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="px-3 py-2 mb-3 feild-head">
                                        <h6 class="fw-bold mb-0">Educational Information</h6>
                                    </div>
                                    <div class="pb-0">
                                        <div class="add-education-list">
                                            <div class="row align-items-end">
                                                <div class="col-lg-11">
                                                    <div class="row">
                                                        <div class="col-lg-3">
                                                            <div class="mb-3">
                                                                <label class="form-label">Educational Degree</label>
                                                                <input type="text" class="form-control" value="M.B.B.S">
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-3">
                                                            <div class="mb-3">
                                                                <label class="form-label">University</label>
                                                                <input type="text" class="form-control" value="Harvard Medical School">
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-3">
                                                            <div class="mb-3">
                                                                <label class="form-label">From</label>
                                                                <div class="input-icon-end position-relative">  
                                                                    <input type="text" class="form-control datetimepicker" placeholder="dd/mm/yyyy">
                                                                    <span class="input-icon-addon">
                                                                        <i class="ti ti-calendar"></i>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-3">
                                                            <div class="mb-3">
                                                                <label class="form-label">To</label>
                                                                <div class="input-icon-end position-relative">  
                                                                    <input type="text" class="form-control datetimepicker" placeholder="dd/mm/yyyy">
                                                                    <span class="input-icon-addon">
                                                                        <i class="ti ti-calendar"></i>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-1">
                                                    <div class="mb-3">
                                                        <a href="#" class="add-education-btn p-2 bg-light btn-icon text-body rounded d-flex align-items-center justify-content-center">
                                                            <i class="ti ti-plus fs-16"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="px-3 py-2 mb-3 feild-head">
                                        <h6 class="fw-bold mb-0">Awards & Recognition</h6>
                                    </div>
                                    <div class="pb-0">
                                        <div class="add-award-list">
                                            <div class="row align-items-end">
                                                <div class="col-lg-11">
                                                    <div class="row">
                                                        <div class="col-lg-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Name</label>
                                                                <input type="text" class="form-control" value="Harvard Medical School">
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">From</label>
                                                                <div class="input-icon-end position-relative">  
                                                                    <input type="text" class="form-control datetimepicker" placeholder="dd/mm/yyyy">
                                                                    <span class="input-icon-addon">
                                                                        <i class="ti ti-calendar"></i>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-1">
                                                    <div class="mb-3">
                                                        <a href="#" class="add-award-btn p-2 bg-light btn-icon text-body rounded d-flex align-items-center justify-content-center">
                                                            <i class="ti ti-plus fs-16"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="px-3 py-2 mb-3 feild-head">
                                        <h6 class="fw-bold mb-0">Certifications</h6>
                                    </div>
                                    <div class="pb-3 mb-3 border-bottom">
                                        <div class="add-certification-list">
                                            <div class="row align-items-end">
                                                <div class="col-lg-11">
                                                    <div class="row">
                                                        <div class="col-lg-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Name</label>
                                                                <input type="text" class="form-control" value="Harvard Medical School">
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">From</label>
                                                                <div class="input-icon-end position-relative">  
                                                                    <input type="text" class="form-control datetimepicker" placeholder="dd/mm/yyyy">
                                                                    <span class="input-icon-addon">
                                                                        <i class="ti ti-calendar"></i>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-1">
                                                    <div class="mb-3">
                                                        <a href="#" class="add-certification-btn p-2 bg-light btn-icon text-body rounded d-flex align-items-center justify-content-center">
                                                            <i class="ti ti-plus fs-16"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class=" d-flex justify-content-end gap-2">
                                        <a href="javascript:void(0);" class="btn btn-light btm-md" data-bs-dismiss="offcanvas">Cancel</a>
                                        <button class="btn btn-primary btm-md">Save Changes</button>
                                    </div>

                                </form>
                            </div>
                            <!-- <div class="card-footer">
                                
                            </div> -->
                         </div>

                        <!-- End Add Doctor -->

                    </div>
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