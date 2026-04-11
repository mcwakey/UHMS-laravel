<?php $page = 'system-backup-settings'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- ========================
        Start Page Content
    ========================= -->
        
    <div class="page-wrapper">

        <!-- Start Content -->
        <div class="content" id="profilePage">

            <!-- Page Header -->
            <div class="mb-3 border-bottom pb-3">
                <h4 class="fw-bold mb-0">Settings</h4>
            </div>
            <!-- End Page Header -->

            <div class="card">
                <div class="card-body p-0">
                    <div class="settings-wrapper d-flex">

                        @component('components.settings-sidebar')
                        @endcomponent

                        <div class="card flex-fill mb-0 border-0 bg-light-500 shadow-none">
                            <div class="card-header border-bottom px-0 mx-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <h5 class="fw-bold">System Backup</h5>
                                    <a href="javascript:void(0);" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#generate"><i class="ti ti-plus me-1"></i>Generate Backup</a>
                                </div>
                            </div>
                            <div class="card-body px-0 mx-3">
                                <!-- Table List -->
                                <div class="table-responsive border">
                                    <table class="table table-nowrap">
                                        <thead class="tablehead-light">
                                            <tr>
                                                <th>Name</th>
                                                <th>Created On</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>patients_data_backup_2025.txt</td>
                                                <td>30 Apr 2025</td>
                                                <td class="action-item">
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </a>
                                                    <ul class="dropdown-menu p-2">
                                                        <li>
                                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_ip_address">Edit</a>
                                                        </li>
                                                        <li>
                                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                                        </li>
                                                    </ul>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>invoice_records_backup_2024.txt</td>
                                                <td>15 Apr 2025</td>
                                                <td class="action-item">
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </a>
                                                    <ul class="dropdown-menu p-2">
                                                        <li>
                                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_ip_address">Edit</a>
                                                        </li>
                                                        <li>
                                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                                        </li>
                                                    </ul>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>lab_transactions_2024.txt</td>
                                                <td>02 Apr 2025</td>
                                                <td class="action-item">
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </a>
                                                    <ul class="dropdown-menu p-2">
                                                        <li>
                                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_ip_address">Edit</a>
                                                        </li>
                                                        <li>
                                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                                        </li>
                                                    </ul>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>payment_transactions_2024.txt</td>
                                                <td>27 Mar 2025</td>
                                                <td class="action-item">
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </a>
                                                    <ul class="dropdown-menu p-2">
                                                        <li>
                                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_ip_address">Edit</a>
                                                        </li>
                                                        <li>
                                                            <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_modal">Delete</a>
                                                        </li>
                                                    </ul>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <!-- /Table List -->

                            </div><!-- end card body -->
                        </div><!-- end card -->

                    </div>

                </div><!-- end card body -->
            </div><!-- end card -->
                            
        </div>
        <!-- End Content -->

        @component('components.footer')
        @endcomponent

    </div>

    <!-- ========================
        End Page Content
    ========================= -->

@endsection    