<?php $page = 'todo'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- ========================
        Start Page Content
    ========================= -->

    <div class="page-wrapper">
        
        <!-- Start Content -->
        <div class="content">                

            <!-- Page Header -->
            <div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3">
                <div class="flex-grow-1">
                    <h4 class="fs-18 fw-semibold mb-0">Todo</h4>
                </div>
                <div class="text-end">
                    <ol class="breadcrumb m-0 py-0">
                        <li class="breadcrumb-item"><a href="{{url('index')}}">Home</a></li>                              
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Applications</a></li>                              
                        <li class="breadcrumb-item active" aria-current="page">Todo</li>
                    </ol>
                </div>
            </div>
            <!-- End Page Header -->
            
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <a href="#" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#add_todo"><i class="ti ti-circle-plus me-1"></i>Create New</a>
                <ul class="d-flex align-items-center flex-shrink-0 list-unstyled mb-0">
                    <li>
                        <a href="{{url('todo')}}" class="btn btn-icon btn-sm bg-primary text-white active me-2"><i class="ti ti-layout-grid"></i></a>
                    </li>
                    <li>
                        <a href="{{url('todo-list')}}" class="btn btn-icon btn-sm bg-white text-dark me-2"><i class="ti ti-list-tree"></i></a>
                    </li>
                </ul>
            </div>

            <div class="card shadow-none mb-0">
                <div class="card-body">

                    <!-- start row -->
                    <div class="row gy-3 mb-3">

                        <div class="col-sm-4">
                            <div class="d-flex align-items-center">
                                <h6 class="fs-16 mb-0">Total Todo</h6>
                                <span class="badge badge-dark rounded-pill badge-xs ms-2">+1</span>
                            </div>
                        </div> <!-- end col -->

                        <div class="col-sm-8">
                            <div class="d-flex align-items-center justify-content-end">
                                <p class="mb-0 me-2 pe-2 border-end fs-14">Total Task : <span class="text-dark"> 55 </span></p>
                                <p class="mb-0 me-2 pe-2 border-end fs-14">Pending : <span class="text-dark"> 15 </span></p>
                                <p class="mb-0 fs-14">Completed : <span class="text-dark"> 40 </span></p>
                            </div>
                        </div> <!-- end col -->

                    </div>
                    <!-- end row -->

                    <div class="border-bottom mb-3">

                        <!-- start row -->
                        <div class="row">

                            <div class="col-lg-12">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                                    <div class="input-group input-group-flat w-auto">
                                        <span class="input-group-text">
                                            <i class="ti ti-calendar"></i>
                                        </span>
                                        <input type="text" class="form-control form-control-sm" placeholder="Due Date"  data-provider="flatpickr" data-date-format="d-m-Y">                                            
                                    </div>

                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <div class="dropdown">
                                            <a href="javascript:void(0);" class="dropdown-toggle btn btn-sm fs-14 border bg-white rounded text-dark d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                                All Tags
                                            </a>
                                            <ul class="dropdown-menu  dropdown-menu-end">
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1">All Tags</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1">Internal</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1">Projects</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1">Meetings</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1">Reminder</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1">Research</a>
                                                </li>
                                            </ul>
                                        </div>
                                        <div class="dropdown">
                                            <a href="javascript:void(0);" class="dropdown-toggle btn btn-sm fs-14 border bg-white rounded text-dark d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                            <span  class="text-body me-1"> Sort By : </span>  Recent
                                            </a>
                                            <ul class="dropdown-menu  dropdown-menu-end">
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1">Recently Added</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1">Ascending</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1">Desending</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1">Last Month</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1">Last 7 Days</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div> <!-- end col -->

                        </div>
                        <!-- end row -->

                    </div>
                    
                    <div class="accordion  accordion-arrow-none" id="accordionExample">

                        <!-- Accordion Start -->
                        <div class="accordion-item mb-3 border-0 border-bottom pb-2">

                            <!-- start row -->
                            <div class="row align-items-center mb-2 row-gap-3">

                                <div class="col-lg-4 col-sm-6">
                                    <div class="accordion-header cursor-pointer" id="headingTwo">
                                        <div class="accordion-button bg-transparent shadow-none p-0" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-controls="collapseTwo">
                                            <div class="d-flex align-items-center w-100">
                                                <div class="me-2">
                                                    <a href="javascript:void(0);">
                                                        <span><i class="ti ti-chevron-down"></i></span>
                                                    </a>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <span><i class="ti ti-square-rounded text-purple me-2"></i></span>
                                                    <h5 class="fw-semibold mb-0">High</h5>
                                                    <span class="badge bg-light text-dark rounded-pill ms-2">15</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div> <!-- end col -->

                                <div class="col-lg-8 col-sm-6">
                                    <div class="d-flex align-items-center justify-content-sm-end">
                                        <a href="#" class="btn btn-light btn-sm">See All<i class="ti ti-arrow-right ms-2"></i></a>
                                    </div>
                                </div> <!-- end col -->

                            </div>
                            <!-- end row -->

                            <div id="collapseTwo" class="accordion-collapse collapse show" aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
                                <div class="accordion-body p-0">
                                    <div class="list-group list-group-flush">
                                        <div class="list-group-item list-item-hover border rounded mb-2 p-3">

                                            <!-- start row -->
                                            <div class="row align-items-center row-gap-3">

                                                <div class="col-lg-6 col-md-7">
                                                    <div class="todo-inbox-check d-flex align-items-center flex-wrap row-gap-3">
                                                        <span class="me-2 d-flex align-items-center"><i class="ti ti-grid-dots text-dark"></i></span>
                                                        <div class="form-check form-check-md me-2">
                                                            <input class="form-check-input" type="checkbox">
                                                        </div>
                                                        <span class="me-2 d-flex align-items-center rating-select"><i class="ti ti-star-filled filled"></i></span>
                                                        <div class="strike-info">
                                                            <h4 class="fs-14 mb-0">Finalize project proposal</h4>
                                                        </div>
                                                        <span class="badge badge-soft-info ms-2 d-inline-flex align-items-center p-1"><i class="ti ti-calendar me-1"></i>15 Jan 2025</span>
                                                    </div>
                                                </div> <!-- end col -->

                                                <div class="col-lg-6 col-md-5">
                                                    <div class="d-flex align-items-center justify-content-md-end flex-wrap row-gap-3">
                                                        <span class="badge badge-success bg-success me-2">Projects</span>
                                                        <span class="badge badge-soft-danger d-inline-flex align-items-center me-2"><i class="fas fa-circle fs-6 me-1"></i>Onhold</span>
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar-list-stacked avatar-group-sm">
                                                                <span class="avatar avatar-rounded">
                                                                    <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" alt="img">
                                                                </span>
                                                                <span class="avatar avatar-rounded">
                                                                    <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" alt="img">
                                                                </span>
                                                                <span class="avatar avatar-rounded">
                                                                    <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-03.jpg')}}" alt="img">
                                                                </span>
                                                            </div>
                                                            <div class="dropdown ms-2">
                                                                <a href="javascript:void(0);" class="d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                                                    <i class="ti ti-dots-vertical"></i>
                                                                </a>
                                                                <ul class="dropdown-menu dropdown-menu-end">
                                                                    <li>
                                                                        <a href="javascript:void(0);" class="dropdown-item rounded-1" data-bs-toggle="modal" data-bs-target="#edit_todo"><i class="ti ti-edit me-2"></i>Edit</a>
                                                                    </li>
                                                                    <li>
                                                                        <a href="javascript:void(0);" class="dropdown-item rounded-1" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash me-2"></i>Delete</a>
                                                                    </li>
                                                                    <li>
                                                                        <a href="javascript:void(0);" class="dropdown-item rounded-1" data-bs-toggle="modal" data-bs-target="#view_todo"><i class="ti ti-eye me-2"></i>View</a>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div> <!-- end col -->

                                            </div>
                                            <!-- end row -->

                                        </div>
                                        <div class="list-group-item list-item-hover border rounded mb-2 p-3">

                                            <!-- start row -->
                                            <div class="row align-items-center row-gap-3">

                                                <div class="col-lg-6 col-md-7">
                                                    <div class="todo-inbox-check d-flex align-items-center flex-wrap row-gap-3">
                                                        <span class="me-2 d-flex align-items-center"><i class="ti ti-grid-dots text-dark"></i></span>
                                                        <div class="form-check form-check-md me-2">
                                                            <input class="form-check-input" type="checkbox">
                                                        </div>
                                                        <span class="me-2 rating-select d-flex align-items-center"><i class="ti ti-star"></i></span>
                                                        <div class="strike-info">
                                                            <h4 class="fs-14 mb-0">Submit to supervisor by EOD</h4>
                                                        </div>
                                                        <span class="badge badge-soft-info ms-2 d-inline-flex align-items-center p-1"><i class="ti ti-calendar me-1"></i>25 May 2024</span>
                                                    </div>
                                                </div> <!-- end col -->

                                                <div class="col-lg-6 col-md-5">
                                                    <div class="d-flex align-items-center justify-content-md-end flex-wrap row-gap-3">
                                                        <span class="badge bg-danger me-2">Internal</span>
                                                        <span class="badge badge-soft-secondary d-flex align-items-center me-2"><i class="fas fa-circle fs-6 me-1"></i>Inprogress</span>
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar-list-stacked avatar-group-sm">
                                                                <span class="avatar avatar-rounded">
                                                                    <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" alt="img">
                                                                </span>
                                                                <span class="avatar avatar-rounded">
                                                                    <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" alt="img">
                                                                </span>
                                                                <span class="avatar avatar-rounded">
                                                                    <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-03.jpg')}}" alt="img">
                                                                </span>
                                                            </div>
                                                            <div class="dropdown ms-2">
                                                                <a href="javascript:void(0);" class="d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                                                    <i class="ti ti-dots-vertical"></i>
                                                                </a>
                                                                <ul class="dropdown-menu dropdown-menu-end">
                                                                    <li>
                                                                        <a href="javascript:void(0);" class="dropdown-item rounded-1" data-bs-toggle="modal" data-bs-target="#edit_todo"><i class="ti ti-edit me-2"></i>Edit</a>
                                                                    </li>
                                                                    <li>
                                                                        <a href="javascript:void(0);" class="dropdown-item rounded-1" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash me-2"></i>Delete</a>
                                                                    </li>
                                                                    <li>
                                                                        <a href="javascript:void(0);" class="dropdown-item rounded-1" data-bs-toggle="modal" data-bs-target="#view_todo"><i class="ti ti-eye me-2"></i>View</a>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div> <!-- end col -->

                                            </div>
                                            <!-- end row -->

                                        </div>
                                        <div class="list-group-item list-item-hover border rounded mb-2 p-3">

                                            <!-- start row -->
                                            <div class="row align-items-center row-gap-3">

                                                <div class="col-lg-6 col-md-7">
                                                    <div class="todo-inbox-check d-flex align-items-center flex-wrap row-gap-3 todo-strike-content">
                                                        <span class="me-2 d-flex align-items-center"><i class="ti ti-grid-dots text-dark"></i></span>
                                                        <div class="form-check form-check-md me-2">
                                                            <input class="form-check-input" type="checkbox" checked>
                                                        </div>
                                                        <span class="me-2 rating-select d-flex align-items-center"><i class="ti ti-star"></i></span>
                                                        <div class="strike-info">
                                                            <h4 class="fs-14 mb-0">Prepare presentation slides</h4>
                                                        </div>
                                                        <span class="badge badge-soft-info ms-2 d-inline-flex align-items-center p-1"><i class="ti ti-calendar me-1"></i>15 Jan 2025</span>
                                                    </div>
                                                </div> <!-- end col -->

                                                <div class="col-lg-6 col-md-5">
                                                    <div class="d-flex align-items-center justify-content-md-end flex-wrap row-gap-3">
                                                        <span class="badge bg-info me-2">Reminder</span>
                                                        <span class="badge badge-soft-info d-flex align-items-center me-2"><i class="fas fa-circle fs-6 me-1"></i>Pending</span>
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar-list-stacked avatar-group-sm">
                                                                <span class="avatar avatar-rounded">
                                                                    <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" alt="img">
                                                                </span>
                                                                <span class="avatar avatar-rounded">
                                                                    <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" alt="img">
                                                                </span>
                                                                <span class="avatar avatar-rounded">
                                                                    <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-03.jpg')}}" alt="img">
                                                                </span>
                                                            </div>
                                                            <div class="dropdown ms-2">
                                                                <a href="javascript:void(0);" class="d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                                                    <i class="ti ti-dots-vertical"></i>
                                                                </a>
                                                                <ul class="dropdown-menu dropdown-menu-end">
                                                                    <li>
                                                                        <a href="javascript:void(0);" class="dropdown-item rounded-1" data-bs-toggle="modal" data-bs-target="#edit_todo"><i class="ti ti-edit me-2"></i>Edit</a>
                                                                    </li>
                                                                    <li>
                                                                        <a href="javascript:void(0);" class="dropdown-item rounded-1" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash me-2"></i>Delete</a>
                                                                    </li>
                                                                    <li>
                                                                        <a href="javascript:void(0);" class="dropdown-item rounded-1" data-bs-toggle="modal" data-bs-target="#view_todo"><i class="ti ti-eye me-2"></i>View</a>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div> <!-- end col -->

                                            </div>
                                            <!-- end row -->

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Accordion End -->

                        <!-- Accordion Start -->
                        <div class="accordion-item mb-3 border-0 border-bottom pb-2">

                            <!-- start row -->
                            <div class="row align-items-center mb-2 row-gap-3">

                                <div class="col-lg-4 col-sm-6">
                                    <div class="accordion-header cursor-pointer" id="headingThree">
                                        <div class="accordion-button bg-transparent shadow-none p-0" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-controls="collapseThree">
                                            <div class="d-flex align-items-center w-100">
                                                <div class="me-2">
                                                    <a href="javascript:void(0);">
                                                        <span><i class="ti ti-chevron-down"></i></span>
                                                    </a>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <span><i class="ti ti-square-rounded text-warning me-2"></i></span>
                                                    <h5 class="fw-semibold mb-0">Medium</h5>
                                                    <span class="badge bg-light text-dark rounded-pill ms-2">05</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div> <!-- end col -->

                                <div class="col-lg-8 col-sm-6">
                                    <div class="d-flex align-items-center justify-content-sm-end">
                                        <a href="#" class="btn btn-light btn-sm">See All<i class="ti ti-arrow-right ms-2"></i></a>
                                    </div>
                                </div> <!-- end col -->

                            </div>
                            <!-- end row -->

                            <div id="collapseThree" class="accordion-collapse collapse show" aria-labelledby="headingThree" data-bs-parent="#accordionExample">
                                <div class="accordion-body p-0">
                                    <div class="list-group list-group-flush">
                                        <div class="list-group-item list-item-hover border rounded mb-2 p-3">

                                            <!-- start row -->
                                            <div class="row align-items-center row-gap-3">

                                                <div class="col-lg-6 col-md-7">
                                                    <div class="todo-inbox-check d-flex align-items-center flex-wrap row-gap-3">
                                                        <span class="me-2 d-flex align-items-center"><i class="ti ti-grid-dots text-dark"></i></span>
                                                        <div class="form-check form-check-md me-2">
                                                            <input class="form-check-input" type="checkbox">
                                                        </div>
                                                        <span class="me-2 rating-select d-flex align-items-center"><i class="ti ti-star"></i></span>
                                                        <div class="strike-info">
                                                            <h4 class="fs-14 mb-0">Check and respond to emails</h4>
                                                        </div>
                                                        <span class="badge badge-soft-info ms-2 d-inline-flex align-items-center p-1"><i class="ti ti-calendar me-1"></i>Tomorrow</span>
                                                    </div>
                                                </div> <!-- end col -->

                                                <div class="col-lg-6 col-md-5">
                                                    <div class="d-flex align-items-center justify-content-md-end flex-wrap row-gap-3">
                                                        <span class="badge bg-info me-2">Reminder</span>
                                                        <span class="badge badge-soft-success d-inline-flex align-items-center me-2"><i class="fas fa-circle fs-6 me-1"></i>Completed</span>
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar-list-stacked avatar-group-sm">
                                                                <span class="avatar avatar-rounded">
                                                                    <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" alt="img">
                                                                </span>
                                                                <span class="avatar avatar-rounded">
                                                                    <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" alt="img">
                                                                </span>
                                                                <span class="avatar avatar-rounded">
                                                                    <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-03.jpg')}}" alt="img">
                                                                </span>
                                                            </div>
                                                            <div class="dropdown ms-2">
                                                                <a href="javascript:void(0);" class="d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                                                    <i class="ti ti-dots-vertical"></i>
                                                                </a>
                                                                <ul class="dropdown-menu dropdown-menu-end">
                                                                    <li>
                                                                        <a href="javascript:void(0);" class="dropdown-item rounded-1" data-bs-toggle="modal" data-bs-target="#edit_todo"><i class="ti ti-edit me-2"></i>Edit</a>
                                                                    </li>
                                                                    <li>
                                                                        <a href="javascript:void(0);" class="dropdown-item rounded-1" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash me-2"></i>Delete</a>
                                                                    </li>
                                                                    <li>
                                                                        <a href="javascript:void(0);" class="dropdown-item rounded-1" data-bs-toggle="modal" data-bs-target="#view_todo"><i class="ti ti-eye me-2"></i>View</a>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div> <!-- end col -->

                                            </div>
                                            <!-- end row -->

                                        </div>

                                        <div class="list-group-item list-item-hover border rounded mb-2 p-3">

                                            <!-- start row -->
                                            <div class="row align-items-center row-gap-3">

                                                <div class="col-lg-6 col-md-7">
                                                    <div class="todo-inbox-check d-flex align-items-center flex-wrap row-gap-3">
                                                        <span class="me-2 d-flex align-items-center"><i class="ti ti-grid-dots text-dark"></i></span>
                                                        <div class="form-check form-check-md me-2">
                                                            <input class="form-check-input" type="checkbox">
                                                        </div>
                                                        <span class="me-2 rating-select d-flex align-items-center"><i class="ti ti-star"></i></span>
                                                        <div class="strike-info">
                                                            <h4 class="fs-14 mb-0">Coordinate with department head on progress</h4>
                                                        </div>
                                                        <span class="badge badge-soft-info ms-2 d-inline-flex align-items-center p-1"><i class="ti ti-calendar me-1"></i>25 May 2024</span>
                                                    </div>
                                                </div> <!-- end col -->

                                                <div class="col-lg-6 col-md-5">
                                                    <div class="d-flex align-items-center justify-content-md-end flex-wrap row-gap-3">
                                                        <span class="badge bg-danger me-2">Internal</span>
                                                        <span class="badge badge-soft-secondary d-flex align-items-center me-2"><i class="fas fa-circle fs-6 me-1"></i>Inprogress</span>
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar-list-stacked avatar-group-sm">
                                                                <span class="avatar avatar-rounded">
                                                                    <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-06.jpg')}}" alt="img">
                                                                </span>
                                                                <span class="avatar avatar-rounded">
                                                                    <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-09.jpg')}}" alt="img">
                                                                </span>
                                                                <span class="avatar avatar-rounded">
                                                                    <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" alt="img">
                                                                </span>
                                                            </div>
                                                            <div class="dropdown ms-2">
                                                                <a href="javascript:void(0);" class="d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                                                    <i class="ti ti-dots-vertical"></i>
                                                                </a>
                                                                <ul class="dropdown-menu dropdown-menu-end">
                                                                    <li>
                                                                        <a href="javascript:void(0);" class="dropdown-item rounded-1" data-bs-toggle="modal" data-bs-target="#edit_todo"><i class="ti ti-edit me-2"></i>Edit</a>
                                                                    </li>
                                                                    <li>
                                                                        <a href="javascript:void(0);" class="dropdown-item rounded-1" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash me-2"></i>Delete</a>
                                                                    </li>
                                                                    <li>
                                                                        <a href="javascript:void(0);" class="dropdown-item rounded-1" data-bs-toggle="modal" data-bs-target="#view_todo"><i class="ti ti-eye me-2"></i>View</a>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div> <!-- end col -->

                                            </div>
                                            <!-- end row -->

                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Accordion End -->

                        <!-- Accordion Start -->
                        <div class="accordion-item border-0 pb-2">

                            <!-- start row -->
                            <div class="row align-items-center mb-2 row-gap-3">

                                <div class="col-lg-4 col-sm-6">
                                    <div class="accordion-header cursor-pointer" id="headingFour">
                                        <div class="accordion-button bg-transparent shadow-none p-0" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-controls="collapseFour">
                                            <div class="d-flex align-items-center w-100">
                                                <div class="me-2">
                                                    <a href="javascript:void(0);">
                                                        <span><i class="ti ti-chevron-down"></i></span>
                                                    </a>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <span><i class="ti ti-square-rounded text-success me-2"></i></span>
                                                    <h5 class="fw-semibold mb-0">Low</h5>
                                                    <span class="badge bg-light text-dark rounded-pill ms-2">24</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div> <!-- end col -->

                                <div class="col-lg-8 col-sm-6">
                                    <div class="d-flex align-items-center justify-content-sm-end">
                                        <a href="#" class="btn btn-light btn-sm">See All<i class="ti ti-arrow-right ms-2"></i></a>
                                    </div>
                                </div> <!-- end col -->
                            </div>
                            <!-- end row -->

                            <div id="collapseFour" class="accordion-collapse collapse show" aria-labelledby="headingFour" data-bs-parent="#accordionExample">
                                <div class="accordion-body p-0">
                                    <div class="list-group list-group-flush">
                                        <div class="list-group-item list-item-hover border rounded mb-2 p-3">

                                            <!-- start row -->
                                            <div class="row align-items-center row-gap-3">

                                                <div class="col-lg-6 col-md-7">
                                                    <div class="todo-inbox-check d-flex align-items-center flex-wrap row-gap-3">
                                                        <span class="me-2 d-flex align-items-center"><i class="ti ti-grid-dots text-dark"></i></span>
                                                        <div class="form-check form-check-md me-2">
                                                            <input class="form-check-input" type="checkbox">
                                                        </div>
                                                        <span class="me-2 rating-select d-flex align-items-center"><i class="ti ti-star"></i></span>
                                                        <div class="strike-info">
                                                            <h4 class="fs-14 mb-0">Plan tasks for the next day</h4>
                                                        </div>
                                                        <span class="badge badge-soft-info ms-2 d-inline-flex align-items-center p-1"><i class="ti ti-calendar me-1"></i>Today</span>
                                                    </div>
                                                </div> <!-- end col -->

                                                <div class="col-lg-6 col-md-5">
                                                    <div class="d-flex align-items-center justify-content-md-end flex-wrap row-gap-3">
                                                        <span class="badge bg-info me-2">Social</span>
                                                        <span class="badge badge-soft-info d-flex align-items-center me-2"><i class="fas fa-circle fs-6 me-1"></i>Pending</span>
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar-list-stacked avatar-group-sm">
                                                                <span class="avatar avatar-rounded">
                                                                    <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" alt="img">
                                                                </span>
                                                                <span class="avatar avatar-rounded">
                                                                    <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" alt="img">
                                                                </span>
                                                                <span class="avatar avatar-rounded">
                                                                    <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-03.jpg')}}" alt="img">
                                                                </span>
                                                            </div>
                                                            <div class="dropdown ms-2">
                                                                <a href="javascript:void(0);" class="d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                                                    <i class="ti ti-dots-vertical"></i>
                                                                </a>
                                                                <ul class="dropdown-menu dropdown-menu-end">
                                                                    <li>
                                                                        <a href="javascript:void(0);" class="dropdown-item rounded-1" data-bs-toggle="modal" data-bs-target="#edit_todo"><i class="ti ti-edit me-2"></i>Edit</a>
                                                                    </li>
                                                                    <li>
                                                                        <a href="javascript:void(0);" class="dropdown-item rounded-1" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash me-2"></i>Delete</a>
                                                                    </li>
                                                                    <li>
                                                                        <a href="javascript:void(0);" class="dropdown-item rounded-1" data-bs-toggle="modal" data-bs-target="#view_todo"><i class="ti ti-eye me-2"></i>View</a>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div> <!-- end col -->

                                            </div>
                                            <!-- end row -->

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Accordion End -->

                    </div>

                    <div class="text-center">
                        <a href="#" class="btn btn-primary btn-sm"><i class="ti ti-loader me-2"></i>Load More</a>
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