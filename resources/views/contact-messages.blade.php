<?php $page = 'contact-messages'; ?>
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
                <div class="d-flex align-items-center">
                    <h4 class="fw-bold mb-0 me-2">Contact Message</h4>
                    <span class="badge badge-soft-primary border pt-1 px-2 border-primary fw-medium">Total Contact Message : 565</span>
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
                    <div class="dropdown me-2">
                        <a href="javascript:void(0);" class="dropdown-toggle btn bg-white btn-md d-inline-flex align-items-center fw-normal rounded border text-dark px-2 py-1 fs-14" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                            <i class="ti ti-filter me-2 fs-14"></i>Filter 
                        </a>
                        <div class="dropdown-menu dropdown-lg dropdown-menu-end filter-dropdown" id="filter-dropdown">
                            <div class="d-flex align-items-center justify-content-between border-bottom filter-header">
                                <h4 class="fs-18 fw-bold">Filter</h4>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="link-danger text-decoration-underline me-3">Clear All</a>
                                </div>
                            </div>
                            <form action="#">
                                <div class="filter-body pb-1">
                                    <div class="mb-3">
                                        <label class="form-label">Name</label>
                                        <div class="dropdown">
                                            <a href="javascript:void(0);" class="dropdown-toggle btn bg-white  d-flex align-items-center justify-content-start fs-13 p-2 fw-normal border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                                                Select
                                            </a>
                                            <div class="dropdown-menu shadow-lg w-100 dropdown-info p-3">
                                                <div class="mb-3">
                                                    <div class="input-icon-start position-relative">
                                                        <span class="input-icon-addon fs-12">
                                                            <i class="isax isax-search-normal"></i>
                                                        </span>
                                                        <input type="text" class="form-control form-control-sm" placeholder="Search">
                                                    </div>
                                                </div>
                                                <ul class="mb-3">
                                                    <li class="d-flex align-items-center justify-content-between mb-3">
                                                        <label class="d-inline-flex align-items-center text-gray-9">
                                                            <input class="form-check-input select-all m-0 me-2" type="checkbox"> Select All
                                                        </label>
                                                        <a href="javascript:void(0);" class="link-danger fw-medium text-decoration-underline">Reset</a>
                                                    </li>
                                                    <li>
                                                        <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                            <input class="form-check-input m-0 me-2" type="checkbox">
                                                            <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-33.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Alberto Ripley
                                                        </label>
                                                    </li>
                                                    <li>
                                                        <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                            <input class="form-check-input m-0 me-2" type="checkbox">
                                                            <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-12.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Bernard Griffith
                                                        </label>
                                                    </li>
                                                    <li>
                                                        <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                            <input class="form-check-input m-0 me-2" type="checkbox">
                                                            <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-02.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Carol Lam
                                                        </label>
                                                    </li>
                                                    <li>
                                                        <label class="dropdown-item px-2 d-flex align-items-center text-dark">
                                                            <input class="form-check-input m-0 me-2" type="checkbox">
                                                            <span class="avatar avatar-sm rounded-circle me-2"><img src="{{URL::asset('build/img/users/user-08.jpg')}}" class="flex-shrink-0 rounded-circle" alt="img"></span>Ezra Belcher
                                                        </label>
                                                    </li>
                                                </ul>
                                                <div class="row g-2">
                                                    <div class="col-6">
                                                        <a href="javascript:void(0);" class="btn btn-outline-white w-100 close-filter">Cancel</a>
                                                    </div>
                                                    <div class="col-6">
                                                        <a href="javascript:void(0);" class="btn btn-primary w-100">Select</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <label class="form-label">Date</label>
                                        </div>
                                        <div class="input-group position-relative">
                                            <input type="text" class="form-control date-range bookingrange rounded-end h-auto py-2 bg-white">
                                            <span class="input-icon-addon fs-16 text-gray-9">
                                                <i class="ti ti-calendar"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="filter-footer d-flex align-items-center justify-content-end border-top">
                                    <a href="javascript:void(0);" class="btn btn-light btn-md me-2" id="close-filter">Close</a>
                                    <button type="submit" class="btn btn-primary btn-md">Filter</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="dropdown">
                        <a href="javascript:void(0);" class="dropdown-toggle btn bg-white btn-md d-inline-flex align-items-center fw-normal rounded border text-dark px-2 py-1 fs-14" data-bs-toggle="dropdown">
                            <span  class="me-1"> Sort By : </span>  Recent
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end p-2">
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

            <div class="table-responsive">
                <table class="table table-nowrap datatable">
                    <thead class="thead-light">
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Message</th>
                            <th>Created On</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-sm me-2">
                                        <img src="{{URL::asset('build/img/users/user-33.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="fw-medium">Alberto Ripley</a>
                                </div>
                            </td>
                            <td>+1 41245 54132</td>
                            <td>alberto@example.com</td>
                            <td><p class="truncate-text">Unable to log into my account. Can you assist?</p></td>
                            <td>30 Apr 2025</td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_contact">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_contact">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-sm me-2">
                                        <img src="{{URL::asset('build/img/users/user-01.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="fw-medium">Susan Babin</a>
                                </div>
                            </td>
                            <td>+1 54554 54789</td>
                            <td>susan@example.com</td>
                            <td><p class="truncate-text">My invoices are not being sent to clients. Please help!</p></td>
                            <td>15 Apr 2025</td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_contact">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_contact">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-sm me-2">
                                        <img src="{{URL::asset('build/img/users/user-02.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="fw-medium">Carol Lam</a>
                                </div>
                            </td>
                            <td>+1  43554 54985</td>
                            <td>carol@example.com</td>
                            <td><p class="truncate-text">How do I integrate payment gateways for faster transactions?</p></td>
                            <td>02 Apr 2025</td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_contact">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_contact">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-sm me-2">
                                        <img src="{{URL::asset('build/img/users/user-04.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="fw-medium">Marsha Noland</a>
                                </div>
                            </td>
                            <td>+1 47554 54257</td>
                            <td>marsha@example.com</td>
                            <td><p class="truncate-text">I need help generating financial reports for my business.</p></td>
                            <td>27 Mar 2025</td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_contact">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_contact">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-sm me-2">
                                        <img src="{{URL::asset('build/img/users/user-05.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="fw-medium">Irma Armstrong</a>
                                </div>
                            </td>
                            <td>+1 54114 57526</td>
                            <td>irma@example.com</td>
                            <td><p class="truncate-text">Can I upgrade my plan without losing my existing data?</p></td>
                            <td>12 Mar 2025</td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_contact">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_contact">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-sm me-2">
                                        <img src="{{URL::asset('build/img/users/user-07.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="fw-medium">Jesus Adams</a>
                                </div>
                            </td>
                            <td>+1 51247 56574</td>
                            <td>jesus@example.com</td>
                            <td><p class="truncate-text">How does the system calculate taxes automatically?</p></td>
                            <td>05 Mar 2025</td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_contact">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_contact">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-sm me-2">
                                        <img src="{{URL::asset('build/img/users/user-08.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="fw-medium">Ezra Belcher</a>
                                </div>
                            </td>
                            <td>+1 41452 25741</td>
                            <td>ezra@example.com</td>
                            <td><p class="truncate-text">The mobile app crashes frequently when generating invoices.</p></td>
                            <td>19 Feb 2025</td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_contact">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_contact">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-sm me-2">
                                        <img src="{{URL::asset('build/img/users/user-10.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="fw-medium">Glen Lentz</a>
                                </div>
                            </td>
                            <td>+1 51425 21498</td>
                            <td>glen@example.com</td>
                            <td><p class="truncate-text">How do I categorize expenses for better tracking?</p></td>
                            <td>16 Feb 2025</td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_contact">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_contact">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-sm me-2">
                                        <img src="{{URL::asset('build/img/users/user-12.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="fw-medium">Bernard Griffith</a>
                                </div>
                            </td>
                            <td>+1 45214 98741</td>
                            <td>bernard@example.com</td>
                            <td><p class="truncate-text">Can I customize invoice templates with my company branding?</p></td>
                            <td>01 Feb 2025</td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_contact">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_contact">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-sm me-2">
                                        <img src="{{URL::asset('build/img/users/user-14.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="fw-medium">John Elsass</a>
                                </div>
                            </td>
                            <td>+1 41245 32540</td>
                            <td>john@example.com</td>
                            <td><p class="truncate-text">How can I add team members with different permission levels?</p></td>
                            <td>20 Jas 2025</td>
                            <td class="action-item">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="btn p-1 btn-white border">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu p-2">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#edit_contact">Edit</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_contact">Delete</a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                    </tbody>
                </table>
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