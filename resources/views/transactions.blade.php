<?php $page = 'transactions'; ?>
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
                    <h4 class="fw-bold mb-0"> Transactions <span class="badge badge-soft-primary fw-medium border py-1 px-2 border-primary fs-13 ms-1">Total Transactions : 565</span> </h4>
                </div>
                <div class="text-end d-flex">
                    <!-- dropdown-->
                    <div class="dropdown me-1">
                        <a href="javascript:void(0);" class="btn btn-md fs-14 fw-normal border bg-white rounded text-dark d-inline-flex align-items-center"  data-bs-toggle="dropdown">
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

            <!--  Start Filter -->
            <div class=" d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="search-set mb-3">
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <div class="table-search d-flex align-items-center mb-0">
                                <div class="search-input">
                                    <a href="javascript:void(0);" class="btn-searchset"></a>
                                </div>
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
                                            <label class="form-label mb-1">Patient</label>
                                            <a href="javascript:void(0);" class="link-primary mb-1">Reset</a>
                                        </div>
                                        <select class="select2" multiple="multiple">
                                            <option value="m-1" selected>Alberto Ripley</option>
                                            <option value="m-2">Susan Babin</option>
                                            <option value="m-3">Martin Lisa</option>
                                            <option value="m-4">Stella Mary</option>
                                            <option value="m-5">Carol Lam</option>
                                            <option value="m-6">Jesus Adams</option>
                                            <option value="m-7">Ezra Belcher</option>
                                            <option value="m-8">Unit Manager </option>
                                            <option value="m-9">Bernard Griffith</option>
                                            <option value="m-10">John Elsass</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <label class="form-label">Practioner</label>
                                            <a href="javascript:void(0);" class="link-primary mb-1">Reset</a>
                                        </div>
                                        <select class="select2" multiple="multiple">
                                            <option value="m-1" selected>Dr.Mick Thompson</option>
                                            <option value="m-2">Dr.Sarah Johnson</option>
                                            <option value="m-3">Dr.Emily Carter</option>
                                            <option value="m-4">Dr.David Lee</option>
                                            <option value="m-5">Dr.Anna Kim </option>
                                            <option value="m-6">Dr.John Smith </option>
                                            <option value="m-7">Dr.Lisa White  </option>
                                            <option value="m-8">Dr.Patrica Brown  </option>
                                            <option value="m-9">Dr.Rachel Green</option>
                                            <option value="m-10">Dr.Michael Smith </option>
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
                                            <option value="m-5">Psychiatrist</option>
                                            <option value="m-6">Neurosurgeon</option>
                                            <option value="m-7">Oncologist</option>
                                            <option value="m-8">Pulmonologist</option>
                                            <option value="m-9">Urologist</option>
                                            <option value="m-10">Dermatologist</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <label class="form-label">Payment Method</label>
                                            <a href="javascript:void(0);" class="link-primary mb-1">Reset</a>
                                        </div>
                                        <select class="select2" multiple="multiple">
                                            <option value="m-1" selected>PayPal</option>
                                            <option value="m-2">Options Enhanced</option>
                                            <option value="m-3">Cheque</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label mb-1 text-dark fs-14 fw-medium">Date</label>
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
                                            <option value="m-1" selected>Completed</option>
                                            <option value="m-2">Pending</option>
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
                        </ul>
                    </div>
                </div>
            </div>
            <!--  End Filter -->

            <!--  Start Table -->
            <div class="table-responsive">
                <table class="table table-nowrap datatable">
                    <thead class="thead-light">
                        <tr>
                            <th class="no-sort">
                                Transaction ID
                            </th>
                            <th>Patient</th>
                            <th>Description</th>
                            <th>Paid  Date</th>
                            <th>Payment Method</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td> <a href="">#TNX0025</a></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/users/user-01.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="text-dark fw-semibold">James Adair  </a>
                                </div>
                            </td>
                            <td class="text-dark"> General Consultation </td>
                            <td class="text-dark"> 30 Apr 2025</td>
                            <td class="text-dark"> PayPal</td>
                            <td class="text-dark"> $800</td>
                            <td> <span class="badge badge-soft-success rounded text-success fw-medium border border-success">Completed</span> </td>
                        </tr>

                        <tr>
                            <td> <a href="">#TNX0024</a></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/users/user-02.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="text-dark fw-semibold">Emily Johnson </a>
                                </div>
                            </td>
                            <td class="text-dark">Dental Cleaning</td>
                            <td class="text-dark"> 15 Apr 2025</td>
                            <td class="text-dark"> Debit Card </td>
                            <td class="text-dark"> $930</td>
                            <td> <span class="badge badge-soft-info rounded text-info fw-medium border border-info">Pending</span> </td>
                        </tr>

                        <tr>
                            <td> <a href="">#TNX0023</a></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/users/user-03.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="text-dark fw-semibold"> Robert Mitchell </a>
                                </div>
                            </td>
                            <td class="text-dark"> Eye Checkup </td>
                            <td class="text-dark"> 02 Apr 2025 </td>
                            <td class="text-dark"> Cheque </td>
                            <td class="text-dark"> $850</td>
                            <td> <span class="badge badge-soft-success rounded text-success fw-medium border border-success">Completed</span> </td>
                        </tr>

                        <tr>
                            <td> <a href="">#TNX0022</a></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/users/user-04.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="text-dark fw-semibold"> Sophia Miller  </a>
                                </div>
                            </td>
                            <td class="text-dark"> X-Ray </td>
                            <td class="text-dark"> 27 Mar 2025 </td>
                            <td class="text-dark"> Debit Card</td>
                            <td class="text-dark"> $80</td>
                            
                            <td> <span class="badge badge-soft-success rounded text-success fw-medium border border-success">Completed</span> </td>
                        </tr>

                        <tr>
                            <td> <a href="">#TNX0021</a></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/users/user-05.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="text-dark fw-semibold">Daniel Anderson  </a>
                                </div>
                            </td>
                            <td class="text-dark">
                                Physiotherapy Session
                            </td>
                            <td class="text-dark"> 12 Mar 2025</td>
                            <td class="text-dark"> PayPal</td>
                            <td class="text-dark"> $650</td>
                            <td> <span class="badge badge-soft-success rounded text-success fw-medium border border-success">Completed</span> </td>
                        </tr>

                        <tr>
                            <td> <a href="">#TNX0020</a></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/users/user-06.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="text-dark fw-semibold">Olivia Davis  </a>
                                </div>
                            </td>
                            <td class="text-dark">
                                Cardiac Screening
                            </td>
                            <td class="text-dark"> 05 Mar 2025 </td>
                            <td class="text-dark"> Cheque </td>
                            <td class="text-dark"> $430 </td>
                            <td> <span class="badge badge-soft-success rounded text-success fw-medium border border-success">Completed</span> </td>
                        </tr>

                        <tr>
                            <td> <a href="">#TNX0019</a></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/users/user-07.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="text-dark fw-semibold">Michael Thompson  </a>
                                </div>
                            </td>
                            <td class="text-dark">
                                Skin Allergy Test
                            </td>
                            <td class="text-dark"> 24 Feb 2025 </td>
                            <td class="text-dark"> Debit Card </td>
                            <td class="text-dark"> $300</td>
                            
                            <td> <span class="badge badge-soft-info rounded text-info fw-medium border border-info">Pending</span> </td>
                        </tr>

                        <tr>
                            <td> <a href="">#TNX0018</a></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/users/user-08.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="text-dark fw-semibold">Isabella Wilson </a>
                                </div>
                            </td>
                            <td class="text-dark">
                                Blood Test
                            </td>
                            <td class="text-dark"> 16 Feb 2025 </td>
                            <td class="text-dark"> Cheque </td>
                            <td class="text-dark"> $450</td>
                            <td> <span class="badge badge-soft-success rounded text-success fw-medium border border-success">Completed</span> </td>
                        </tr>

                        <tr>
                            <td> <a href="">#TNX0017</a></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/users/user-09.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="text-dark fw-semibold"> Michael Trade</a>
                                </div>
                            </td>
                            <td class="text-dark">
                                ENT Consultation
                            </td>
                            <td class="text-dark"> 01 Feb 2025 </td>
                            <td class="text-dark"> Debit Card </td>
                            <td class="fw-semibold text-dark"> $570</td>
                            <td> <span class="badge badge-soft-success rounded text-success fw-medium border border-success"> completed</span> </td>
                        </tr>
                        <tr>
                            <td> <a href="">#TNX0017</a></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/users/user-10.jpg')}}" alt="product" class="rounded-circle">
                                    </a>
                                    <a href="javascript:void(0);" class="text-dark fw-semibold">Ava Robinson </a>
                                </div>
                            </td>
                            <td class="text-dark">
                                Nutrition Counseling
                            </td>
                            <td class="text-dark"> 25 Jan 2025 </td>
                            <td class="text-dark"> PayPal</td>
                            <td class="text-dark"> $800</td>
                            <td> <span class="badge badge-soft-success rounded text-success fw-medium border border-success">Completed</span> </td>
                        </tr>

                    </tbody>
                </table>
            </div>
            <!--  End Table -->

        </div>
        <!-- End Content -->

        @component('components.footer')
        @endcomponent

    </div>

    <!-- ========================
        End Page Content
    ========================= -->

@endsection    