<?php $page = 'kanban-view'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- ========================
        Start Page Content
    ========================= -->

    <div class="page-wrapper">
        
        <!-- Start Content -->
        <div class="content pb-0">
            
            <!-- Page Header -->
            <div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3">
                <div class="flex-grow-1">
                    <h4 class="fs-18 fw-semibold mb-0">Kanban View</h4>
                </div>
                <div class="text-end">
                    <ol class="breadcrumb m-0 py-0">
                        <li class="breadcrumb-item"><a href="{{url('index')}}">Home</a></li>                              
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Applications</a></li>                              
                        <li class="breadcrumb-item active" aria-current="page">Kanban View</li>
                    </ol>
                </div>
            </div>
            <!-- End Page Header -->
            
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                    <h6 class="fs-16 mb-0">Kanban View</h6>
                    <div class="d-flex align-items-center flex-wrap row-gap-3">
                        <div class="avatar-list-stacked avatar-group-sm me-2">
                            <span class="avatar avatar-rounded">
                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-19.jpg')}}" alt="img">
                            </span>
                            <span class="avatar avatar-rounded">
                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" alt="img">
                            </span>
                            <span class="avatar avatar-rounded">
                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-16.jpg')}}" alt="img">
                            </span>
                            <span class="avatar avatar-rounded bg-primary fs-12">
                                1+
                            </span>
                        </div>
                        <div class="d-flex align-items-center flex-wrap gap-2 me-3">
                            <p class="mb-0 pe-2 border-end fs-14">Total Task : <span class="text-dark"> 55 </span></p>
                            <p class="mb-0 pe-2 border-end fs-14">Pending : <span class="text-dark"> 15 </span></p>
                            <p class="mb-0 fs-14">Completed : <span class="text-dark"> 40 </span></p>
                        </div>
                        <div class="input-group input-group-flat w-auto">
                            <span class="input-group-text">
                                <i class="ti ti-search"></i>
                            </span>
                            <input type="text" class="form-control form-control-sm" placeholder="Search Project">
                        </div>
                    </div>
                </div><!-- end header end -->
                <div class="card-body overflow-hidden">
                    
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">

                        <div class="dropdown">
                            <a href="javascript:void(0);" class="dropdown-toggle btn btn-outline-light bg-white text-dark d-inline-flex align-items-center drop-arrow-none" data-bs-toggle="dropdown">
                                Select Priority<i class="ti ti-chevron-down align-middle ms-1"></i>
                            </a>
                            <ul class="dropdown-menu  dropdown-menu-start">
                                <li>
                                    <a href="javascript:void(0);" class="dropdown-item rounded-1">All</a>
                                </li>
                                <li>
                                    <a href="javascript:void(0);" class="dropdown-item rounded-1">High</a>
                                </li>
                                <li>
                                    <a href="javascript:void(0);" class="dropdown-item rounded-1">Medium</a>
                                </li>
                                <li>
                                    <a href="javascript:void(0);" class="dropdown-item rounded-1">Completed</a>
                                </li>
                            </ul>
                        </div> <!-- end col -->

                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle btn  bg-white btn-outline-light text-dark d-inline-flex align-items-center drop-arrow-none" data-bs-toggle="dropdown">
                                Clients<i class="ti ti-chevron-down align-middle ms-1"></i>
                                </a>
                                <ul class="dropdown-menu  dropdown-menu-end">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">Clients</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">Sophie</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">Cameron</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">Doris</a>
                                    </li>
                                </ul>
                            </div>
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle btn bg-white btn-outline-light text-dark d-inline-flex align-items-center drop-arrow-none" data-bs-toggle="dropdown">
                                Select Status<i class="ti ti-chevron-down align-middle ms-1"></i>
                                </a>
                                <ul class="dropdown-menu  dropdown-menu-end">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">Inprogress</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">On-hold</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">Completed</a>
                                    </li>
                                </ul>
                            </div>
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle btn bg-white btn-outline-light text-dark d-inline-flex align-items-center drop-arrow-none" data-bs-toggle="dropdown">
                                    <span  class="text-body me-1"> Sort By : </span>  Recent<i class="ti ti-chevron-down align-middle ms-1"></i>
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
                        </div> <!-- end col -->

                    </div>          

                    <div class="d-flex align-items-start overflow-auto project-status" data-plugin="dragula" data-containers='["drag-one", "drag-two", "drag-three", "drag-four"]'>

                        <div class="p-2 rounded bg-light border w-100 me-3">
                            
                            <div class="bg-white border p-3 rounded mb-2">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <span class="bg-soft-warning p-1 d-flex rounded-circle me-2"><span class="bg-warning rounded-circle d-block p-1"></span></span>
                                        <h6 class="me-2 mb-0">New</h6>
                                        <span class="badge bg-light  text-dark rounded-pill">02</span>
                                    </div>
                                    <div class="dropdown">
                                        <a href="javascript:void(0);" class="d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                            <i class="ti ti-dots-vertical"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item rounded-1" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash me-2"></i>Delete</a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="kanban-drag" id="drag-one">

                                <div>
                                    <div class="card kanban-card mb-2">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between mb-3">
                                                <div class="d-flex align-items-center">
                                                    <span class="badge bg-success badge-xs d-flex align-items-center justify-content-center"><i class="fas fa-circle fs-7 me-1"></i>Low</span>
                                                </div>
                                                <div class="dropdown">
                                                    <a href="javascript:void(0);" class="d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </a>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a href="javascript:void(0);" class="dropdown-item rounded-1" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash me-2"></i>Delete</a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center mb-3">
                                                <span class="avatar avatar-xs rounded-circle bg-warning me-2">
                                                <img src="{{URL::asset('build/img/icons/kanban-arrow.svg')}}" class="w-auto h-auto" alt="Img">
                                            </span>
                                                <h6 class="d-flex align-items-center mb-0">Doccure</h6>
                                            </div>
                                            <div class="d-flex align-items-center border-bottom mb-3 pb-3">
                                                <div class="me-2 pe-2 border-end">
                                                    <span class="fw-medium fs-12 d-block mb-1">Budget</span>
                                                    <p class="fs-12 text-dark mb-0">$24,000</p>
                                                </div>
                                                <div class="me-2 pe-2 border-end">
                                                    <span class="fw-medium fs-12 d-block mb-1">Tasks</span>
                                                    <p class="fs-12 text-dark mb-0">12/15</p>
                                                </div>
                                                <div>
                                                    <span class="fw-medium fs-12 d-block mb-1">Due on</span>
                                                    <p class="fs-12 text-dark mb-0">15 Apr</p>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="avatar-list-stacked avatar-group-sm me-3">
                                                    <span class="avatar avatar-rounded">
                                                        <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-10.jpg')}}" alt="img">
                                                    </span>
                                                    <span class="avatar avatar-rounded">
                                                        <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-08.jpg')}}" alt="img">
                                                    </span>
                                                    <span class="avatar avatar-rounded">
                                                        <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-07.jpg')}}" alt="img">
                                                    </span>
                                                    <span class="avatar avatar-rounded">
                                                        <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" alt="img">
                                                    </span>
                                                    <span class="avatar avatar-rounded">
                                                        <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-03.jpg')}}" alt="img">
                                                    </span>
                                                    <a href="#" class="avatar avatar-rounded bg-primary fs-12 text-white">1+</a>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="d-flex align-items-center me-2"><i class="ti ti-message-circle me-1"></i></a>
                                                    <a href="javascript:void(0);" class="d-flex align-items-center"><i class="ti ti-paperclip"></i></a>
                                                </div>
                                            </div>
                                        </div><!-- end card body -->
                                    </div><!-- end card -->
                                </div>

                                <div>
                                    <div class="card kanban-card mb-2">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between mb-3">
                                                <div class="d-flex align-items-center">
                                                    <span class="badge bg-danger badge-xs d-flex align-items-center justify-content-center"><i class="fas fa-circle fs-7 me-1"></i>High</span>
                                                </div>
                                                <div class="dropdown">
                                                    <a href="javascript:void(0);" class="d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </a>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a href="javascript:void(0);" class="dropdown-item rounded-1" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash me-2"></i>Delete</a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center mb-3">
                                                <span class="avatar avatar-xs rounded-circle bg-warning me-2">
                                                    <img src="{{URL::asset('build/img/icons/kanban-arrow.svg')}}" class="w-auto h-auto" alt="Img">
                                                </span>
                                                <h6 class="d-flex align-items-center mb-0">Dreams Tour</h6>
                                            </div>
                                            <div class="d-flex align-items-center border-bottom mb-3 pb-3">
                                                <div class="me-2 pe-2 border-end">
                                                    <span class="fw-medium fs-12 d-block mb-1">Budget</span>
                                                    <p class="fs-12 text-dark mb-0">$24,000</p>
                                                </div>
                                                <div class="me-2 pe-2 border-end">
                                                    <span class="fw-medium fs-12 d-block mb-1">Tasks</span>
                                                    <p class="fs-12 text-dark mb-0">12/15</p>
                                                </div>
                                                <div>
                                                    <span class="fw-medium fs-12 d-block mb-1">Due on</span>
                                                    <p class="fs-12 text-dark mb-0">15 Apr</p>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="avatar-list-stacked avatar-group-sm me-3">
                                                    <span class="avatar avatar-rounded">
                                                        <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-07.jpg')}}" alt="img">
                                                    </span>
                                                    <span class="avatar avatar-rounded">
                                                        <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-09.jpg')}}" alt="img">
                                                    </span>
                                                    <span class="avatar avatar-rounded">
                                                        <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" alt="img">
                                                    </span>
                                                    <span class="avatar avatar-rounded">
                                                        <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" alt="img">
                                                    </span>
                                                    <span class="avatar avatar-rounded">
                                                        <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-03.jpg')}}" alt="img">
                                                    </span>
                                                    <a href="#" class="avatar avatar-rounded bg-primary fs-12 text-white">1+</a>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="d-flex align-items-center me-2"><i class="ti ti-message-circle me-1"></i></a>
                                                    <a href="javascript:void(0);" class="d-flex align-items-center"><i class="ti ti-paperclip"></i></a>
                                                </div>
                                            </div>
                                        </div><!-- end card body -->
                                    </div><!-- end card -->
                                </div>

                            </div>

                            <div class="pt-2">
                                <a href="#" class="btn btn-secondary d-flex align-items-center justify-content-center" data-bs-toggle="modal" data-bs-target="#add-task">
                                    <i class="ti ti-plus me-2"></i> New Project
                                </a>
                            </div>

                        </div>

                        <div class="p-2 rounded bg-light border w-100 me-3">

                            <div class="bg-white border p-3 rounded mb-2">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <span class="bg-soft-primary p-1 d-flex rounded-circle me-2"><span class="bg-primary rounded-circle d-block p-1"></span></span>
                                        <h6 class="me-2 mb-0">Inprogress</h6>
                                        <span class="badge bg-light  text-dark rounded-pill">13</span>
                                    </div>
                                    <div class="dropdown">
                                        <a href="javascript:void(0);" class="d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                            <i class="ti ti-dots-vertical"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item rounded-1" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash me-2"></i>Delete</a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="kanban-drag" id="drag-two">

                                <div>
                                    <div class="card kanban-card mb-2">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between mb-3">
                                                <div class="d-flex align-items-center">
                                                    <span class="badge bg-danger badge-xs d-flex align-items-center justify-content-center"><i class="fas fa-circle fs-7 me-1"></i>High</span>
                                                </div>
                                                <div class="dropdown">
                                                    <a href="javascript:void(0);" class="d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </a>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a href="javascript:void(0);" class="dropdown-item rounded-1" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash me-2"></i>Delete</a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center mb-3">
                                                <span class="avatar avatar-xs rounded-circle bg-warning me-2">
                                                <img src="{{URL::asset('build/img/icons/kanban-arrow.svg')}}" class="w-auto h-auto" alt="Img">
                                            </span>
                                                <h6 class="d-flex align-items-center mb-0">Dreams Gigs</h6>
                                            </div>
                                            <div class="d-flex align-items-center border-bottom mb-3 pb-3">
                                                <div class="me-2 pe-2 border-end">
                                                    <span class="fw-medium fs-12 d-block mb-1">Budget</span>
                                                    <p class="fs-12 text-dark mb-0">$24,000</p>
                                                </div>
                                                <div class="me-2 pe-2 border-end">
                                                    <span class="fw-medium fs-12 d-block mb-1">Tasks</span>
                                                    <p class="fs-12 text-dark mb-0">12/15</p>
                                                </div>
                                                <div>
                                                    <span class="fw-medium fs-12 d-block mb-1">Due on</span>
                                                    <p class="fs-12 text-dark mb-0">15 Apr</p>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="avatar-list-stacked avatar-group-sm me-3">
                                                    <span class="avatar avatar-rounded">
                                                        <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" alt="img">
                                                    </span>
                                                    <span class="avatar avatar-rounded">
                                                    <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-04.jpg')}}" alt="img">
                                                    </span>
                                                    <span class="avatar avatar-rounded">
                                                        <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" alt="img">
                                                    </span>
                                                    <span class="avatar avatar-rounded">
                                                        <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" alt="img">
                                                    </span>
                                                    <span class="avatar avatar-rounded">
                                                        <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-03.jpg')}}" alt="img">
                                                    </span>
                                                    <a href="#" class="avatar avatar-rounded bg-primary fs-12 text-white">1+</a>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="d-flex align-items-center me-2"><i class="ti ti-message-circle me-1"></i></a>
                                                    <a href="javascript:void(0);" class="d-flex align-items-center"><i class="ti ti-paperclip"></i></a>
                                                </div>
                                            </div>
                                        </div><!-- end card body -->
                                    </div><!-- end card -->
                                </div>

                                <div>
                                    <div class="card kanban-card mb-2">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between mb-3">
                                                <div class="d-flex align-items-center">
                                                    <span class="badge bg-warning badge-xs d-flex align-items-center justify-content-center"><i class="fas fa-circle fs-7 me-1"></i>Medium</span>
                                                </div>
                                                <div class="dropdown">
                                                    <a href="javascript:void(0);" class="d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </a>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a href="javascript:void(0);" class="dropdown-item rounded-1" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash me-2"></i>Delete</a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center mb-3">
                                                <span class="avatar avatar-xs rounded-circle bg-warning me-2">
                                                    <img src="{{URL::asset('build/img/icons/kanban-arrow.svg')}}" class="w-auto h-auto" alt="Img">
                                                </span>
                                                <h6 class="d-flex align-items-center mb-0">Dreams Rent</h6>
                                            </div>
                                            <div class="d-flex align-items-center border-bottom mb-3 pb-3">
                                                <div class="me-2 pe-2 border-end">
                                                    <span class="fw-medium fs-12 d-block mb-1">Budget</span>
                                                    <p class="fs-12 text-dark mb-0">$24,000</p>
                                                </div>
                                                <div class="me-2 pe-2 border-end">
                                                    <span class="fw-medium fs-12 d-block mb-1">Tasks</span>
                                                    <p class="fs-12 text-dark mb-0">12/15</p>
                                                </div>
                                                <div>
                                                    <span class="fw-medium fs-12 d-block mb-1">Due on</span>
                                                    <p class="fs-12 text-dark mb-0">15 Apr</p>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="avatar-list-stacked avatar-group-sm me-3">
                                                    <span class="avatar avatar-rounded">
                                                        <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-10.jpg')}}" alt="img">
                                                    </span>
                                                    <span class="avatar avatar-rounded">
                                                        <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-05.jpg')}}" alt="img">
                                                    </span>
                                                    <span class="avatar avatar-rounded">
                                                        <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" alt="img">
                                                    </span>
                                                    <span class="avatar avatar-rounded">
                                                        <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" alt="img">
                                                    </span>
                                                    <span class="avatar avatar-rounded">
                                                        <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-03.jpg')}}" alt="img">
                                                    </span>
                                                    <a href="#" class="avatar avatar-rounded bg-primary fs-12 text-white">1+</a>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="d-flex align-items-center me-2"><i class="ti ti-message-circle me-1"></i></a>
                                                    <a href="javascript:void(0);" class="d-flex align-items-center"><i class="ti ti-paperclip"></i></a>
                                                </div>
                                            </div>
                                        </div><!-- end card body -->
                                    </div><!-- end card -->
                                </div>

                            </div>

                            <div class="pt-2">
                                <a href="#" class="btn btn-secondary d-flex align-items-center justify-content-center" data-bs-toggle="modal" data-bs-target="#add-task">
                                    <i class="ti ti-plus me-2"></i> New Project
                                </a>
                            </div>

                        </div>

                        <div class="p-2 rounded bg-light border w-100 me-3">

                            <div class="bg-white border p-3 rounded mb-2">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <span class="bg-soft-danger p-1 d-flex rounded-circle me-2"><span class="bg-danger rounded-circle d-block p-1"></span></span>
                                        <h6 class="me-2 mb-0">On-hold</h6>
                                        <span class="badge bg-light text-dark rounded-pill">04</span>
                                    </div>
                                    <div class="dropdown">
                                        <a href="javascript:void(0);" class="d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                            <i class="ti ti-dots-vertical"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item rounded-1" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash me-2"></i>Delete</a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="kanban-drag" id="drag-three">
                                <div>
                                    <div class="card kanban-card mb-2">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between mb-3">
                                                <div class="d-flex align-items-center">
                                                    <span class="badge bg-success badge-xs d-flex align-items-center justify-content-center"><i class="fas fa-circle fs-7 me-1"></i>Low</span>
                                                </div>
                                                <div class="dropdown">
                                                    <a href="javascript:void(0);" class="d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </a>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a href="javascript:void(0);" class="dropdown-item rounded-1" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash me-2"></i>Delete</a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center mb-3">
                                                <span class="avatar avatar-xs rounded-circle bg-warning me-2">
                                                <img src="{{URL::asset('build/img/icons/kanban-arrow.svg')}}" class="w-auto h-auto" alt="Img">
                                            </span>
                                                <h6 class="d-flex align-items-center mb-0">Dreams Sports</h6>
                                            </div>
                                            <div class="d-flex align-items-center border-bottom mb-3 pb-3">
                                                <div class="me-2 pe-2 border-end">
                                                    <span class="fw-medium fs-12 d-block mb-1">Budget</span>
                                                    <p class="fs-12 text-dark mb-0">$24,000</p>
                                                </div>
                                                <div class="me-2 pe-2 border-end">
                                                    <span class="fw-medium fs-12 d-block mb-1">Tasks</span>
                                                    <p class="fs-12 text-dark mb-0">12/15</p>
                                                </div>
                                                <div>
                                                    <span class="fw-medium fs-12 d-block mb-1">Due on</span>
                                                    <p class="fs-12 text-dark mb-0">15 Apr</p>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="avatar-list-stacked avatar-group-sm me-3">
                                                    <span class="avatar avatar-rounded">
                                                        <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-10.jpg')}}" alt="img">
                                                    </span>
                                                    <span class="avatar avatar-rounded">
                                                        <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-03.jpg')}}" alt="img">
                                                    </span>
                                                    <span class="avatar avatar-rounded">
                                                        <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" alt="img">
                                                    </span>
                                                    <span class="avatar avatar-rounded">
                                                        <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" alt="img">
                                                    </span>
                                                    <span class="avatar avatar-rounded">
                                                        <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-03.jpg')}}" alt="img">
                                                    </span>
                                                    <a href="#" class="avatar avatar-rounded bg-primary fs-12 text-white">1+</a>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="d-flex align-items-center me-2"><i class="ti ti-message-circle me-1"></i></a>
                                                    <a href="javascript:void(0);" class="d-flex align-items-center"><i class="ti ti-paperclip"></i></a>
                                                </div>
                                            </div>
                                        </div><!-- end card body -->
                                    </div><!-- end card -->
                                </div>

                                <div>
                                    <div class="card kanban-card mb-2">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between mb-3">
                                                <div class="d-flex align-items-center">
                                                    <span class="badge bg-success badge-xs d-flex align-items-center justify-content-center"><i class="fas fa-circle fs-7 me-1"></i>Low</span>
                                                </div>
                                                <div class="dropdown">
                                                    <a href="javascript:void(0);" class="d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </a>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a href="javascript:void(0);" class="dropdown-item rounded-1" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash me-2"></i>Delete</a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center mb-3">
                                                <span class="avatar avatar-xs rounded-circle bg-warning me-2">
                                                <img src="{{URL::asset('build/img/icons/kanban-arrow.svg')}}" class="w-auto h-auto" alt="Img">
                                            </span>
                                                <h6 class="d-flex align-items-center mb-0">Dreams Estate</h6>
                                            </div>
                                            <div class="d-flex align-items-center border-bottom mb-3 pb-3">
                                                <div class="me-2 pe-2 border-end">
                                                    <span class="fw-medium fs-12 d-block mb-1">Budget</span>
                                                    <p class="fs-12 text-dark mb-0">$24,000</p>
                                                </div>
                                                <div class="me-2 pe-2 border-end">
                                                    <span class="fw-medium fs-12 d-block mb-1">Tasks</span>
                                                    <p class="fs-12 text-dark mb-0">12/15</p>
                                                </div>
                                                <div>
                                                    <span class="fw-medium fs-12 d-block mb-1">Due on</span>
                                                    <p class="fs-12 text-dark mb-0">15 Apr</p>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="avatar-list-stacked avatar-group-sm me-3">
                                                    <span class="avatar avatar-rounded">
                                                        <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-10.jpg')}}" alt="img">
                                                    </span>
                                                    <span class="avatar avatar-rounded">
                                                        <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-04.jpg')}}" alt="img">
                                                    </span>
                                                    <span class="avatar avatar-rounded">
                                                        <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" alt="img">
                                                    </span>
                                                    <span class="avatar avatar-rounded">
                                                        <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" alt="img">
                                                    </span>
                                                    <span class="avatar avatar-rounded">
                                                        <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-03.jpg')}}" alt="img">
                                                    </span>
                                                    <a href="#" class="avatar avatar-rounded bg-primary fs-12 text-white">1+</a>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="d-flex align-items-center me-2"><i class="ti ti-message-circle me-1"></i></a>
                                                    <a href="javascript:void(0);" class="d-flex align-items-center"><i class="ti ti-paperclip"></i></a>
                                                </div>
                                            </div>
                                        </div><!-- end card body -->
                                    </div><!-- end card -->
                                </div>

                            </div>

                            <div class="pt-2">
                                <a href="#" class="btn btn-secondary d-flex align-items-center justify-content-center" data-bs-toggle="modal" data-bs-target="#add-task">
                                    <i class="ti ti-plus me-2"></i> New Project
                                </a>
                            </div>

                        </div>

                        <div class="p-2 rounded bg-light border w-100">

                            <div class="bg-white border p-3 rounded mb-2">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <span class="bg-soft-success p-1 d-flex rounded-circle me-2"><span class="bg-success rounded-circle d-block p-1"></span></span>
                                        <h6 class="me-2 mb-0">Completed</h6>
                                        <span class="badge bg-light  text-dark rounded-pill">10</span>
                                    </div>
                                    <div class="dropdown">
                                        <a href="javascript:void(0);" class="d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                            <i class="ti ti-dots-vertical"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item rounded-1" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash me-2"></i>Delete</a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="kanban-drag" id="drag-four">

                                <div>
                                    <div class="card kanban-card mb-2">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between mb-3">
                                                <div class="d-flex align-items-center">
                                                    <span class="badge bg-warning badge-xs d-flex align-items-center justify-content-center"><i class="fas fa-circle fs-7 me-1"></i>Medium</span>
                                                </div>
                                                <div class="dropdown">
                                                    <a href="javascript:void(0);" class="d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </a>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a href="javascript:void(0);" class="dropdown-item rounded-1" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash me-2"></i>Delete</a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center mb-3">
                                                <span class="avatar avatar-xs rounded-circle bg-warning me-2">
                                                <img src="{{URL::asset('build/img/icons/kanban-arrow.svg')}}" class="w-auto h-auto" alt="Img">
                                            </span>
                                                <h6 class="d-flex align-items-center mb-0">Dreams Rent</h6>
                                            </div>
                                            <div class="d-flex align-items-center border-bottom mb-3 pb-3">
                                                <div class="me-2 pe-2 border-end">
                                                    <span class="fw-medium fs-12 d-block mb-1">Budget</span>
                                                    <p class="fs-12 text-dark mb-0">$24,000</p>
                                                </div>
                                                <div class="me-2 pe-2 border-end">
                                                    <span class="fw-medium fs-12 d-block mb-1">Tasks</span>
                                                    <p class="fs-12 text-dark mb-0">12/15</p>
                                                </div>
                                                <div>
                                                    <span class="fw-medium fs-12 d-block mb-1">Due on</span>
                                                    <p class="fs-12 text-dark mb-0">15 Apr</p>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="avatar-list-stacked avatar-group-sm me-3">
                                                    <span class="avatar avatar-rounded">
                                                        <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-10.jpg')}}" alt="img">
                                                    </span>
                                                    <span class="avatar avatar-rounded">
                                                        <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-04.jpg')}}" alt="img">
                                                    </span>
                                                    <span class="avatar avatar-rounded">
                                                        <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" alt="img">
                                                    </span>
                                                    <span class="avatar avatar-rounded">
                                                        <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" alt="img">
                                                    </span>
                                                    <span class="avatar avatar-rounded">
                                                        <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-03.jpg')}}" alt="img">
                                                    </span>
                                                    <a href="#" class="avatar avatar-rounded bg-primary fs-12 text-white">1+</a>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="d-flex align-items-center me-2"><i class="ti ti-message-circle me-1"></i></a>
                                                    <a href="javascript:void(0);" class="d-flex align-items-center"><i class="ti ti-paperclip"></i></a>
                                                </div>
                                            </div>
                                        </div><!-- end card body -->
                                    </div><!-- end card -->
                                </div>

                            </div>

                            <div class="pt-2">
                                <a href="#" class="btn btn-secondary d-flex align-items-center justify-content-center" data-bs-toggle="modal" data-bs-target="#add-task">
                                    <i class="ti ti-plus me-2"></i> New Project
                                </a>
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