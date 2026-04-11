<?php $page = 'language-settings3'; ?>
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
                                    <h5 class="fw-bold">Language</h5>
                                <div class="d-flex align-items-center">
                                    <div class="dropdown me-2">
                                        <a href="javascript:void(0);" class="dropdown-toggle btn btn-outline-white d-inline-flex align-items-center" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                                            <i class="isax isax-language-square me-1"></i>Language
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-lg p-2">
                                            <li>
                                                <label class="dropdown-item d-flex align-items-center rounded-1">
                                                    English
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item d-flex align-items-center rounded-1">
                                                    German
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item d-flex align-items-center rounded-1">
                                                    Arabic
                                                </label>
                                            </li>
                                            <li>
                                                <label class="dropdown-item d-flex align-items-center rounded-1">
                                                    French
                                                </label>
                                            </li>
                                        </ul>
                                    </div>
                                    <a href="javascript:void(0);" class="btn btn-primary d-inline-flex align-items-center"><i class="ti ti-plus me-1"></i>New Language</a>
                                </div>
                                </div>
                            </div>
                            <div class="card-body px-0 mx-3">

                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                                    <div class="top-search me-2">
                                        <div class="input-icon-start position-relative me-2">
                                            <input type="text" class="form-control form-control-sm ps-1 bg-white" placeholder="Search">                             
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <a href="javascript:void(0);" class="btn btn-outline-white d-inline-flex align-items-center me-2 fw-normal"><i class="ti ti-arrow-left me-1"></i>Back to Translations</a>
                                        <a href="javascript:void(0);" class="btn btn-sm btn-outline-white me-2 fw-normal d-inline-flex align-items-center">
                                            <img src="{{URL::asset('build/img/flags/ae.svg')}}" alt="img" class="avatar avatar-xs rounded-circle me-1"> Arabic
                                        </a>
                                        <div class="progress-percent">
                                            <span class="text-gray-9 fs-10">Progress</span>
                                            <div class="d-flex align-items-center">
                                                <div class="progress progress-xs" style="width: 120px;">
                                                    <div class="progress-bar bg-danger rounded" role="progressbar" style="width: 70%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <span class="d-inline-flex fs-12 ms-2">70%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Start Table -->
                                <div class="custom-datatable-filter table-responsive border rounded mb-3">
                                    <table class="table mb-0">
                                        <thead>
                                            <tr>
                                                <th>English</th>
                                                <th>Arabic</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td class="text-gray-9">
                                                    Invoices
                                                </td>
                                                <td>
                                                    <input type="text" dir="rtl" class="form-control text-end" value="الفواتير">
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-gray-9">
                                                    Recurring Invoices
                                                </td>
                                                <td>
                                                    <input type="text" dir="rtl" class="form-control text-end" value="الفواتير المتكررة">
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-gray-9">
                                                    Credit Notes
                                                </td>
                                                <td>
                                                    <input type="text" dir="rtl" class="form-control text-end" value="ملاحظات الائتمان">
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-gray-9">
                                                    Quotations
                                                </td>
                                                <td>
                                                    <input type="text" dir="rtl" class="form-control text-end" value="الاقتباسات">
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-gray-9">
                                                    Delivery Challans
                                                </td>
                                                <td>
                                                    <input type="text" dir="rtl" class="form-control text-end" value="تسليم تشالان">
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-gray-9">
                                                    Customers
                                                </td>
                                                <td>
                                                    <input type="text" dir="rtl" class="form-control text-end" value="عملاء">
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <!-- End Table -->

                            </div>
                        </div>
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