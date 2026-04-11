<?php $page = 'doctors-leaves'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- ========================
        Start Page Content
    ========================= -->
        
    <div class="page-wrapper">

        <!-- Start Content -->
        <div class="content">

            <!-- Start Page Header -->
            <div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-1 border-bottom">
                <div class="flex-grow-1">
                    <h4 class="fw-bold mb-0"> Reviews </h4>
                </div>
                <div class="text-end">
                    <!-- dropdown-->
                    <div class="dropdown me-1">
                        <a href="javascript:void(0);" class="btn btn-md fw-normal border bg-white rounded text-dark d-inline-flex align-items-center fs-14"  data-bs-toggle="dropdown">
                            Export<i class="ti ti-chevron-down ms-2"></i>
                        </a>
                        <ul class="dropdown-menu p-2">
                            <li>
                                <a class="dropdown-item" href="#">Download as PDF</a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#">Download as Excel</a>
                            </li>
                        </ul>
                    </div>
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
                        <a href="javascript:void(0);" class="bg-white border rounded btn btn-md text-dark fs-14 py-1 align-items-center d-flex fw-normal" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                            <i class="ti ti-filter text-gray-5 me-1"></i>Filters
                        </a>
                        <div class="dropdown-menu dropdown-lg dropdown-menu-end filter-dropdown p-0" id="filter-dropdown">
                            <div class="d-flex align-items-center justify-content-between border-bottom filter-header">
                                <h4 class="mb-0 fw-bold">Filter</h4>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="link-danger text-decoration-underline">Clear All</a>
                                </div>
                            </div>
                            <form action="#">
                                <div class="filter-body pb-0">
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <label class="form-label mb-1">Doctor</label>
                                            <a href="javascript:void(0);" class="link-primary mb-1">Reset</a>
                                        </div>
                                        <select class="select2" multiple="multiple">
                                            <option value="m-1" selected>Dr. Mick Thompson</option>
                                            <option value="m-2">Dr. Sarah Johnson</option>
                                            <option value="m-3">Dr. Emily Carter</option>
                                            <option value="m-4">Dr. David Lee</option>
                                            <option value="m-5">Dr. Anna Kim</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <label class="form-label">Designation</label>
                                            <a href="javascript:void(0);" class="link-primary mb-1">Reset</a>
                                        </div>
                                        <select class="select2" multiple="multiple">
                                            <option value="m-1" selected>Cardiologist</option>
                                            <option value="m-2">Orthopedic Surgeon</option>
                                            <option value="m-3">Pediatrician</option>
                                            <option value="m-4">Gynecologist</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <label class="form-label">Department</label>
                                            <a href="javascript:void(0);" class="link-primary mb-1">Reset</a>
                                        </div>
                                        <select class="select2" multiple="multiple">
                                            <option value="m-1" selected>Cardiology</option>
                                            <option value="m-2">Orthopedics</option>
                                            <option value="m-3">Pediatrics</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label mb-1 text-dark fs-14 fw-medium">Date<span class="text-danger">*</span></label>
                                        <div class="input-icon-end position-relative">  
                                            <input type="text" class="form-control datetimepicker" placeholder="dd/mm/yyyy">
                                            <span class="input-icon-addon">
                                                <i class="ti ti-calendar"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <label class="form-label">Amount</label>
                                            <a href="javascript:void(0);" class="link-primary mb-1">Reset</a>
                                        </div>
                                        <select class="select2" multiple="multiple">
                                            <option value="m-1" selected>$501 - $1000</option>
                                            <option value="m-2">$501 - $1100</option>
                                            <option value="m-3">$701 - $1200</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <label class="form-label">Status</label>
                                            <a href="javascript:void(0);" class="link-primary mb-1">Reset</a>
                                        </div>
                                        <select class="select2" multiple="multiple">
                                            <option value="m-1" selected>Available</option>
                                            <option value="m-2">Unavailable</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="filter-footer d-flex align-items-center justify-content-end border-top">
                                    <a href="javascript:void(0);" class="btn btn-light btn-md me-2 fw-medium" id="close-filter">Close</a>
                                    <button type="submit" class="btn btn-primary btn-md fw-medium">Filter</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="dropdown">
                        <a href="javascript:void(0);" class="dropdown-toggle btn bg-white btn-md d-inline-flex align-items-center fw-normal rounded border text-dark px-2 py-1 fs-14" data-bs-toggle="dropdown">
                            <span  class="me-1"> Sort By : </span>  Recent
                        </a>
                        <ul class="dropdown-menu  dropdown-menu-end p-2">
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1">Recent</a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1">Oldest</a>
                            </li>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-nowrap datatable">
                    <thead class="thead-light">
                        <tr>
                            <th class="no-sort">
                                Reviewed By
                            </th>
                            <th>Review</th>
                            <th>Ratings</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-sm me-2">
                                        <img src="{{URL::asset('build/img/users/user-33.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);">Alberto Ripley</a>
                                </div>
                            </td>
                            <td>The clinic was well-organized, and I didn’t have to wait long. </td>
                            <td>
                                <div class="star-ratings d-flex align-items-center">
                                    <i class="ti ti-star-filled fs-14 text-warning"></i>
                                    <i class="ti ti-star-filled fs-14 text-warning"></i>
                                    <i class="ti ti-star-filled fs-14 text-warning"></i>
                                    <i class="ti ti-star-filled fs-14 text-warning"></i>
                                    <i class="ti ti-star-filled fs-14 text-warning"></i>
                                </div>
                            </td>
                            <td>30 Apr 2025</td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-sm me-2">
                                        <img src="{{URL::asset('build/img/users/user-01.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);">Susan Babin</a>
                                </div>
                            </td>
                            <td>Dr. Mick explained everything in simple terms and made sure I understood my condition and treatm. </td>
                            <td>
                                <div class="star-ratings d-flex align-items-center">
                                    <i class="ti ti-star-filled fs-14 text-warning"></i>
                                    <i class="ti ti-star-filled fs-14 text-warning"></i>
                                    <i class="ti ti-star-filled fs-14 text-warning"></i>
                                    <i class="ti ti-star-filled fs-14 text-warning"></i>
                                    <i class="ti ti-star-filled fs-14 text-light"></i>
                                </div>
                            </td>
                            <td>15 Apr 2025</td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-sm me-2">
                                        <img src="{{URL::asset('build/img/users/user-02.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);">Carol Lam</a>
                                </div>
                            </td>
                            <td>From the front desk to the consultation, everything was smooth. Dr. Mick and his staff were friend. </td>
                            <td>
                                <div class="star-ratings d-flex align-items-center">
                                    <i class="ti ti-star-filled fs-14 text-warning"></i>
                                    <i class="ti ti-star-filled fs-14 text-warning"></i>
                                    <i class="ti ti-star-filled fs-14 text-warning"></i>
                                    <i class="ti ti-star-filled fs-14 text-warning"></i>
                                    <i class="ti ti-star-filled fs-14 text-light"></i>
                                </div>
                            </td>
                            <td>02 Apr 2025</td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-sm me-2">
                                        <img src="{{URL::asset('build/img/users/user-04.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);">Marsha Noland</a>
                                </div>
                            </td>
                            <td>While the doctor was knowledgeable, I felt a bit rushed during my visit. I would’ve appreciated. </td>
                            <td>
                                <div class="star-ratings d-flex align-items-center">
                                    <i class="ti ti-star-filled fs-14 text-warning"></i>
                                    <i class="ti ti-star-filled fs-14 text-warning"></i>
                                    <i class="ti ti-star-filled fs-14 text-light"></i>
                                    <i class="ti ti-star-filled fs-14 text-light"></i>
                                    <i class="ti ti-star-filled fs-14 text-light"></i>
                                </div>
                            </td>
                            <td>27 Mar 2025</td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-sm me-2">
                                        <img src="{{URL::asset('build/img/users/user-05.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);">Irma Armstrong</a>
                                </div>
                            </td>
                            <td>This is helpful information! If anyone has ongoing symptoms, don’t hesitate to speak to a licensed  </td>
                            <td>
                                <div class="star-ratings d-flex align-items-center">
                                    <i class="ti ti-star-filled fs-14 text-warning"></i>
                                    <i class="ti ti-star-filled fs-14 text-warning"></i>
                                    <i class="ti ti-star-filled fs-14 text-warning"></i>
                                    <i class="ti ti-star-filled fs-14 text-light"></i>
                                    <i class="ti ti-star-filled fs-14 text-light"></i>
                                </div>
                            </td>
                            <td>12 Mar 2025</td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-sm me-2">
                                        <img src="{{URL::asset('build/img/users/user-07.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);">Jesus Adams</a>
                                </div>
                            </td>
                            <td>Very professional and caring staff. I felt heard and understood throughout my treatment. </td>
                            <td>
                                <div class="star-ratings d-flex align-items-center">
                                    <i class="ti ti-star-filled fs-14 text-warning"></i>
                                    <i class="ti ti-star-filled fs-14 text-warning"></i>
                                    <i class="ti ti-star-filled fs-14 text-warning"></i>
                                    <i class="ti ti-star-filled fs-14 text-warning"></i>
                                    <i class="ti ti-star-filled fs-14 text-warning"></i>
                                </div>
                            </td>
                            <td>05 Mar 2025</td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-sm me-2">
                                        <img src="{{URL::asset('build/img/users/user-08.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);">Ezra Belcher</a>
                                </div>
                            </td>
                            <td>The doctors took time to explain everything clearly. I left feeling informed and reassured. </td>
                            <td>
                                <div class="star-ratings d-flex align-items-center">
                                    <i class="ti ti-star-filled fs-14 text-warning"></i>
                                    <i class="ti ti-star-filled fs-14 text-warning"></i>
                                    <i class="ti ti-star-filled fs-14 text-warning"></i>
                                    <i class="ti ti-star-filled fs-14 text-warning"></i>
                                    <i class="ti ti-star-filled fs-14 text-light"></i>
                                </div>
                            </td>
                            <td>19 Feb 2025</td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-sm me-2">
                                        <img src="{{URL::asset('build/img/users/user-10.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);">Glen Lentz</a>
                                </div>
                            </td>
                            <td>Exceptional care from start to finish. The clinic is clean, organized, and patient-focused. </td>
                            <td>
                                <div class="star-ratings d-flex align-items-center">
                                    <i class="ti ti-star-filled fs-14 text-warning"></i>
                                    <i class="ti ti-star-filled fs-14 text-warning"></i>
                                    <i class="ti ti-star-filled fs-14 text-warning"></i>
                                    <i class="ti ti-star-filled fs-14 text-warning"></i>
                                    <i class="ti ti-star-filled fs-14 text-light"></i>
                                </div>
                            </td>
                            <td>16 Feb 2025</td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-sm me-2">
                                        <img src="{{URL::asset('build/img/users/user-12.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);">Bernard Griffith</a>
                                </div>
                            </td>
                            <td>Booking was easy, the team was punctual, and I received excellent medical attention.. </td>
                            <td>
                                <div class="star-ratings d-flex align-items-center">
                                    <i class="ti ti-star-filled fs-14 text-warning"></i>
                                    <i class="ti ti-star-filled fs-14 text-warning"></i>
                                    <i class="ti ti-star-filled fs-14 text-warning"></i>
                                    <i class="ti ti-star-filled fs-14 text-warning"></i>
                                    <i class="ti ti-star-filled fs-14 text-warning"></i>
                                </div>
                            </td>
                            <td>01 Feb 2025</td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-sm me-2">
                                        <img src="{{URL::asset('build/img/users/user-14.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);">John Elsass</a>
                                </div>
                            </td>
                            <td>Great experience! The doctor truly listened and provided personalized advice. </td>
                            <td>
                                <div class="star-ratings d-flex align-items-center">
                                    <i class="ti ti-star-filled fs-14 text-warning"></i>
                                    <i class="ti ti-star-filled fs-14 text-warning"></i>
                                    <i class="ti ti-star-filled fs-14 text-warning"></i>
                                    <i class="ti ti-star-filled fs-14 text-warning"></i>
                                    <i class="ti ti-star-filled fs-14 text-warning"></i>
                                </div>
                            </td>
                            <td>20 Jas 2025</td>
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