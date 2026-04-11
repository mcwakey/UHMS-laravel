<?php $page = 'leave-type'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- =====================`===
        Start Page Content
    ========================= -->
        
    <div class="page-wrapper">

        <!-- Start Content -->
        <div class="content" id="profilePage">

            <!-- Page Header -->
            <div class="mb-3 border-bottom pb-3">
                <div class="d-flex align-items-center justify-content-between">
                    <h4 class="fw-bold mb-0">Leave Type</h4>
                    <a href="javascript:void(0);" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add_leave_type"><i class="ti ti-plus me-1"></i>New Leave Type</a>
                </div>
            </div>
            <!-- End Page Header -->

            <!-- Table List -->
            <div class="table-responsive border">
                <table class="table table-nowrap">
                    <thead class="tablehead-light">
                        <tr>
                            <th>Leave Type</th>
                            <th>Leave Quota</th>
                            <th>Created On</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Sick Leave</td>
                            <td>02</td>
                            <td>30 Apr 2025</td>
                            <td><span class="badge bg-soft-success fs-13 fw-medium text-success border border-success py-1 px-2">Active</span></td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_leave_type">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_leave_type">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>Casual Leave</td>
                            <td>01</td>
                            <td>15 Apr 2025</td>
                            <td><span class="badge bg-soft-danger fs-13 fw-medium text-danger border border-danger py-1 px-2">Inactive</span></td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_leave_type">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_leave_type">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>Emergency</td>
                            <td>05</td>
                            <td>02 Apr 2025</td>
                            <td><span class="badge bg-soft-success fs-13 fw-medium text-success border border-success py-1 px-2">Active</span></td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_leave_type">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_leave_type">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>Vacation</td>
                            <td>05</td>
                            <td>12 Mar 2025</td>
                            <td><span class="badge bg-soft-success fs-13 fw-medium text-success border border-success py-1 px-2">Active</span></td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_leave_type">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_leave_type">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>Maternity</td>
                            <td>05</td>
                            <td>27 Mar 2025</td>
                            <td><span class="badge bg-soft-success fs-13 fw-medium text-success border border-success py-1 px-2">Active</span></td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_leave_type">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_leave_type">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>Paternity</td>
                            <td>05</td>
                            <td>05 Mar 2025</td>
                            <td><span class="badge bg-soft-success fs-13 fw-medium text-success border border-success py-1 px-2">Active</span></td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_leave_type">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_leave_type">Delete</a>
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