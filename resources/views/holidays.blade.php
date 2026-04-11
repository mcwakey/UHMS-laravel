<?php $page = 'holidays'; ?>
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
                    <div class="d-flex align-items-center">
                        <h4 class="fw-bold mb-0 me-2">Holidays</h4>
                        <span class="badge badge-soft-primary border border-primary fw-medium">Total Holidays : 33</span>
                    </div>
                    <a href="javascript:void(0);" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add_holiday"><i class="ti ti-plus me-1"></i>Add Holiday</a>
                </div>
            </div>
            <!-- End Page Header -->

            <!-- Table List -->
            <div class="table-responsive border">
                <table class="table table-nowrap">
                    <thead class="tablehead-light">
                        <tr>
                            <th>Name</th>
                            <th>Date</th>
                            <th>Days</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>New Year</td>
                            <td>01 Jan  2025</td>
                            <td>Monday</td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_holiday">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_holiday">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>World Braille Day</td>
                            <td>04 Jan 2025</td>
                            <td>Saturday</td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_holiday">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_holiday">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>Republic Day of India</td>
                            <td>26 Jan 2025</td>
                            <td>Sunday</td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_holiday">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_holiday">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>World Cancer Day</td>
                            <td>04 Feb 2025</td>
                            <td>Tuesday</td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_holiday">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_holiday">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>World Birth Defects Day</td>
                            <td>03 Mar 2025</td>
                            <td>Saturday</td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_holiday">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_holiday">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>International Women's Day</td>
                            <td>08 May 2025</td>
                            <td>Saturday</td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_holiday">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_holiday">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>Good Friday</td>
                            <td>29 Mar 2025</td>
                            <td>Friday</td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_holiday">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_holiday">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>Easter Monday</td>
                            <td>01 Apr 2025</td>
                            <td>Tuesday</td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_holiday">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_holiday">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>May Day</td>
                            <td>01 May 2025</td>
                            <td>Thursday</td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_holiday">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_holiday">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>Memorial Day</td>
                            <td>27 May 2025</td>
                            <td>Tuesday</td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_holiday">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_holiday">Delete</a>
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