<?php $page = 'expense-report'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- ========================
        Start Page Content
    ========================= -->
        
    <div class="page-wrapper">

        <!-- Start Content -->
        <div class="content">

            <!-- Start Page Header -->
            <div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
                <div class="flex-grow-1">
                    <h4 class="fw-bold mb-0">Expenses Report</h4>
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

            <!-- row start -->
            <div class="row">

                <!-- col start -->
                <div class="col-xl-3 col-md-6 d-flex">                       
                    <div class="card shadow-sm flex-fill w-100 z-0">
                        <img src="{{URL::asset('./build/img/bg/widget-bg-05.svg')}}" alt="img" class="position-absolute start-0 top-0 z-n1">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-end">
                                <div>
                                    <p class="mb-1 text-truncate">Total Expenses</p>
                                    <h6 class="mb-2 fw-bold">$13,500</h6>
                                    <p class="mb-0 fs-13 text-truncate"><span class="text-success"><i class="ti ti-arrow-up-right me-1"></i>5.62%</span> from last month</p>
                                </div>
                                <span class="avatar avatar-md widget-icon bg-soft-primary border border-primary text-primary rounded-2 flex-shrink-0"><i class="ti ti-currency-dollar fs-14"></i></span>
                            </div>                                
                        </div>
                    </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-xl-3 col-md-6 d-flex">                       
                    <div class="card shadow-sm flex-fill w-100 z-0">
                        <img src="{{URL::asset('./build/img/bg/widget-bg-06.svg')}}" alt="img" class="position-absolute start-0 top-0 z-n1">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-end">
                                <div>
                                    <p class="mb-1 text-truncate">Doctor Payouts</p>
                                    <h6 class="mb-2 fw-bold">$4,500</h6>
                                    <p class="mb-0 fs-13 text-truncate"><span class="text-success"><i class="ti ti-arrow-up-right me-1"></i>11.4%</span> from last month</p>
                                </div>
                                <span class="avatar avatar-md widget-icon bg-soft-success border border-success text-success rounded-2 flex-shrink-0"><i class="ti ti-stethoscope fs-14"></i></span>
                            </div>                                
                        </div>
                    </div>
                </div>
                <!-- col end -->

                    <!-- col start -->
                    <div class="col-xl-3 col-md-6 d-flex">                       
                        <div class="card shadow-sm flex-fill w-100 z-0">
                            <img src="{{URL::asset('./build/img/bg/widget-bg-07.svg')}}" alt="img" class="position-absolute start-0 top-0 z-n1">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-end">
                                    <div>
                                        <p class="mb-1 text-truncate">Staff Salaries</p>
                                        <h6 class="mb-2 fw-bold">$3,200</h6>
                                        <p class="mb-0 fs-13 text-truncate"><span class="text-success"><i class="ti ti-arrow-up-right me-1"></i>8.52%</span> from last month</p>
                                    </div>
                                    <span class="avatar avatar-md widget-icon bg-soft-warning border border-warning text-warning rounded-2 flex-shrink-0"><i class="ti ti-pill fs-14"></i></span>
                                </div>                                
                            </div>
                        </div>
                    </div>
                    <!-- col end -->

                    <!-- col start -->
                    <div class="col-xl-3 col-md-6 d-flex">                       
                        <div class="card shadow-sm flex-fill w-100 z-0">
                            <img src="{{URL::asset('./build/img/bg/widget-bg-08.svg')}}" alt="img" class="position-absolute start-0 top-0 z-n1">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-end">
                                    <div>
                                        <p class="mb-1 text-truncate">Utilities & Rent</p>
                                        <h6 class="mb-2 fw-bold">$2,000</h6>
                                        <p class="mb-0 fs-13 text-truncate"><span class="text-danger"><i class="ti ti-arrow-down-right me-1"></i>7.45%</span> from last month</p>
                                    </div>
                                    <span class="avatar avatar-md widget-icon bg-soft-danger border border-danger text-danger rounded-2 flex-shrink-0"><i class="ti ti-flask fs-14"></i></span>
                                </div>                                
                            </div>
                        </div>
                    </div>
                    <!-- col end -->

                </div>
                <!-- row end -->

                <div class="card">
                <div class="card-body">

                    <!-- start row -->
                        <div class="row row-gap-2">
                        <div class="col-md-6">
                            <div class="mb-0">
                                <label class="form-label">Date</label>
                                <div class="input-group position-relative">
                                    <input type="text" class="form-control date-range bookingrange rounded-end bg-white">
                                    <span class="input-icon-addon fs-14 text-dark">
                                        <i class="ti ti-calendar"></i>
                                    </span>
                                </div>
                            </div>
                        </div><!-- end col -->
                        <div class="col-md-6">
                            <div class="mb-0">
                                <label class="form-label">Category</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>Medical Supplies</option>
                                    <option>Laboratory</option>
                                    <option>Medical Supplies</option>
                                    <option>Cleaning Services</option>
                                </select>
                            </div>
                        </div><!-- end col -->
                        <div class="col-md-6">
                            <div class="mb-0">
                                <label class="form-label">Purchased By</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>James Allaire</option>
                                    <option>Esther Schmidt</option>
                                    <option>Judi Lenahan</option>
                                    <option>Robert Reid</option>
                                </select>
                            </div>
                        </div><!-- end col -->
                        <div class="col-md-6">
                            <div class="mb-0">
                                <label class="form-label">Status</label>
                                <input class="input-tags form-control" id="inputBox" type="text" data-role="tagsinput"  name="specialist" value="Received, Pending">
                            </div>
                        </div><!-- end col -->
                        <div class="col-md-12">
                            <div class="d-flex align-items-center justify-content-end">
                                <a href="javascript:void(0);" class="btn btn-dark"><i class="ti ti-player-play me-1"></i>Run Report</a>
                            </div>
                        </div><!-- end col -->
                        </div>
                    <!-- end row -->

                </div><!-- end card body -->
                </div><!-- end card -->

            <div class="table-responsive">
                <table class="table table-nowrap">
                    <thead class="thead-light">
                        <tr>
                            <th>Expense</th>
                            <th>Category</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Purchased By</th>
                            <th>Status</th>
                            <th>Payment Method</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Gloves & Masks</td>
                            <td>Medical Supplies</td>
                            <td><p class="text-dark fw-medium">$800</p></td>
                            <td>22 Apr 2025</td>
                            <td><p class="text-dark fw-medium">James Carter</p></td>
                            <td><span class="badge badge-soft-success border border-success px-2 py-1 fs-13 fw-medium">Approved</span></td>
                            <td>PayPal</td>
                        </tr>
                        <tr>
                            <td>Microscope Parts</td>
                            <td>Laboratory</td>
                            <td><p class="text-dark fw-medium">$930</p></td>
                            <td>12 Apr 2025</td>
                            <td><p class="text-dark fw-medium">Emily Johnson</p></td>
                            <td><span class="badge badge-soft-warning border border-warning px-2 py-1 fs-13 fw-medium">Pending</span></td>
                            <td>Debit Card</td>
                        </tr>
                        <tr>
                            <td>Thermometers</td>
                            <td>Medical Supplies</td>
                            <td><p class="text-dark fw-medium">$850</p></td>
                            <td>01 Apr 2025</td>
                            <td><p class="text-dark fw-medium">Robert Mitchell</p></td>
                            <td><span class="badge badge-soft-success border border-success px-2 py-1 fs-13 fw-medium">Approved</span></td>
                            <td>Cheque</td>
                        </tr>
                        <tr>
                            <td>Disinfectant Supplies</td>
                            <td>Cleaning Services</td>
                            <td><p class="text-dark fw-medium">$700</p></td>
                            <td>05 Mar 2025</td>
                            <td><p class="text-dark fw-medium">Sophia Miller</p></td>
                            <td><span class="badge badge-soft-danger border border-danger px-2 py-1 fs-13 fw-medium">Rejected</span></td>
                            <td>Cheque</td>
                        </tr>
                        <tr>
                            <td>IV Sets</td>
                            <td>Medical Supplies</td>
                            <td><p class="text-dark fw-medium">$650</p></td>
                            <td>20 Mar 2025</td>
                            <td><p class="text-dark fw-medium">Daniel Anderson</p></td>
                            <td><span class="badge badge-soft-primary border border-primary px-2 py-1 fs-13 fw-medium">New</span></td>
                            <td>PayPal</td>
                        </tr>
                        <tr>
                            <td>Reagent Refill</td>
                            <td>Laboratory</td>
                            <td><p class="text-dark fw-medium">$430</p></td>
                            <td>01 Mar 2025</td>
                            <td><p class="text-dark fw-medium">Olivia Davis</p></td>
                            <td><span class="badge badge-soft-danger border border-danger px-2 py-1 fs-13 fw-medium">Rejected</span></td>
                            <td>Debit Card</td>
                        </tr>
                        <tr>
                            <td>Syringes & Gauze</td>
                            <td>Medical Supplies</td>
                            <td><p class="text-dark fw-medium">$300</p></td>
                            <td>15 Feb 2025</td>
                            <td><p class="text-dark fw-medium">Michael Thompson</p></td>
                            <td><span class="badge badge-soft-primary border border-primary px-2 py-1 fs-13 fw-medium">New</span></td>
                            <td>Cheque</td>
                        </tr>
                        <tr>
                            <td>Blood Collection Tubes</td>
                            <td>Laboratory</td>
                            <td><p class="text-dark fw-medium">$450</p></td>
                            <td>09 Feb 2025</td>
                            <td><p class="text-dark fw-medium">Isabella Wilson</p></td>
                            <td><span class="badge badge-soft-success border border-success px-2 py-1 fs-13 fw-medium">Approved</span></td>
                            <td>PayPal</td>
                        </tr>
                        <tr>
                            <td>Bandages & Tapes</td>
                            <td>Medical Supplies</td>
                            <td><p class="text-dark fw-medium">$570</p></td>
                            <td>01 Feb 2025</td>
                            <td><p class="text-dark fw-medium">Michael Trade</p></td>
                            <td><span class="badge badge-soft-primary border border-primary px-2 py-1 fs-13 fw-medium">New</span></td>
                            <td>Debit Card</td>
                        </tr>
                        <tr>
                            <td>Tissue Slides</td>
                            <td>Laboratory</td>
                            <td><p class="text-dark fw-medium">$800</p></td>
                            <td>12 Jan 2025</td>
                            <td><p class="text-dark fw-medium">Ava Robinson</p></td>
                            <td><span class="badge badge-soft-success border border-success px-2 py-1 fs-13 fw-medium">Approved</span></td>
                            <td>Cheque</td>
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