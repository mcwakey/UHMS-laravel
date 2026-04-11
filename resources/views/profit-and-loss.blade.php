<?php $page = 'profit-and-loss'; ?>
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
                    <h4 class="fw-bold mb-0">Profit & Loss Report</h4>
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
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div>
                                        <p class="mb-1 text-truncate">Total Income</p>
                                        <h6 class="mb-2 fw-bold">$125,150</h6>
                                    </div>
                                    <span class="avatar avatar-lg bg-primary text-white rounded-circle border border-primary flex-shrink-0"><i class="ti ti-arrow-up-right-circle fs-24"></i></span>
                                </div>
                                <div class="p-2 rounded bg-light text-center">
                                <p class="mb-0 fs-13"><span class="text-success"><i class="ti ti-arrow-up-right me-1"></i>5.62%</span> from last month</p>
                                </div>
                            </div>
                        </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-xl-3 col-md-6 d-flex">
                        <div class="card shadow-sm flex-fill w-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div>
                                        <p class="mb-1 text-truncate">Total Expenses</p>
                                        <h6 class="mb-2 fw-bold">$91,800</h6>
                                    </div>
                                    <span class="avatar avatar-lg bg-success text-white rounded-circle border border-success flex-shrink-0"><i class="ti ti-arrow-down-right-circle fs-24"></i></span>
                                </div>
                                <div class="p-2 rounded bg-light text-center">
                                <p class="mb-0 fs-13"><span class="text-success"><i class="ti ti-arrow-up-right me-1"></i>11.4%</span> from last month</p>
                                </div>
                            </div>
                        </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-xl-3 col-md-6 d-flex">
                        <div class="card shadow-sm flex-fill w-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div>
                                        <p class="mb-1 text-truncate">Net Profit</p>
                                        <h6 class="mb-2 fw-bold">$91,800</h6>
                                    </div>
                                    <span class="avatar avatar-lg bg-warning text-white rounded-circle border border-warning flex-shrink-0"><i class="ti ti-currency-dollar fs-24"></i></span>
                                </div>
                                <div class="p-2 rounded bg-light text-center">
                                <p class="mb-0 fs-13"><span class="text-success"><i class="ti ti-arrow-up-right me-1"></i>8.52%</span> from last month</p>
                                </div>
                            </div>
                        </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-xl-3 col-md-6 d-flex">
                        <div class="card shadow-sm flex-fill w-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div>
                                        <p class="mb-1 text-truncate">Profit Margin (%)</p>
                                        <h6 class="mb-2 fw-bold">26.6%</h6>
                                    </div>
                                    <span class="avatar avatar-lg bg-danger text-white rounded-circle border border-danger flex-shrink-0"><i class="ti ti-cash fs-24"></i></span>
                                </div>
                                <div class="p-2 rounded bg-light text-center">
                                <p class="mb-0 fs-13"><span class="text-danger"><i class="ti ti-arrow-down-right me-1"></i>7.45%</span> from last month</p>
                                </div>
                            </div>
                        </div>
                </div>
                <!-- col end -->

                </div>     
                <!-- row end -->

            <div class="table-responsive">
                <table class="table table-nowrap">
                    <thead class="thead-light">
                        <tr>
                            <th class="border-end"></th>
                            <th>Jan 2025</th>
                            <th>Feb 2025</th>
                            <th>Mar 2025</th>
                            <th>Apr 2025</th>
                            <th>May 2025</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="border-end">Income</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td><p class="text-dark fw-medium"></p></td>
                        </tr>
                        <tr>
                            <td class="border-end">Consultation Fees</td>
                            <td>$50,125</td>
                            <td>$25,750</td>
                            <td>$25,750</td>
                            <td>$25,750</td>
                            <td>$25,750</td>
                            <td><p class="text-dark fw-medium">$25,750</p></td>
                        </tr>
                        <tr>
                            <td class="border-end">Lab Revenue</td>
                            <td>$75,900</td>
                            <td>$50,125</td>
                            <td>$50,125</td>
                            <td>$50,125</td>
                            <td>$50,125</td>
                            <td><p class="text-dark fw-medium">$50,125</p></td>
                        </tr>
                        <tr>
                            <td class="border-end">Pharmacy Sales</td>
                            <td>$151,775</td>
                            <td>$75,900</td>
                            <td>$75,900</td>
                            <td>$75,900</td>
                            <td>$75,900</td>
                            <td><p class="text-dark fw-medium">$75,900</p></td>
                        </tr>
                        <tr>
                            <td class="border-end"><p class="text-dark fw-medium">Gross Profit</p></td>
                            <td><p class="text-dark fw-medium">$25,750</p></td>
                            <td><p class="text-dark fw-medium">$151,775</p></td>
                            <td><p class="text-dark fw-medium">$151,775</p></td>
                            <td><p class="text-dark fw-medium">$151,775</p></td>
                            <td><p class="text-dark fw-medium">$151,775</p></td>
                            <td><p class="text-dark fw-medium">$151,775</p></td>
                        </tr>
                        <tr>
                            <td class="border-end"><p class="text-dark fw-medium">Expense</p></td>
                            <td><p class="text-dark fw-medium"></p></td>
                            <td><p class="text-dark fw-medium"></p></td>
                            <td><p class="text-dark fw-medium"></p></td>
                            <td><p class="text-dark fw-medium"></p></td>
                            <td><p class="text-dark fw-medium"></p></td>
                            <td><p class="text-dark fw-medium"></p></td>
                        </tr>
                        <tr>
                            <td class="border-end">Doctor Payouts</td>
                            <td>$50,125</td>
                            <td>$25,750</td>
                            <td>$25,750</td>
                            <td>$25,750</td>
                            <td>$25,750</td>
                            <td><p class="text-dark fw-medium">$25,750</p></td>
                        </tr>
                        <tr>
                            <td class="border-end">Staff Salaries</td>
                            <td>$75,900</td>
                            <td>$50,125</td>
                            <td>$50,125</td>
                            <td>$50,125</td>
                            <td>$50,125</td>
                            <td><p class="text-dark fw-medium">$50,125</p></td>
                        </tr>
                        <tr>
                            <td class="border-end">Rent & Utilities</td>
                            <td>$15,000</td>
                            <td>$75,900</td>
                            <td>$75,900</td>
                            <td>$75,900</td>
                            <td>$75,900</td>
                            <td><p class="text-dark fw-medium">$87,650</p></td>
                        </tr>
                        <tr>
                            <td class="border-end">Medical Supplies</td>
                            <td>$18,200</td>
                            <td>$15,000</td>
                            <td>$15,000</td>
                            <td>$15,000</td>
                            <td>$15,000</td>
                            <td><p class="text-dark fw-medium">$15,000</p></td>
                        </tr>
                        <tr>
                            <td class="border-end">Lab Consumables</td>
                            <td>$75,900</td>
                            <td>$18,200</td>
                            <td>$18,200</td>
                            <td>$18,200</td>
                            <td>$18,200</td>
                            <td><p class="text-dark fw-medium">$18,200</p></td>
                        </tr>
                        <tr>
                            <td class="border-end">Maintenance & Repair</td>
                            <td>$99,999</td>
                            <td>$20,800</td>
                            <td>$20,800</td>
                            <td>$20,800</td>
                            <td>$20,800</td>
                            <td><p class="text-dark fw-medium">$20,800</p></td>
                        </tr>
                        <tr>
                            <td class="border-end"><p class="text-dark fw-medium">Total Expense</p></td>
                            <td><p class="text-dark fw-medium">$2,69,276</p></td>
                            <td><p class="text-dark fw-medium"></p>$99,999</td>
                            <td><p class="text-dark fw-medium"></p>$99,999</td>
                            <td><p class="text-dark fw-medium">$99,999</p></td>
                            <td><p class="text-dark fw-medium">$99,999</p></td>
                            <td><p class="text-dark fw-medium">$151,775</p></td>
                        </tr>
                        <tr>
                            <td class="border-end"><p class="text-dark fw-medium">Net Income</p></td>
                            <td><p class="text-dark fw-medium">$2,69,276</p></td>
                            <td><p class="text-dark fw-medium">$2,75,638</p></td>
                            <td><p class="text-dark fw-medium">$2,51,629</p></td>
                            <td><p class="text-dark fw-medium">$7,96,543</p></td>
                            <td><p class="text-dark fw-medium">$7,96,543</p></td>
                            <td><p class="text-dark fw-medium">$2,75,638</p></td>
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