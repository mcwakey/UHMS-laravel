<?php $page = 'income-report'; ?>
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
                    <h4 class="fw-bold mb-0">Income Report</h4>
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
                        <div class="card shadow-sm flex-fill w-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <div>
                                        <p class="mb-1 text-truncate">Total Clinic Income</p>
                                        <h6 class="mb-0 fw-bold">$18,750</h6>
                                    </div>
                                    <span class="avatar avatar-lg bg-soft-primary text-primary rounded-circle flex-shrink-0"><i class="ti ti-currency-dollar fs-24"></i></span>
                                </div>
                                <div class="progress mb-2 progress-sm">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: 75%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <p class="mb-0 fs-13"><span class="text-success"><i class="ti ti-arrow-up-right me-1"></i>5.62%</span> from last month</p>
                            </div>
                        </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-xl-3 col-md-6 d-flex">
                        <div class="card shadow-sm flex-fill w-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <div>
                                        <p class="mb-1 text-truncate">Doctor Fees Collected</p>
                                        <h6 class="mb-0 fw-bold">$7,000</h6>
                                    </div>
                                    <span class="avatar avatar-lg bg-soft-success text-success rounded-circle flex-shrink-0"><i class="ti ti-stethoscope fs-24"></i></span>
                                </div>
                                <div class="progress mb-2 progress-sm">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: 75%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <p class="mb-0 fs-13"><span class="text-success"><i class="ti ti-arrow-up-right me-1"></i>11.4%</span> from last month</p>
                            </div>
                        </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-xl-3 col-md-6 d-flex">
                        <div class="card shadow-sm flex-fill w-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <div>
                                        <p class="mb-1 text-truncate">Medicine Sales</p>
                                        <h6 class="mb-0 fw-bold">$6,250</h6>
                                    </div>
                                    <span class="avatar avatar-lg bg-soft-warning text-warning rounded-circle flex-shrink-0"><i class="ti ti-pill fs-24"></i></span>
                                </div>
                                <div class="progress mb-2 progress-sm">
                                    <div class="progress-bar bg-warning" role="progressbar" style="width: 75%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <p class="mb-0 fs-13"><span class="text-success"><i class="ti ti-arrow-up-right me-1"></i>8.52%</span> from last month</p>
                            </div>
                        </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-xl-3 col-md-6 d-flex">
                        <div class="card shadow-sm flex-fill w-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <div>
                                        <p class="mb-1 text-truncate">Lab Revenue</p>
                                        <h6 class="mb-0 fw-bold">$5,500</h6>
                                    </div>
                                    <span class="avatar avatar-lg bg-soft-danger text-danger rounded-circle flex-shrink-0"><i class="ti ti-flask fs-24"></i></span>
                                </div>
                                <div class="progress mb-2 progress-sm">
                                    <div class="progress-bar bg-danger" role="progressbar" style="width: 25%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <p class="mb-0 fs-13"><span class="text-danger"><i class="ti ti-arrow-down-right me-1"></i>7.45%</span> from last month</p>
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
                                <label class="form-label">Received From</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>Alberto Ripley</option>
                                    <option>Susan Babin</option>
                                    <option>Martin Lisa</option>
                                    <option>Stella Mary</option>
                                    <option>Carol Lam</option>
                                </select>
                            </div>
                        </div><!-- end col -->
                        <div class="col-md-6">
                            <div class="mb-0">
                                <label class="form-label">Payment Method</label>
                                <input class="input-tags form-control" id="inputBox" type="text" data-role="tagsinput"  name="specialist" value="PayPal, Cheque">
                            </div>
                        </div><!-- end col -->
                        <div class="col-md-6">
                            <div class="mb-0">
                                <label class="form-label">Status</label>
                                <input class="input-tags form-control" id="inputBox3" type="text" data-role="tagsinput"  name="specialist" value="Received, Pending">
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
                            <th>Income</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Received From</th>
                            <th>Payment Method</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Nurse</td>
                            <td><p class="text-dark fw-medium">$800</p></td>
                            <td>22 Apr 2025</td>
                            <td><p class="text-dark fw-medium">James Carter</p></td>
                            <td>PayPal</td>
                            <td><span class="badge badge-soft-success border border-success px-2 py-1 fs-13 fw-medium">Received</span></td>
                        </tr>
                        <tr>
                            <td>Pharmacist</td>
                            <td><p class="text-dark fw-medium">$930</p></td>
                            <td>12 Apr 2025</td>
                            <td><p class="text-dark fw-medium">Emily Johnson</p></td>
                            <td>Debit Card</td>
                            <td><span class="badge badge-soft-warning border border-warning px-2 py-1 fs-13 fw-medium">Pending</span></td>
                        </tr>
                        <tr>
                            <td>Receptionist</td>
                            <td><p class="text-dark fw-medium">$850</p></td>
                            <td>01 Apr 2025</td>
                            <td><p class="text-dark fw-medium">Robert Mitchell</p></td>
                            <td>Cheque</td>
                            <td><span class="badge badge-soft-success border border-success px-2 py-1 fs-13 fw-medium">Received</span></td>
                        </tr>
                        <tr>
                            <td>Technician</td>
                            <td><p class="text-dark fw-medium">$700</p></td>
                            <td>05 Mar 2025</td>
                            <td><p class="text-dark fw-medium">Sophia Miller</p></td>
                            <td>Cheque</td>
                            <td><span class="badge badge-soft-success border border-success px-2 py-1 fs-13 fw-medium">Received</span></td>
                        </tr>
                        <tr>
                            <td>Medical Assistant</td>
                            <td><p class="text-dark fw-medium">$650</p></td>
                            <td>20 Mar 2025</td>
                            <td><p class="text-dark fw-medium">Daniel Anderson</p></td>
                            <td>PayPal</td>
                            <td><span class="badge badge-soft-warning border border-warning px-2 py-1 fs-13 fw-medium">Pending</span></td>
                        </tr>
                        <tr>
                            <td>Phlebotomist</td>
                            <td><p class="text-dark fw-medium">$430</p></td>
                            <td>01 Mar 2025</td>
                            <td><p class="text-dark fw-medium">Olivia Davis</p></td>
                            <td>Debit Card</td>
                            <td><span class="badge badge-soft-success border border-success px-2 py-1 fs-13 fw-medium">Received</span></td>
                        </tr>
                        <tr>
                            <td>Dialysis Technician</td>
                            <td><p class="text-dark fw-medium">$300</p></td>
                            <td>15 Feb 2025</td>
                            <td><p class="text-dark fw-medium">Michael Thompson</p></td>
                            <td>Cheque</td>
                            <td><span class="badge badge-soft-success border border-success px-2 py-1 fs-13 fw-medium">Received</span></td>
                        </tr>
                        <tr>
                            <td>Medical Coder</td>
                            <td><p class="text-dark fw-medium">$450</p></td>
                            <td>09 Feb 2025</td>
                            <td><p class="text-dark fw-medium">Isabella Wilson</p></td>
                            <td>PayPal</td>
                            <td><span class="badge badge-soft-warning border border-warning px-2 py-1 fs-13 fw-medium">Pending</span></td>
                        </tr>
                        <tr>
                            <td>Chiropractor</td>
                            <td><p class="text-dark fw-medium">$570</p></td>
                            <td>01 Feb 2025</td>
                            <td><p class="text-dark fw-medium">Michael Trade</p></td>
                            <td>Debit Card</td>
                            <td><span class="badge badge-soft-success border border-success px-2 py-1 fs-13 fw-medium">Received</span></td>
                        </tr>
                        <tr>
                            <td>Acupuncturist</td>
                            <td><p class="text-dark fw-medium">$800</p></td>
                            <td>12 Jan 2025</td>
                            <td><p class="text-dark fw-medium">Ava Robinson</p></td>
                            <td>Cheque</td>
                            <td><span class="badge badge-soft-success border border-success px-2 py-1 fs-13 fw-medium">Received</span></td>
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