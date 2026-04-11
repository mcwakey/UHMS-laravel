<?php $page = 'announcements'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- ========================
        Start Page Content
    ========================= -->
        
    <div class="page-wrapper">

        <!-- Start Content -->
        <div class="content">

            <!-- Page Header -->
            <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
                <div class="d-flex align-items-center">
                    <h4 class="fw-bold mb-0 me-2">Announcements</h4>
                    <span class="badge badge-soft-primary border pt-1 px-2 border-primary fw-medium">Total City : 365</span>
                </div>
                <div class="text-end">
                    <a href="javascript:void(0);" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add_announcement"><i class="ti ti-plus me-1"></i>Add New Announcement</a>
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

            <!-- Table List -->
            <div class="table-responsive">
                <table class="table table-nowrap datatable">
                    <thead>
                        <tr>
                            <th>Created On</th>
                            <th>Announcement</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>30 Apr 2025</td>
                            <td>
                                <h6 class="fs-14 fw-semibold">System Maintenance Scheduled</h6>
                                <p>Platform will be under maintenance on 07 Apr from 1 AM to 3 AM.</p>
                            </td>
                            <td>System Update</td>
                            <td><span class="badge bg-soft-success fs-13 fw-medium text-success border border-success py-1 px-2">Active</span></td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_announcement">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_announcement">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>15 Apr 2025</td>
                            <td>
                                <h6 class="fs-14 fw-semibold">New Lab Test Packages Added</h6>
                                <p>New health checkup and diagnostic packages now available for booking.</p>
                            </td>
                            <td>Service Update</td>
                            <td><span class="badge bg-soft-success fs-13 fw-medium text-success border border-success py-1 px-2">Active</span></td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_announcement">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_announcement">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>02 Apr 2025</td>
                            <td>
                                <h6 class="fs-14 fw-semibold">Staff Training Program</h6>
                                <p>Mandatory EMR training for all staff on 02 Apr at 10 AM in Meeting Room 1.</p>
                            </td>
                            <td>Service Update</td>
                            <td><span class="badge bg-soft-success fs-13 fw-medium text-success border border-success py-1 px-2">Active</span></td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_announcement">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_announcement">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>27 Mar 2025</td>
                            <td>
                                <h6 class="fs-14 fw-semibold">Patient Feedback System Launched</h6>
                                <p>Mandatory EMR training for all staff on 02 Apr at 10 AM in Meeting Room 1.</p>
                            </td>
                            <td>Internal Notice</td>
                            <td><span class="badge bg-soft-danger fs-13 fw-medium text-danger border border-danger py-1 px-2">Inactive</span></td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_announcement">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_announcement">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>12 Mar 2025</td>
                            <td>
                                <h6 class="fs-14 fw-semibold">Pharmacy Stock Audit Notice</h6>
                                <p>A new digital form is now available for feedback after appointments.</p>
                            </td>
                            <td>Staff Update</td>
                            <td><span class="badge bg-soft-success fs-13 fw-medium text-success border border-success py-1 px-2">Active</span></td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_announcement">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_announcement">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>05 Mar 2025</td>
                            <td>
                                <h6 class="fs-14 fw-semibold">Lab Closed for Renovation</h6>
                                <p>A new digital form is now available for feedback after appointments.</p>
                            </td>
                            <td>Service Update</td>
                            <td><span class="badge bg-soft-success fs-13 fw-medium text-success border border-success py-1 px-2">Active</span></td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_announcement">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_announcement">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>24 Feb 2025</td>
                            <td>
                                <h6 class="fs-14 fw-semibold">Referral Program Live</h6>
                                <p>Lab services will pause 20–22 Mar for upgrades. Alternate slots available.</p>
                            </td>
                            <td>Pharmacy Notice</td>
                            <td><span class="badge bg-soft-danger fs-13 fw-medium text-danger border border-danger py-1 px-2">Inactive</span></td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_announcement">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_announcement">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>16 Feb 2025</td>
                            <td>
                                <h6 class="fs-14 fw-semibold">Lab Closed for Renovation</h6>
                                <p>Refer friends and earn discounts on health checkups.</p>
                            </td>
                            <td>Service Alert</td>
                            <td><span class="badge bg-soft-success fs-13 fw-medium text-success border border-success py-1 px-2">Active</span></td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_announcement">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_announcement">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>01 Feb 2025</td>
                            <td>
                                <h6 class="fs-14 fw-semibold">Referral Program Live</h6>
                                <p>Lab services will pause 20–22 Mar for upgrades. Alternate slots available.</p>
                            </td>
                            <td>Promotion</td>
                            <td><span class="badge bg-soft-danger fs-13 fw-medium text-danger border border-danger py-1 px-2">Inactive</span></td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_announcement">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_announcement">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>25 Jan 2025</td>
                            <td>
                                <h6 class="fs-14 fw-semibold">Monthly Review Meeting</h6>
                                <p>Refer friends and earn discounts on health checkups.</p>
                            </td>
                            <td>Admin Meeting</td>
                            <td><span class="badge bg-soft-danger fs-13 fw-medium text-danger border border-danger py-1 px-2">Inactive</span></td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_announcement">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_announcement">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- /Table List -->
                            
        </div>
        <!-- End Content -->

        @component('components.footer')
        @endcomponent

    </div>

    <!-- ========================
        End Page Content
    ========================= -->

@endsection