<?php $page = 'blog-categories'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- ========================
        Start Page Content
    ========================= -->

    <div class="page-wrapper">

        <!-- Start Content -->
        <div class="content">

            <!-- Page Header -->
            <div class="d-flex align-items-center pb-3 mb-3 border-bottom">
                <div class="flex-grow-1">
                    <h4 class="fw-bold mb-0">Categories</h4>
                </div>
                <div class="text-end">
                    <a href="javascript:void(0);" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add_categories"><i class="ti ti-plus me-1"></i>Add New Categories</a>
                </div>
            </div>
            <!-- End Page Header -->

            <!-- Table List -->
            <div class="table-responsive border">
                <table class="table table-nowrap">
                    <thead class="tablehead-light">
                        <tr>
                            <th>Categories</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>General Medicine</td>
                            <td>30 Apr 2025</td>
                            <td><span class="badge bg-soft-success fs-13 fw-medium text-success border border-success py-1 px-2">Active</span></td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_categories">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_categories">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>Disease & Conditions</td>
                            <td>15 Apr 2025</td>
                            <td><span class="badge bg-soft-success fs-13 fw-medium text-success border border-success py-1 px-2">Active</span></td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_categories">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_categories">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>Mental Health</td>
                            <td>02 Apr 2025</td>
                            <td><span class="badge bg-soft-success fs-13 fw-medium text-success border border-success py-1 px-2">Active</span></td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_categories">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_categories">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>Patient Education </td>
                            <td>27 Mar 2025</td>
                            <td><span class="badge bg-soft-success fs-13 fw-medium text-success border border-success py-1 px-2">Active</span></td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_categories">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_categories">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>Nutrition & Lifestyle Medicine</td>
                            <td>12 Mar 2025</td>
                            <td><span class="badge bg-soft-success fs-13 fw-medium text-success border border-success py-1 px-2">Active</span></td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_categories">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_categories">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>Women’s & Men’s Health</td>
                            <td>05 Mar 2025</td>
                            <td><span class="badge bg-soft-success fs-13 fw-medium text-success border border-success py-1 px-2">Active</span></td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_categories">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_categories">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>Preventive Care</td>
                            <td>24 Feb 2025</td>
                            <td><span class="badge bg-soft-success fs-13 fw-medium text-success border border-success py-1 px-2">Active</span></td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_categories">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_categories">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>Medical Technology & Innovations</td>
                            <td>16 Feb 2025</td>
                            <td><span class="badge bg-soft-success fs-13 fw-medium text-success border border-success py-1 px-2">Active</span></td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_categories">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_categories">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>Healthcare Policy & News</td>
                            <td>01 Feb 2025</td>
                            <td><span class="badge bg-soft-danger fs-13 fw-medium text-danger border border-danger py-1 px-2">Inactive</span></td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_categories">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_categories">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>Pediatrics & Geriatrics</td>
                            <td>25 Jan 2025</td>
                            <td><span class="badge bg-soft-danger fs-13 fw-medium text-danger border border-danger py-1 px-2">Inactive</span></td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_categories">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_categories">Delete</a>
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