<?php $page = 'blog-comments'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- ========================
        Start Page Content
    ========================= -->

    <div class="page-wrapper">

        <!-- Start Content -->
        <div class="content">

            <!-- Page Header -->
            <div class="mb-3 pb-3 border-bottom">
                <div class="d-flex align-items-center">
                    <h4 class="fw-bold mb-0 me-2">Comments</h4>
                    <span class="badge badge-soft-primary border pt-1 px-2 border-primary fw-medium">Total Comments : 365</span>
                </div>
            </div>
            <!-- End Page Header -->

            <!-- Table List -->
            <div class="table-responsive border bg-white">
                <table class="table table-nowrap mb-0">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Comment</th>
                            <th>Created On</th>
                            <th>Review</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-sm rounded-circle me-2 flex-shrink-0">
                                        <img src="{{URL::asset('build/img/users/user-02.jpg')}}" class="rounded-circle" alt="img">
                                    </a>
                                    <div>
                                        <h6 class="fs-14 fw-medium mb-0"><a href="javascript:void(0);">Ruth Castleberry</a></h6>
                                    </div>
                                </div>
                            </td>
                            <td>ruth@gmail.com</td>
                            <td><p class="truncate-text">Great article! Always remember—prevention is better than cure. Regular checkups and healthy habits go a long way</p></td>
                            <td>30 Apr 2025</td>
                            <td>
                                <div class="dropdown me-2">
                                    <a href="javascript:void(0);" class="dropdown-toggle btn btn-white border fw-normal d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                        Publish
                                    </a>
                                    <ul class="dropdown-menu  dropdown-menu-end p-2">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item">Unpublish</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item">Publish</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                            <td>
                                <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_categories"><i class="ti ti-trash"></i></a>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-sm rounded-circle me-2 flex-shrink-0">
                                        <img src="{{URL::asset('build/img/users/user-03.jpg')}}" class="rounded-circle" alt="img">
                                    </a>
                                    <div>
                                        <h6 class="fs-14 fw-medium mb-0"><a href="javascript:void(0);">John Carter</a></h6>
                                    </div>
                                </div>
                            </td>
                            <td>john@gmail.com</td>
                            <td><p class="truncate-text">Glad to see people investing in their health! Just a reminder to always consult your doctor before starting new supplements</p></td>
                            <td>15 Apr 2025</td>
                            <td>
                                <div class="dropdown me-2">
                                    <a href="javascript:void(0);" class="dropdown-toggle btn btn-white border fw-normal d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                        Publish
                                    </a>
                                    <ul class="dropdown-menu  dropdown-menu-end p-2">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item">Unpublish</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item">Publish</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                            <td>
                                <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_categories"><i class="ti ti-trash"></i></a>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-sm rounded-circle me-2 flex-shrink-0">
                                        <img src="{{URL::asset('build/img/users/user-06.jpg')}}" class="rounded-circle" alt="img">
                                    </a>
                                    <div>
                                        <h6 class="fs-14 fw-medium mb-0"><a href="javascript:void(0);">Mary Moore</a></h6>
                                    </div>
                                </div>
                            </td>
                            <td>mary@gmail.com</td>
                            <td><p class="truncate-text">Small daily changes lead to big results. Stay consistent and put your health first—your future self will thank you!</p></td>
                            <td>02 Apr 2025</td>
                            <td>
                                <div class="dropdown me-2">
                                    <a href="javascript:void(0);" class="dropdown-toggle btn btn-white border fw-normal d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                        Publish
                                    </a>
                                    <ul class="dropdown-menu  dropdown-menu-end p-2">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item">Unpublish</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item">Publish</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                            <td>
                                <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_categories"><i class="ti ti-trash"></i></a>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-sm rounded-circle me-2 flex-shrink-0">
                                        <img src="{{URL::asset('build/img/users/user-16.jpg')}}" class="rounded-circle" alt="img">
                                    </a>
                                    <div>
                                        <h6 class="fs-14 fw-medium mb-0"><a href="javascript:void(0);">Emma Barton</a></h6>
                                    </div>
                                </div>
                            </td>
                            <td>emma@gmail.com</td>
                            <td><p class="truncate-text">Appreciate this focus on mental well-being. Taking care of your mind is just as important as your body.</p></td>
                            <td>27 Mar 2025</td>
                            <td>
                                <div class="dropdown me-2">
                                    <a href="javascript:void(0);" class="dropdown-toggle btn btn-white border fw-normal d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                        Publish
                                    </a>
                                    <ul class="dropdown-menu  dropdown-menu-end p-2">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item">Unpublish</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item">Publish</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                            <td>
                                <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_categories"><i class="ti ti-trash"></i></a>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-sm rounded-circle me-2 flex-shrink-0">
                                        <img src="{{URL::asset('build/img/users/user-08.jpg')}}" class="rounded-circle" alt="img">
                                    </a>
                                    <div>
                                        <h6 class="fs-14 fw-medium mb-0"><a href="javascript:void(0);">Kelly Perkins</a></h6>
                                    </div>
                                </div>
                            </td>
                            <td>kelly@gmail.com</td>
                            <td><p class="truncate-text">This is helpful information! If anyone has ongoing symptoms, don’t hesitate to speak to a licensed healthcare provider</p></td>
                            <td>30 Apr 2025</td>
                            <td>
                                <div class="dropdown me-2">
                                    <a href="javascript:void(0);" class="dropdown-toggle btn btn-white border fw-normal d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                        Publish
                                    </a>
                                    <ul class="dropdown-menu  dropdown-menu-end p-2">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item">Unpublish</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item">Publish</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                            <td>
                                <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_categories"><i class="ti ti-trash"></i></a>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-sm rounded-circle me-2 flex-shrink-0">
                                        <img src="{{URL::asset('build/img/users/user-09.jpg')}}" class="rounded-circle" alt="img">
                                    </a>
                                    <div>
                                        <h6 class="fs-14 fw-medium mb-0"><a href="javascript:void(0);">Oscar King</a></h6>
                                    </div>
                                </div>
                            </td>
                            <td>oscar@gmail.com</td>
                            <td><p class="truncate-text">Very professional and caring staff. I felt heard and understood throughout my treatment.</p></td>
                            <td>12 Mar 2025</td>
                            <td>
                                <div class="dropdown me-2">
                                    <a href="javascript:void(0);" class="dropdown-toggle btn btn-white border fw-normal d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                        Publish
                                    </a>
                                    <ul class="dropdown-menu  dropdown-menu-end p-2">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item">Unpublish</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item">Publish</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                            <td>
                                <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_categories"><i class="ti ti-trash"></i></a>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-sm rounded-circle me-2 flex-shrink-0">
                                        <img src="{{URL::asset('build/img/users/user-14.jpg')}}" class="rounded-circle" alt="img">
                                    </a>
                                    <div>
                                        <h6 class="fs-14 fw-medium mb-0"><a href="javascript:void(0);">Daniel Shea</a></h6>
                                    </div>
                                </div>
                            </td>
                            <td>daniel@gmail.com</td>
                            <td><p class="truncate-text">The doctors took time to explain everything clearly. I left feeling informed and reassured.</p></td>
                            <td>05 Mar 2025</td>
                            <td>
                                <div class="dropdown me-2">
                                    <a href="javascript:void(0);" class="dropdown-toggle btn btn-white border fw-normal d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                        Publish
                                    </a>
                                    <ul class="dropdown-menu  dropdown-menu-end p-2">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item">Unpublish</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item">Publish</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                            <td>
                                <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_categories"><i class="ti ti-trash"></i></a>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-sm rounded-circle me-2 flex-shrink-0">
                                        <img src="{{URL::asset('build/img/users/user-15.jpg')}}" class="rounded-circle" alt="img">
                                    </a>
                                    <div>
                                        <h6 class="fs-14 fw-medium mb-0"><a href="javascript:void(0);">Christopher Smith</a></h6>
                                    </div>
                                </div>
                            </td>
                            <td>chris@gmail.com</td>
                            <td><p class="truncate-text">Exceptional care from start to finish. The clinic is clean, organized, and patient-focused.</p></td>
                            <td>24 Feb 2025</td>
                            <td>
                                <div class="dropdown me-2">
                                    <a href="javascript:void(0);" class="dropdown-toggle btn btn-white border fw-normal d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                        Publish
                                    </a>
                                    <ul class="dropdown-menu  dropdown-menu-end p-2">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item">Unpublish</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item">Publish</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                            <td>
                                <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_categories"><i class="ti ti-trash"></i></a>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-sm rounded-circle me-2 flex-shrink-0">
                                        <img src="{{URL::asset('build/img/users/user-23.jpg')}}" class="rounded-circle" alt="img">
                                    </a>
                                    <div>
                                        <h6 class="fs-14 fw-medium mb-0"><a href="javascript:void(0);">Steve Mason</a></h6>
                                    </div>
                                </div>
                            </td>
                            <td>steve@gmail.com</td>
                            <td><p class="truncate-text">Booking was easy, the team was punctual, and I received excellent medical attention.</p></td>
                            <td>16 Feb 2025</td>
                            <td>
                                <div class="dropdown me-2">
                                    <a href="javascript:void(0);" class="dropdown-toggle btn btn-white border fw-normal d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                        Publish
                                    </a>
                                    <ul class="dropdown-menu  dropdown-menu-end p-2">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item">Unpublish</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item">Publish</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                            <td>
                                <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_categories"><i class="ti ti-trash"></i></a>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-sm rounded-circle me-2 flex-shrink-0">
                                        <img src="{{URL::asset('build/img/users/user-26.jpg')}}" class="rounded-circle" alt="img">
                                    </a>
                                    <div>
                                        <h6 class="fs-14 fw-medium mb-0"><a href="javascript:void(0);">Charles Jarrell</a></h6>
                                    </div>
                                </div>
                            </td>
                            <td>charles@gmail.com</td>
                            <td><p class="truncate-text">Great experience! The doctor truly listened and provided personalized advice.</p></td>
                            <td>01 Feb 2025</td>
                            <td>
                                <div class="dropdown me-2">
                                    <a href="javascript:void(0);" class="dropdown-toggle btn btn-white border fw-normal d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                        Publish
                                    </a>
                                    <ul class="dropdown-menu  dropdown-menu-end p-2">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item">Unpublish</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item">Publish</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                            <td>
                                <a href="javascript:void(0);" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#delete_categories"><i class="ti ti-trash"></i></a>
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