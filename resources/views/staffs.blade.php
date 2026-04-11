<?php $page = 'staffs'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- ========================
        Start Page Content
    ========================= -->
        
    <div class="page-wrapper">

        <!-- Start Content -->
        <div class="content"  id="profilePage">

            <!-- Start Page Header -->
            <div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
                <div class="flex-grow-1">
                    <h4 class="fw-bold mb-0"> Staff<span class="badge badge-soft-primary border border-primary fs-13 fw-medium ms-2">Total Staffs : 565</span></h4>
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
                    <a href="javascript:void(0);" class="btn btn-primary ms-2 fs-13 btn-md" data-bs-toggle="modal" data-bs-target="#add_staff"><i class="ti ti-plus me-1"></i>Add Staff</a>
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
                                            <label class="form-label">Staff</label>
                                            <a href="javascript:void(0);" class="link-primary mb-1">Reset</a>
                                        </div>
                                        <select class="select2" multiple="multiple">
                                            <option value="m-1" selected>James Adair</option>
                                            <option value="m-2">Adam Milne</option>
                                            <option value="m-3">Richard Clark</option>
                                            <option value="m-4">Robert Reid</option>
                                            <option value="m-5">Dottie Jeny</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <label class="form-label">Designation</label>
                                            <a href="javascript:void(0);" class="link-primary mb-1">Reset</a>
                                        </div>
                                        <select class="select2" multiple="multiple">
                                            <option value="m-1" selected>Admin Officer</option>
                                            <option value="m-2">Front Office Executive</option>
                                            <option value="m-3">Medical Records Executive</option>
                                            <option value="m-4">Billing Executive</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <label class="form-label">Role</label>
                                            <a href="javascript:void(0);" class="link-primary mb-1">Reset</a>
                                        </div>
                                        <select class="select2" multiple="multiple">
                                            <option value="m-1" selected>Admin</option>
                                            <option value="m-2">Reception</option>
                                            <option value="m-3">Nurses</option>
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
                                        <div class="dropdown">
                                            <a href="javascript:void(0);" class="dropdown-toggle btn btn-lg d-flex align-items-center justify-content-start fs-13 fw-normal rounded p-2 border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                                Select
                                            </a>
                                            <div class="dropdown-menu shadow-lg w-100 dropdown-info">							
                                                <div class="filter-range">
                                                    <input type="text" id="range_03">
                                                    <p>Range : <span class="text-gray-9">Range : $200 - $5695</span></p>
                                                </div>
                                            </div>
                                        </div>
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
                            <th>Staff</th>
                            <th>Designation</th>
                            <th>Role</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar me-2" data-bs-toggle="modal" data-bs-target="#view_staff">
                                        <img src="{{URL::asset('build/img/users/user-08.jpg')}}" alt="Doctor" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="mb-1 fs-14 fw-semibold"><a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#view_staff">James Adair</a></h6>
                                    </div>
                                </div>
                            </td>
                            <td>Admin Officer</td>
                            <td>Admin</td>
                            <td>+1 41245 54132</td>
                            <td>james@gmail.com</td>
                            <td><span class="badge badge-soft-success border border-success">Available</span></td>
                            <td>
                                <div class="action-item">
                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </a>
                                    <ul class="dropdown-menu p-2">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#view_staff">View Details</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_staff">Edit</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_staff">Delete</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar me-2" data-bs-toggle="modal" data-bs-target="#view_staff">
                                        <img src="{{URL::asset('build/img/users/user-03.jpg')}}" alt="Doctor" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="mb-1 fs-14 fw-semibold"><a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#view_staff">Adam Milne</a></h6>
                                    </div>
                                </div>
                            </td>
                            <td>Front Officer </td>
                            <td>Reception</td>
                            <td>+1 54554 54789</td>
                            <td>adam@gmail.com</td>
                            <td><span class="badge badge-soft-success border border-success">Available</span></td>
                            <td>
                                <div class="action-item">
                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </a>
                                    <ul class="dropdown-menu p-2">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#view_staff">View Details</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_staff">Edit</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_staff">Delete</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar me-2" data-bs-toggle="modal" data-bs-target="#view_staff">
                                        <img src="{{URL::asset('build/img/users/user-04.jpg')}}" alt="Doctor" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="mb-1 fs-14 fw-semibold"><a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#view_staff">Richard Clark</a></h6>
                                    </div>
                                </div>
                            </td>
                            <td>Medical Recorder</td>
                            <td>Admin</td>
                            <td>+1  43554 54985</td>
                            <td>richard@gmail.com</td>
                            <td><span class="badge badge-soft-danger border border-danger">Unavailable</span></td>
                            <td>
                                <div class="action-item">
                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </a>
                                    <ul class="dropdown-menu p-2">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#view_staff">View Details</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_staff">Edit</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_staff">Delete</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar me-2" data-bs-toggle="modal" data-bs-target="#view_staff">
                                        <img src="{{URL::asset('build/img/users/user-05.jpg')}}" alt="Doctor" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="mb-1 fs-14 fw-semibold"><a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#view_staff">Robert Reid</a></h6>
                                    </div>
                                </div>
                            </td>
                            <td>Billing Executive</td>
                            <td>Admin</td>
                            <td>+1 47554 54257</td>
                            <td>robert@gmail.com</td>
                            <td><span class="badge badge-soft-success border border-success">Available</span></td>
                            <td>
                                <div class="action-item">
                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </a>
                                    <ul class="dropdown-menu p-2">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#view_staff">View Details</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_staff">Edit</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_staff">Delete</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar me-2" data-bs-toggle="modal" data-bs-target="#view_staff">
                                        <img src="{{URL::asset('build/img/users/user-06.jpg')}}" alt="Doctor" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="mb-1 fs-14 fw-semibold"><a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#view_staff">Dottie Jeny</a></h6>
                                    </div>
                                </div>
                            </td>
                            <td>Nurse</td>
                            <td>Nurse</td>
                            <td>+1 54114 57526</td>
                            <td>dottie@gmail.com</td>
                            <td><span class="badge badge-soft-success border border-success">Available</span></td>
                            <td>
                                <div class="action-item">
                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </a>
                                    <ul class="dropdown-menu p-2">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#view_staff">View Details</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_staff">Edit</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_staff">Delete</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar me-2" data-bs-toggle="modal" data-bs-target="#view_staff">
                                        <img src="{{URL::asset('build/img/users/user-07.jpg')}}" alt="Doctor" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="mb-1 fs-14 fw-semibold"><a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#view_staff">Cheryl Bilodeau</a></h6>
                                    </div>
                                </div>
                            </td>
                            <td>Staff Nurse</td>
                            <td>Nurse (RN)</td>
                            <td>+1 51247 56574</td>
                            <td>cheryl@gmail.com</td>
                            <td><span class="badge badge-soft-success border border-success">Available</span></td>
                            <td>
                                <div class="action-item">
                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </a>
                                    <ul class="dropdown-menu p-2">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#view_staff">View Details</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_staff">Edit</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_staff">Delete</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar me-2" data-bs-toggle="modal" data-bs-target="#view_staff">
                                        <img src="{{URL::asset('build/img/users/user-02.jpg')}}" alt="Doctor" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="mb-1 fs-14 fw-semibold"><a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#view_staff">Valerie Padgett</a></h6>
                                    </div>
                                </div>
                            </td>
                            <td>HR Executive</td>
                            <td>Nurse Practitioner</td>
                            <td>+1 41452 25741</td>
                            <td>valerie@gmail.com</td>
                            <td><span class="badge badge-soft-success border border-success">Available</span></td>
                            <td>
                                <div class="action-item">
                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </a>
                                    <ul class="dropdown-menu p-2">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#view_staff">View Details</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_staff">Edit</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_staff">Delete</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar me-2" data-bs-toggle="modal" data-bs-target="#view_staff">
                                        <img src="{{URL::asset('build/img/users/user-25.jpg')}}" alt="Doctor" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="mb-1 fs-14 fw-semibold"><a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#view_staff">Diane Nash</a></h6>
                                    </div>
                                </div>
                            </td>
                            <td>Nurse Specialist</td>
                            <td>Nurses</td>
                            <td>+1 51425 21498</td>
                            <td>diane@gmail.com</td>
                            <td><span class="badge badge-soft-success border border-success">Available</span></td>
                            <td>
                                <div class="action-item">
                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </a>
                                    <ul class="dropdown-menu p-2">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#view_staff">View Details</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_staff">Edit</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_staff">Delete</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar me-2" data-bs-toggle="modal" data-bs-target="#view_staff">
                                        <img src="{{URL::asset('build/img/users/user-29.jpg')}}" alt="Doctor" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="mb-1 fs-14 fw-semibold"><a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#view_staff">Sally Cavazos</a></h6>
                                    </div>
                                </div>
                            </td>
                            <td>Nurse Specialist</td>
                            <td>Nurses</td>
                            <td>+1 45214 98741</td>
                            <td>sally@gmail.com</td>
                            <td><span class="badge badge-soft-success border border-success">Available</span></td>
                            <td>
                                <div class="action-item">
                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </a>
                                    <ul class="dropdown-menu p-2">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#view_staff">View Details</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_staff">Edit</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_staff">Delete</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar me-2" data-bs-toggle="modal" data-bs-target="#view_staff">
                                        <img src="{{URL::asset('build/img/users/user-12.jpg')}}" alt="Doctor" class="rounded-circle">
                                    </a>
                                    <div>
                                        <h6 class="mb-1 fs-14 fw-semibold"><a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#view_staff">Forest Heath</a></h6>
                                    </div>
                                </div>
                            </td>
                            <td>Call Center</td>
                            <td>Reception</td>
                            <td>+1 41245 32540</td>
                            <td>forest@gmail.com</td>
                            <td><span class="badge badge-soft-success border border-success">Available</span></td>
                            <td>
                                <div class="action-item">
                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </a>
                                    <ul class="dropdown-menu p-2">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#view_staff">View Details</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_staff">Edit</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_staff">Delete</a>
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