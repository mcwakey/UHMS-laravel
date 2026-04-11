<?php $page = 'leaves'; ?>
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
                    <h4 class="fw-bold mb-0">Admin Leaves</h4>
                </div>
                <div class="text-end d-flex">
                    <a href="javascript:void(0);" class="btn btn-primary ms-2 fs-13 btn-md" data-bs-toggle="modal" data-bs-target="#add_leave"><i class="ti ti-plus me-1"></i>New Leave</a>
                </div>
            </div>
            <!-- End Page Header -->

            <!-- start row -->
            <div class="row">
                <div class="col-lg-3">
                    <div class="card">
                        <div class="card-body position-relative">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <p>Total Present</p>
                                    <div class="d-flex align-items-center">
                                        <p class="mb-0 me-2"><span class="text-dark fw-bold">180</span></p>
                                        <span class="badge badge-soft-success fs-12 fw-normal">+10.6%<i class="ti ti-arrow-up-right ms-1"></i></span>
                                    </div>
                                </div>
                                <span class="p-2 bg-soft-primary border border-primary rounded-circle d-inline-flex align-items-center justify-content-center text-primary position-relative z-1"><i class="ti ti-user-check"></i></span>
                            </div>
                            <img src="{{URL::asset('build/img/bg/attendance-bg-01.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0">
                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div><!-- end col -->
                <div class="col-lg-3">
                    <div class="card">
                        <div class="card-body position-relative">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <p>Planned Leaves</p>
                                    <div class="d-flex align-items-center">
                                        <p class="mb-0 me-2"><span class="text-dark fw-bold">10</span></p>
                                        <span class="badge badge-soft-success fs-12 fw-normal">+8.95%<i class="ti ti-arrow-up-right ms-1"></i></span>
                                    </div>
                                </div>
                                <span class="p-2 bg-soft-success border border-success rounded-circle d-inline-flex align-items-center justify-content-center text-success position-relative z-1"><i class="ti ti-user-x"></i></span>
                            </div>
                            <img src="{{URL::asset('build/img/bg/attendance-bg-02.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0">
                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div><!-- end col -->
                <div class="col-lg-3">
                    <div class="card">
                        <div class="card-body position-relative">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <p>Unplanned Leaves</p>
                                    <div class="d-flex align-items-center">
                                        <p class="mb-0 me-2"><span class="text-dark fw-bold">50</span></p>
                                        <span class="badge badge-soft-success fs-12 fw-normal">+3.78%<i class="ti ti-arrow-up-right ms-1"></i></span>
                                    </div>
                                </div>
                                <span class="p-2 bg-soft-warning border border-warning rounded-circle d-inline-flex align-items-center justify-content-center text-warning position-relative z-1"><i class="ti ti-user-exclamation"></i></span>
                            </div>
                            <img src="{{URL::asset('build/img/bg/attendance-bg-03.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0">
                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div><!-- end col -->
                <div class="col-lg-3">
                    <div class="card">
                        <div class="card-body position-relative">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <p>Pending Requests</p>
                                    <div class="d-flex align-items-center">
                                        <p class="mb-0 me-2"><span class="text-dark fw-bold">15</span></p>
                                        <span class="badge badge-soft-success fs-12 fw-normal">+7.65%<i class="ti ti-arrow-up-right ms-1"></i></span>
                                    </div>
                                </div>
                                <span class="p-2 bg-soft-danger border border-danger rounded-circle d-inline-flex align-items-center justify-content-center text-danger position-relative z-1"><i class="ti ti-user-question"></i></span>
                            </div>
                            <img src="{{URL::asset('build/img/bg/attendance-bg-04.png')}}" alt="" class="img-fluid position-absolute bottom-0 end-0">
                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div><!-- end col -->
            </div>
            <!-- end row -->

            <div class=" d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                <div class="search-set mb-3">
                    <div class="d-flex align-items-center">
                        <div class="table-search d-flex align-items-center mb-0 me-2">
                            <div class="search-input">
                                <a href="javascript:void(0);" class="btn-searchset"></a>
                            </div>
                        </div>
                        <div class="d-flex right-content align-items-center flex-wrap">
                            <div id="reportrange" class="reportrange-picker d-flex align-items-center">
                                <i class="ti ti-calendar text-gray-5 fs-14 me-1"></i><span class="reportrange-picker-field">16 Apr 25 - 16 Apr 25</span>
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
                                <h5 class="mb-0 fw-bold">Filter</h5>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="link-danger text-decoration-underline">Clear All</a>
                                </div>
                            </div>
                            <form action="#">
                                <div class="filter-body pb-0">
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <label class="form-label">Employee</label>
                                            <a href="javascript:void(0);" class="link-primary mb-1">Reset</a>
                                        </div>
                                        <select class="select2" multiple="multiple">
                                            <option value="m-1" selected>James Adair</option>
                                            <option value="m-2">Adam Milne</option>
                                            <option value="m-3">Richard Clark</option>
                                            <option value="m-4">Robert Reid</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <label class="form-label">Leave Type</label>
                                            <a href="javascript:void(0);" class="link-primary mb-1">Reset</a>
                                        </div>
                                        <select class="select2" multiple="multiple">
                                            <option value="m-1" selected>Sick Leave</option>
                                            <option value="m-2">Casual Leave</option>
                                            <option value="m-3">Emergency</option>
                                            <option value="m-4">Vacation</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label mb-1 text-dark fs-14 fw-medium">Date</label>
                                        <div class="input-icon-end position-relative">  
                                            <input type="text" class="form-control datetimepicker" placeholder="dd/mm/yyyy">
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
                                        <select class="select2" multiple="multiple">
                                            <option value="m-1" selected>Approved</option>
                                            <option value="m-2">Rejected</option>
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
                                <a href="javascript:void(0);" class="dropdown-item rounded-1">Recent</a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1">Oldest</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-nowrap datatable">
                    <thead class="thead-light">
                        <tr>
                            <th>ID</th>
                            <th>Employee</th>
                            <th>Leave Type</th>
                            <th>Date</th>
                            <th>Day</th>
                            <th>Applied On</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>#EMP020</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar me-2" data-bs-toggle="modal" data-bs-target="#view_staff">
                                        <img src="{{URL::asset('build/img/users/user-08.jpg')}}" alt="Doctor" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="mb-0 fs-14 fw-semibold"><a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#view_staff">James Adair</a></h6>
                                    </div>
                                </div>
                            </td>
                            <td>Sick Leave</td>
                            <td>30 Apr 2025 - 30 Apr 2025</td>
                            <td>01 Day</td>
                            <td>22 Apr 2025</td>
                            <td><span class="badge badge-soft-success border border-success px-2 py-1 fs-13 fw-medium">Approved</span></td>
                            <td>
                                <div class="action-item p-2">
                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_leave">Edit</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_leave">Delete</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>#EMP019</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar me-2" data-bs-toggle="modal" data-bs-target="#view_staff">
                                        <img src="{{URL::asset('build/img/users/user-03.jpg')}}" alt="Doctor" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="mb-0 fs-14 fw-semibold"><a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#view_staff">Adam Milne</a></h6>
                                    </div>
                                </div>
                            </td>
                            <td>Casual Leave</td>
                            <td>15 Apr 2025 - 15 Apr 2025</td>
                            <td>02 Days</td>
                            <td>12 Apr 2025</td>
                            <td><span class="badge badge-soft-danger border border-danger px-2 py-1 fs-13 fw-medium">Rejected</span></td>
                            <td>
                                <div class="action-item p-2">
                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_leave">Edit</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_leave">Delete</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>#EMP018</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar me-2" data-bs-toggle="modal" data-bs-target="#view_staff">
                                        <img src="{{URL::asset('build/img/users/user-04.jpg')}}" alt="Doctor" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="mb-0 fs-14 fw-semibold"><a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#view_staff">Richard Clark</a></h6>
                                    </div>
                                </div>
                            </td>
                            <td>Emergency</td>
                            <td>02 Apr 2025 - 03 Apr 2025</td>
                            <td>03 Days</td>
                            <td>01 Apr 2025</td>
                            <td><span class="badge badge-soft-success border border-success px-2 py-1 fs-13 fw-medium">Approved</span></td>
                            <td>
                                <div class="action-item p-2">
                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_leave">Edit</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_leave">Delete</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>#EMP017</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar me-2" data-bs-toggle="modal" data-bs-target="#view_staff">
                                        <img src="{{URL::asset('build/img/users/user-05.jpg')}}" alt="Doctor" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="mb-0 fs-14 fw-semibold"><a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#view_staff">Robert Reid</a></h6>
                                    </div>
                                </div>
                            </td>
                            <td>Vacation</td>
                            <td>12 Mar 2025 - 13 Mar 2025</td>
                            <td>01 Day</td>
                            <td>05 Mar 2025</td>
                            <td><span class="badge badge-soft-danger border border-danger px-2 py-1 fs-13 fw-medium">Rejected</span></td>
                            <td>
                                <div class="action-item p-2">
                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_leave">Edit</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_leave">Delete</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>#EMP016</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar me-2" data-bs-toggle="modal" data-bs-target="#view_staff">
                                        <img src="{{URL::asset('build/img/users/user-06.jpg')}}" alt="Doctor" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="mb-0 fs-14 fw-semibold"><a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#view_staff">Dottie Jeny</a></h6>
                                    </div>
                                </div>
                            </td>
                            <td>Maternity</td>
                            <td>27 Mar 2025 - 29 Mar 2025</td>
                            <td>02 Days</td>
                            <td>20 Mar 2025</td>
                            <td><span class="badge badge-soft-success border border-success px-2 py-1 fs-13 fw-medium">Approved</span></td>
                            <td>
                                <div class="action-item p-2">
                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_leave">Edit</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_leave">Delete</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>#EMP015</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar me-2" data-bs-toggle="modal" data-bs-target="#view_staff">
                                        <img src="{{URL::asset('build/img/users/user-07.jpg')}}" alt="Doctor" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="mb-0 fs-14 fw-semibold"><a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#view_staff">Cheryl Bilodeau</a></h6>
                                    </div>
                                </div>
                            </td>
                            <td>Paternity</td>
                            <td>05 Mar 2025 - 05 Mar 2025</td>
                            <td>01 Day</td>
                            <td>01 Mar 2025</td>
                            <td><span class="badge badge-soft-danger border border-danger px-2 py-1 fs-13 fw-medium">Rejected</span></td>
                            <td>
                                <div class="action-item p-2">
                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_leave">Edit</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_leave">Delete</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>#EMP014</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar me-2" data-bs-toggle="modal" data-bs-target="#view_staff">
                                        <img src="{{URL::asset('build/img/users/user-02.jpg')}}" alt="Doctor" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="mb-0 fs-14 fw-semibold"><a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#view_staff">Valerie Padgett</a></h6>
                                    </div>
                                </div>
                            </td>
                            <td>Sick Leave</td>
                            <td>24 Feb 2025 - 25 Feb 2025</td>
                            <td>02 Days</td>
                            <td>15 Feb 2025</td>
                            <td><span class="badge badge-soft-danger border border-danger px-2 py-1 fs-13 fw-medium">Rejected</span></td>
                            <td>
                                <div class="action-item p-2">
                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_leave">Edit</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_leave">Delete</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>#EMP013</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar me-2" data-bs-toggle="modal" data-bs-target="#view_staff">
                                        <img src="{{URL::asset('build/img/users/user-25.jpg')}}" alt="Doctor" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="mb-0 fs-14 fw-semibold"><a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#view_staff">Diane Nash</a></h6>
                                    </div>
                                </div>
                            </td>
                            <td>Casual Leave</td>
                            <td>16 Feb 2025 - 17 Feb 2025</td>
                            <td>01 Day</td>
                            <td>09 Feb 2025</td>
                            <td><span class="badge badge-soft-success border border-success px-2 py-1 fs-13 fw-medium">Approved</span></td>
                            <td>
                                <div class="action-item p-2">
                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_leave">Edit</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_leave">Delete</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>#EMP012</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar me-2" data-bs-toggle="modal" data-bs-target="#view_staff">
                                        <img src="{{URL::asset('build/img/users/user-29.jpg')}}" alt="Doctor" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="mb-0 fs-14 fw-semibold"><a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#view_staff">Sally Cavazos</a></h6>
                                    </div>
                                </div>
                            </td>
                            <td>Emergency</td>
                            <td>01 Feb 2025 - 01 Feb 2025</td>
                            <td>02 Days</td>
                            <td>01 Feb 2025</td>
                            <td><span class="badge badge-soft-success border border-success px-2 py-1 fs-13 fw-medium">Approved</span></td>
                            <td>
                                <div class="action-item p-2">
                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_leave">Edit</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_leave">Delete</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>#EMP011</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar me-2" data-bs-toggle="modal" data-bs-target="#view_staff">
                                        <img src="{{URL::asset('build/img/users/user-12.jpg')}}" alt="Doctor" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="mb-0 fs-14 fw-semibold"><a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#view_staff">Forest Heath</a></h6>
                                    </div>
                                </div>
                            </td>
                            <td>Vacation</td>
                            <td>25 Jan 2025 - 29 Jan 2025</td>
                            <td>05 Days</td>
                            <td>12 Jan 2025</td>
                            <td><span class="badge badge-soft-danger border border-danger px-2 py-1 fs-13 fw-medium">Rejected</span></td>
                            <td>
                                <div class="action-item p-2">
                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_leave">Edit</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_leave">Delete</a>
                                        </li>
                                    </ul>
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