<?php $page = 'todo-list'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- ========================
        Start Page Content
    ========================= -->

    <div class="page-wrapper">
        
        <!-- Start Content -->
        <div class="content content-two">

            <!-- Page Header -->
            <div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3">
                <div class="flex-grow-1">
                    <h4 class="fs-18 fw-semibold mb-0">Todo List</h4>
                </div>
                <div class="text-end">
                    <ol class="breadcrumb m-0 py-0">
                        <li class="breadcrumb-item"><a href="{{url('index')}}">Home</a></li>                              
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Applications</a></li>                              
                        <li class="breadcrumb-item active" aria-current="page">Todo List</li>
                    </ol>
                </div>
            </div>
            <!-- End Page Header -->
            
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <a href="#" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#add_todo"><i class="ti ti-circle-plus me-1"></i>Create New</a>
                <ul class="d-flex align-items-center flex-shrink-0 list-unstyled mb-0">
                    <li>
                        <a href="{{url('todo')}}" class="btn btn-icon btn-sm bg-white text-dark me-2"><i class="ti ti-layout-grid"></i></a>
                    </li>
                    <li>
                        <a href="{{url('todo-list')}}" class="btn btn-icon btn-sm bg-primary text-white active me-2"><i class="ti ti-list-tree"></i></a>
                    </li>
                </ul>
            </div>

            <div>

                <div class="table-responsive table-nowrap">
                    <table class="table border mb-0">
                        <thead class="table-light text-uppercase">
                            <tr>
                                <th>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox" id="select-all">
                                    </div>
                                </th>
                                <th>Company Name</th>
                                <th>Tags</th>
                                <th>Assignee</th>
                                <th>Created On</th>
                                <th>Progress</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                        <span class="mx-2 d-flex align-items-center rating-select"><i class="ti ti-star"></i></span>
                                        <span class="d-flex align-items-center"><i class="ti ti-square-rounded text-danger me-2"></i></span>
                                    </div>
                                </td>
                                <td>
                                    <p class="fw-medium text-dark">Respond to any pending messages</p>
                                </td>
                                <td>
                                    <span class="badge bg-info">Social</span>
                                </td>
                                <td>
                                    <div class="avatar-list-stacked avatar-group-sm">
                                        <span class="avatar avatar-rounded">
                                            <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" alt="img">
                                        </span>
                                        <span class="avatar avatar-rounded">
                                            <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" alt="img">
                                        </span>
                                        <span class="avatar avatar-rounded">
                                            <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-05.jpg')}}" alt="img">
                                        </span>
                                    </div>
                                </td>
                                <td>14 Jan 2024</td>
                                <td>
                                    <span class="d-block mb-1">Progress : 100%</span>
                                    <div class="progress progress-xs flex-grow-1 mb-2" style="width: 190px;">
                                        <div class="progress-bar bg-success rounded" role="progressbar" style="width: 100%;" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </td>
                                <td>14 Jan 2024</td>
                                <td>
                                    <span class="badge badge-soft-success d-inline-flex align-items-center">
                                        <i class="ti ti-circle-filled fs-5 me-1"></i>
                                        Completed
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#" class="btn btn-sm btn-icon" data-bs-toggle="modal" data-bs-target="#edit_todo">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                        <a href="#" class="btn btn-sm btn-icon" data-bs-toggle="modal" data-bs-target="#delete_modal">
                                            <i class="ti ti-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                        <span class="mx-2 d-flex align-items-center rating-select"><i class="ti ti-star-filled filled"></i></span>
                                        <span class="d-flex align-items-center"><i class="ti ti-square-rounded text-purple me-2"></i></span>
                                    </div>
                                </td>
                                <td>
                                    <p class="fw-medium text-dark">Update calendar and schedule</p>
                                </td>
                                <td>
                                    <span class="badge bg-primary">Meetings</span>
                                </td>
                                <td>
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
                                </td>
                                <td>21 Jan 2024</td>
                                <td>
                                    <span class="d-block mb-1">Progress : 15%</span>
                                    <div class="progress progress-xs flex-grow-1 mb-2" style="width: 190px;">
                                        <div class="progress-bar bg-danger rounded" role="progressbar" style="width: 15%;" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </td>
                                <td>21 Jan 2024</td>
                                <td>
                                    <span class="badge badge-soft-secondary d-inline-flex align-items-center">
                                        <i class="ti ti-circle-filled fs-5 me-1"></i>
                                        Pending
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#" class="btn btn-sm btn-icon" data-bs-toggle="modal" data-bs-target="#edit_todo">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                        <a href="#" class="btn btn-sm btn-icon" data-bs-toggle="modal" data-bs-target="#delete_modal">
                                            <i class="ti ti-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                        <span class="mx-2 d-flex align-items-center rating-select"><i class="ti ti-star"></i></span>
                                        <span class="d-flex align-items-center"><i class="ti ti-square-rounded text-purple me-2"></i></span>
                                    </div>
                                </td>
                                <td>
                                    <p class="fw-medium text-dark">Respond to any pending messages</p>
                                </td>
                                <td>
                                    <span class="badge bg-danger">Research</span>
                                </td>
                                <td>
                                    <div class="avatar-list-stacked avatar-group-sm">
                                        <span class="avatar avatar-rounded">
                                            <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-04.jpg')}}" alt="img">
                                        </span>
                                        <span class="avatar avatar-rounded">
                                            <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-05.jpg')}}" alt="img">
                                        </span>
                                        <span class="avatar avatar-rounded">
                                            <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-06.jpg')}}" alt="img">
                                        </span>
                                    </div>
                                </td>
                                <td>20 Feb 2024</td>
                                <td>
                                    <span class="d-block mb-1">Progress : 45%</span>
                                    <div class="progress progress-xs flex-grow-1 mb-2" style="width: 190px;">
                                        <div class="progress-bar bg-warning rounded" role="progressbar" style="width: 45%;" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </td>
                                <td>20 Feb 2024</td>
                                <td>
                                    <span class="badge badge-soft-primary d-inline-flex align-items-center">
                                        <i class="ti ti-circle-filled fs-5 me-1"></i>
                                        Inprogress
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#" class="btn btn-sm btn-icon" data-bs-toggle="modal" data-bs-target="#edit_todo">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                        <a href="#" class="btn btn-sm btn-icon" data-bs-toggle="modal" data-bs-target="#delete_modal">
                                            <i class="ti ti-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                        <span class="mx-2 d-flex align-items-center rating-select"><i class="ti ti-star"></i></span>
                                        <span class="d-flex align-items-center"><i class="ti ti-square-rounded text-warning me-2"></i></span>
                                    </div>
                                </td>
                                <td>
                                    <p class="fw-medium text-dark">Attend team meeting at 10:00 AM</p>
                                </td>
                                <td>
                                    <span class="badge bg-primary">Web Design</span>
                                </td>
                                <td>
                                    <div class="avatar-list-stacked avatar-group-sm">
                                        <span class="avatar avatar-rounded">
                                            <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-05.jpg')}}" alt="img">
                                        </span>
                                        <span class="avatar avatar-rounded">
                                            <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-06.jpg')}}" alt="img">
                                        </span>
                                        <span class="avatar avatar-rounded">
                                            <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-07.jpg')}}" alt="img">
                                        </span>
                                    </div>
                                </td>
                                <td>15 Mar 2024</td>
                                <td>
                                    <span class="d-block mb-1">Progress : 40%</span>
                                    <div class="progress progress-xs flex-grow-1 mb-2" style="width: 190px;">
                                        <div class="progress-bar bg-warning rounded" role="progressbar" style="width: 40%;" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </td>
                                <td>15 Mar 2024</td>
                                <td>
                                    <span class="badge badge-soft-primary d-inline-flex align-items-center">
                                        <i class="ti ti-circle-filled fs-5 me-1"></i>
                                        Inprogress
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#" class="btn btn-sm btn-icon" data-bs-toggle="modal" data-bs-target="#edit_todo">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                        <a href="#" class="btn btn-sm btn-icon" data-bs-toggle="modal" data-bs-target="#delete_modal">
                                            <i class="ti ti-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                        <span class="mx-2 d-flex align-items-center rating-select"><i class="ti ti-star"></i></span>
                                        <span class="d-flex align-items-center"><i class="ti ti-square-rounded text-purple me-2"></i></span>
                                    </div>
                                </td>
                                <td>
                                    <p class="fw-medium text-dark">Check and respond to emails</p>
                                </td>
                                <td>
                                    <span class="badge bg-info">Reminder</span>
                                </td>
                                <td>
                                    <div class="avatar-list-stacked avatar-group-sm">
                                        <span class="avatar avatar-rounded">
                                            <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-08.jpg')}}" alt="img">
                                        </span>
                                        <span class="avatar avatar-rounded">
                                            <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-09.jpg')}}" alt="img">
                                        </span>
                                        <span class="avatar avatar-rounded">
                                            <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-10.jpg')}}" alt="img">
                                        </span>
                                    </div>
                                </td>
                                <td>12 Apr 2024</td>
                                <td>
                                    <span class="d-block mb-1">Progress : 65%</span>
                                    <div class="progress progress-xs flex-grow-1 mb-2" style="width: 190px;">
                                        <div class="progress-bar bg-purple rounded" role="progressbar" style="width: 65%;" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </td>
                                <td>12 Apr 2024</td>
                                <td>
                                    <span class="badge badge-soft-secondary d-inline-flex align-items-center">
                                        <i class="ti ti-circle-filled fs-5 me-1"></i>
                                        Pending
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#" class="btn btn-sm btn-icon" data-bs-toggle="modal" data-bs-target="#edit_todo">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                        <a href="#" class="btn btn-sm btn-icon" data-bs-toggle="modal" data-bs-target="#delete_modal">
                                            <i class="ti ti-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                        <span class="mx-2 d-flex align-items-center rating-select"><i class="ti ti-star"></i></span>
                                        <span class="d-flex align-items-center"><i class="ti ti-square-rounded text-warning me-2"></i></span>
                                    </div>
                                </td>
                                <td>
                                    <p class="fw-medium text-dark">Coordinate with department head</p>
                                </td>
                                <td>
                                    <span class="badge bg-danger">Internal</span>
                                </td>
                                <td>
                                    <div class="avatar-list-stacked avatar-group-sm">
                                        <span class="avatar avatar-rounded">
                                            <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-11.jpg')}}" alt="img">
                                        </span>
                                        <span class="avatar avatar-rounded">
                                            <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-12.jpg')}}" alt="img">
                                        </span>
                                        <span class="avatar avatar-rounded">
                                            <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-13.jpg')}}" alt="img">
                                        </span>
                                    </div>
                                </td>
                                <td>20 Apr 2024</td>
                                <td>
                                    <span class="d-block mb-1">Progress : 85%</span>
                                    <div class="progress progress-xs flex-grow-1 mb-2" style="width: 190px;">
                                        <div class="progress-bar bg-pink rounded" role="progressbar" style="width: 85%;" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </td>
                                <td>20 Apr 2024</td>
                                <td>
                                    <span class="badge badge-soft-danger d-inline-flex align-items-center">
                                        <i class="ti ti-circle-filled fs-5 me-1"></i>
                                        Onhold
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#" class="btn btn-sm btn-icon" data-bs-toggle="modal" data-bs-target="#edit_todo">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                        <a href="#" class="btn btn-sm btn-icon" data-bs-toggle="modal" data-bs-target="#delete_modal">
                                            <i class="ti ti-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                        <span class="mx-2 d-flex align-items-center rating-select"><i class="ti ti-star"></i></span>
                                        <span class="d-flex align-items-center"><i class="ti ti-square-rounded text-success me-2"></i></span>
                                    </div>
                                </td>
                                <td>
                                    <p class="fw-medium text-dark">Plan tasks for the next day</p>
                                </td>
                                <td>
                                    <span class="badge bg-info">Social</span>
                                </td>
                                <td>
                                    <div class="avatar-list-stacked avatar-group-sm">
                                        <span class="avatar avatar-rounded">
                                            <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-14.jpg')}}" alt="img">
                                        </span>
                                        <span class="avatar avatar-rounded">
                                            <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-15.jpg')}}" alt="img">
                                        </span>
                                        <span class="avatar avatar-rounded">
                                            <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-16.jpg')}}" alt="img">
                                        </span>
                                    </div>
                                </td>
                                <td>06 Jul 2024</td>
                                <td>
                                    <span class="d-block mb-1">Progress : 100%</span>
                                    <div class="progress progress-xs flex-grow-1 mb-2" style="width: 190px;">
                                        <div class="progress-bar bg-success rounded" role="progressbar" style="width: 100%;" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </td>
                                <td>06 Jul 2024</td>
                                <td>
                                    <span class="badge badge-soft-success d-inline-flex align-items-center">
                                        <i class="ti ti-circle-filled fs-5 me-1"></i>
                                        Completed
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#" class="btn btn-sm btn-icon" data-bs-toggle="modal" data-bs-target="#edit_todo">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                        <a href="#" class="btn btn-sm btn-icon" data-bs-toggle="modal" data-bs-target="#delete_modal">
                                            <i class="ti ti-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                        <span class="mx-2 d-flex align-items-center rating-select"><i class="ti ti-star"></i></span>
                                        <span class="d-flex align-items-center"><i class="ti ti-square-rounded text-success me-2"></i></span>
                                    </div>
                                </td>
                                <td>
                                    <p class="fw-medium text-dark">Finalize project proposal</p>
                                </td>
                                <td>
                                    <span class="badge bg-success">Projects</span>
                                </td>
                                <td>
                                    <div class="avatar-list-stacked avatar-group-sm">
                                        <span class="avatar avatar-rounded">
                                            <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-17.jpg')}}" alt="img">
                                        </span>
                                        <span class="avatar avatar-rounded">
                                            <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-18.jpg')}}" alt="img">
                                        </span>
                                        <span class="avatar avatar-rounded">
                                            <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-19.jpg')}}" alt="img">
                                        </span>
                                    </div>
                                </td>
                                <td>02 Sep 2024</td>
                                <td>
                                    <span class="d-block mb-1">Progress : 65%</span>
                                    <div class="progress progress-xs flex-grow-1 mb-2" style="width: 190px;">
                                        <div class="progress-bar bg-danger rounded" role="progressbar" style="width: 65%;" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </td>
                                <td>02 Sep 2024</td>
                                <td>
                                    <span class="badge badge-soft-danger d-inline-flex align-items-center">
                                        <i class="ti ti-circle-filled fs-5 me-1"></i>
                                        Onhold
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#" class="btn btn-sm btn-icon" data-bs-toggle="modal" data-bs-target="#edit_todo">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                        <a href="#" class="btn btn-sm btn-icon" data-bs-toggle="modal" data-bs-target="#delete_modal">
                                            <i class="ti ti-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                        <span class="mx-2 d-flex align-items-center rating-select"><i class="ti ti-star"></i></span>
                                        <span class="d-flex align-items-center"><i class="ti ti-square-rounded text-purple me-2"></i></span>
                                    </div>
                                </td>
                                <td>
                                    <p class="fw-medium text-dark">Submit to supervisor by EOD</p>
                                </td>
                                <td>
                                    <span class="badge bg-info">Reminder</span>
                                </td>
                                <td>
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
                                </td>
                                <td>15 Nov 2024</td>
                                <td>
                                    <span class="d-block mb-1">Progress : 75%</span>
                                    <div class="progress progress-xs flex-grow-1 mb-2" style="width: 190px;">
                                        <div class="progress-bar bg-purple rounded" role="progressbar" style="width: 75%;" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </td>
                                <td>15 Nov 2024</td>
                                <td>
                                    <span class="badge badge-soft-primary d-inline-flex align-items-center">
                                        <i class="ti ti-circle-filled fs-5 me-1"></i>
                                        Inprogress
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#" class="btn btn-sm btn-icon" data-bs-toggle="modal" data-bs-target="#edit_todo">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                        <a href="#" class="btn btn-sm btn-icon" data-bs-toggle="modal" data-bs-target="#delete_modal">
                                            <i class="ti ti-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                        <span class="mx-2 d-flex align-items-center rating-select"><i class="ti ti-star"></i></span>
                                        <span class="d-flex align-items-center"><i class="ti ti-square-rounded text-success me-2"></i></span>
                                    </div>
                                </td>
                                <td>
                                    <p class="fw-medium text-dark">Prepare presentation slides</p>
                                </td>
                                <td>
                                    <span class="badge bg-danger">Research</span>
                                </td>
                                <td>
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
                                </td>
                                <td>10 Dec 2024</td>
                                <td>
                                    <span class="d-block mb-1">Progress : 90%</span>
                                    <div class="progress progress-xs flex-grow-1 mb-2" style="width: 190px;">
                                        <div class="progress-bar bg-pink rounded" role="progressbar" style="width: 90%;" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </td>
                                <td>10 Dec 2024</td>
                                <td>
                                    <span class="badge badge-soft-secondary d-inline-flex align-items-center">
                                        <i class="ti ti-circle-filled fs-5 me-1"></i>
                                        Pending
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#" class="btn btn-sm btn-icon" data-bs-toggle="modal" data-bs-target="#edit_todo">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                        <a href="#" class="btn btn-sm btn-icon" data-bs-toggle="modal" data-bs-target="#delete_modal">
                                            <i class="ti ti-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div> <!-- end table responsive -->                    
                    
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