<?php $page = 'language-settings'; ?>
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
                                    <a href="javascript:void(0);" class="btn btn-primary d-inline-flex align-items-center"><i class="ti ti-plus me-1"></i>Add New Language</a>
                                </div>
                                </div>
                            </div>
                            <div class="card-body px-0 mx-3">

                                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                                    <div class="d-flex my-xl-auto right-content align-items-center flex-wrap row-gap-3">
                                        <div class="input-icon-start position-relative me-2">
                                            <input type="text" class="form-control form-control-sm ps-2 bg-white" placeholder="Search">                                      
                                        </div>
                                    </div>
                                    <a href="javascript:void(0);" class="btn btn-outline-white d-inline-flex align-items-center"><i class="ti ti-download me-1"></i>Import Sample</a>
                                </div>

                                <!-- Start Table -->
                                <div class="custom-datatable-filter table-nowrap table-responsive border rounded mb-3">
                                    <table class="table mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Language</th>
                                                <th>Code</th>
                                                <th>RTL</th>
                                                <th>Default</th>
                                                <th>Status</th>
                                                <th></th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <a href="{{url('language-settings2')}}" class=" me-2 flex-shrink-0"><img src="{{URL::asset('build/img/flags/us.svg')}}" alt="img" class="avatar avatar-xs rounded-circle"></a>
                                                        <a href="{{url('language-settings2')}}">English</a>
                                                    </div>
                                                </td>
                                                <td>
                                                    en
                                                </td>
                                                <td>
                                                    <div class="form-check form-check-sm form-switch">
                                                        <input class="form-check-input form-label" type="checkbox" role="switch" checked>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="form-check form-check-sm form-switch">
                                                        <a href="javascript:void(0);" class="btn btn-light p-1 rounded-circle toggle-star"><i class="ti ti-star"></i></a>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="form-check form-check-sm form-switch">
                                                        <span class="badge badge-soft-success text-success fw-medium fs-13"><i class="ti ti-point-filled"></i> Active</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <a href="{{url('language-settings2')}}" class="btn btn-sm btn-outline-white me-2">Web</a>
                                                        <a href="{{url('language-settings2')}}" class="btn btn-sm btn-outline-white me-2">App</a>
                                                        <a href="{{url('language-settings2')}}" class="btn btn-sm btn-outline-white">Admin</a>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="dropdown">
                                                        <a href="javascript:void(0);" class="btn btn-outline-white d-inline-flex rounded p-1 align-items-center justify-content-center btn-sm" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="ti ti-dots-vertical"></i>
                                                        </a>
                                                        <ul class="dropdown-menu dropdown-menu-end p-2">
                                                            <li>
                                                                <a class="dropdown-item rounded-1" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#edit_testimonial"><i class="isax isax-edit me-2"></i>Edit</a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item rounded-1" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="isax isax-trash me-2"></i>Delete</a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <a href="{{url('language-settings2')}}" class="flex-shrink-0 me-2"><img src="{{URL::asset('build/img/flags/de.svg')}}" alt="img" class="avatar avatar-xs rounded-circle"></a>
                                                        <a href="{{url('language-settings2')}}">German</a>
                                                    </div>
                                                </td>
                                                <td>
                                                    de
                                                </td>
                                                <td>
                                                    <div class="form-check form-check-sm form-switch">
                                                        <input class="form-check-input form-label" type="checkbox" role="switch" checked>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="form-check form-check-sm form-switch">
                                                        <a href="javascript:void(0);" class="btn btn-light p-1 rounded-circle toggle-star"><i class="ti ti-star"></i></a>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="form-check form-check-sm form-switch">
                                                        <span class="badge badge-soft-success text-success fw-medium fs-13"><i class="ti ti-point-filled"></i> Active</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <a href="{{url('language-settings2')}}" class="btn btn-sm btn-outline-white me-2">Web</a>
                                                        <a href="{{url('language-settings2')}}" class="btn btn-sm btn-outline-white me-2">App</a>
                                                        <a href="{{url('language-settings2')}}" class="btn btn-sm btn-outline-white">Admin</a>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="dropdown">
                                                        <a href="javascript:void(0);" class="btn btn-outline-white d-inline-flex rounded p-1 align-items-center justify-content-center btn-sm" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="ti ti-dots-vertical"></i>
                                                        </a>
                                                        <ul class="dropdown-menu dropdown-menu-end p-2">
                                                            <li>
                                                                <a class="dropdown-item rounded-1" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#edit_testimonial"><i class="isax isax-edit me-2"></i>Edit</a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item rounded-1" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="isax isax-trash me-2"></i>Delete</a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <a href="{{url('language-settings2')}}" class="flex-shrink-0 me-2"><img src="{{URL::asset('build/img/flags/ae.svg')}}" alt="img" class="avatar avatar-xs rounded-circle"></a>
                                                        <a href="{{url('language-settings2')}}">Arabic</a>
                                                    </div>
                                                </td>
                                                <td>
                                                    ar
                                                </td>
                                                <td>
                                                    <div class="form-check form-check-sm form-switch">
                                                        <input class="form-check-input form-label" type="checkbox" role="switch" checked>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="form-check form-check-sm form-switch">
                                                        <a href="javascript:void(0);" class="btn btn-light p-1 rounded-circle toggle-star"><i class="ti ti-star"></i></a>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="form-check form-check-sm form-switch">
                                                        <span class="badge badge-soft-success text-success fw-medium fs-13"><i class="ti ti-point-filled"></i> Active</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <a href="{{url('language-settings2')}}" class="btn btn-sm btn-outline-white me-2">Web</a>
                                                        <a href="{{url('language-settings2')}}" class="btn btn-sm btn-outline-white me-2">App</a>
                                                        <a href="{{url('language-settings2')}}" class="btn btn-sm btn-outline-white">Admin</a>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="dropdown">
                                                        <a href="javascript:void(0);" class="btn btn-outline-white d-inline-flex rounded p-1 align-items-center justify-content-center btn-sm" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="ti ti-dots-vertical"></i>
                                                        </a>
                                                        <ul class="dropdown-menu dropdown-menu-end p-2">
                                                            <li>
                                                                <a class="dropdown-item rounded-1" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#edit_testimonial"><i class="isax isax-edit me-2"></i>Edit</a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item rounded-1" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="isax isax-trash me-2"></i>Delete</a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <a href="{{url('language-settings2')}}" class="flex-shrink-0 me-2"><img src="{{URL::asset('build/img/flags/fr.svg')}}" alt="img" class="avatar avatar-xs rounded-circle"></a>
                                                        <a href="{{url('language-settings2')}}">French</a>
                                                    </div>
                                                </td>
                                                <td>
                                                    fr
                                                </td>
                                                <td>
                                                    <div class="form-check form-check-sm form-switch">
                                                        <input class="form-check-input form-label" type="checkbox" role="switch" checked>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="form-check form-check-sm form-switch">
                                                        <a href="javascript:void(0);" class="btn btn-light p-1 rounded-circle toggle-star"><i class="ti ti-star"></i></a>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="form-check form-check-sm form-switch">
                                                        <span class="badge badge-soft-danger text-danger fw-medium fs-13"><i class="ti ti-point-filled"></i> Inactive</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <a href="{{url('language-settings2')}}" class="btn btn-sm btn-outline-white me-2">Web</a>
                                                        <a href="{{url('language-settings2')}}" class="btn btn-sm btn-outline-white me-2">App</a>
                                                        <a href="{{url('language-settings2')}}" class="btn btn-sm btn-outline-white">Admin</a>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="dropdown">
                                                        <a href="javascript:void(0);" class="btn btn-outline-white d-inline-flex rounded p-1 align-items-center justify-content-center btn-sm" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="ti ti-dots-vertical"></i>
                                                        </a>
                                                        <ul class="dropdown-menu dropdown-menu-end p-2">
                                                            <li>
                                                                <a class="dropdown-item rounded-1" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#edit_testimonial"><i class="isax isax-edit me-2"></i>Edit</a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item rounded-1" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="isax isax-trash me-2"></i>Delete</a>
                                                            </li>
                                                        </ul>
                                                    </div>
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