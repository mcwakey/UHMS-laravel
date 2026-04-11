<?php $page = 'appointment-report'; ?>
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
                    <h4 class="fw-bold mb-0">Appointment Report</h4>
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
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="avatar avatar-sm bg-soft-primary border border-primary text-primary rounded-2 flex-shrink-0"><i class="ti ti-calendar-time fs-14"></i></span>
                                <span class="badge fw-medium bg-soft-success text-success flex-shrink-0 ms-2">+10%<i class="ti ti-arrow-up-right ms-1"></i></span>
                            </div>  
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="mb-1 text-truncate">Total Appointments</p>
                                    <h6 class="mb-0 fw-bold">850</h6>
                                </div>
                                <div id="s-col-11" class="chart-set"></div>
                            </div>  
                        </div>
                    </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-xl-3 col-md-6 d-flex">                       
                    <div class="card shadow-sm flex-fill w-100 z-0">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="avatar avatar-sm bg-soft-success border border-success text-success rounded-2 flex-shrink-0"><i class="ti ti-calendar-up fs-14"></i></span>
                                <span class="badge fw-medium bg-soft-success text-success flex-shrink-0 ms-2">+11.5%<i class="ti ti-arrow-up-right ms-1"></i></span>
                            </div>  
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="mb-1 text-truncate">Completed</p>
                                    <h6 class="mb-0 fw-bold">720</h6>
                                </div>
                                <div id="s-col-12" class="chart-set"></div>
                            </div>  
                        </div>
                    </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-xl-3 col-md-6 d-flex">                       
                    <div class="card shadow-sm flex-fill w-100 z-0">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="avatar avatar-sm bg-soft-warning border border-warning text-warning rounded-2 flex-shrink-0"><i class="ti ti-calendar-down fs-14"></i></span>
                                <span class="badge fw-medium bg-soft-success text-success flex-shrink-0 ms-2">+8.43%<i class="ti ti-arrow-up-right ms-1"></i></span>
                            </div>  
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="mb-1 text-truncate">Cancelled</p>
                                    <h6 class="mb-0 fw-bold">65</h6>
                                </div>
                                <div id="s-col-13" class="chart-set"></div>
                            </div>  
                        </div>
                    </div>
                </div>
                <!-- col end -->

                <!-- col start -->
                <div class="col-xl-3 col-md-6 d-flex">                       
                    <div class="card shadow-sm flex-fill w-100 z-0">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="avatar avatar-sm bg-soft-danger border border-danger text-danger rounded-2 flex-shrink-0"><i class="ti ti-calendar-repeat fs-14"></i></span>
                                <span class="badge fw-medium bg-soft-success text-success flex-shrink-0 ms-2">+6.58%<i class="ti ti-arrow-up-right ms-1"></i></span>
                            </div>  
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="mb-1 text-truncate">Rescheduled</p>
                                    <h6 class="mb-0 fw-bold">40</h6>
                                </div>
                                <div id="s-col-14" class="chart-set"></div>
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
                        <div class="col-lg-4">
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
                        <div class="col-lg-4">
                            <div class="mb-0">
                                <label class="form-label">Patient</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>Alberto Ripley</option>
                                    <option>Susan Babin</option>
                                    <option>Martin Lisa</option>
                                    <option>Stella Mary</option>
                                    <option>Carol Lam</option>
                                    <option>Jesus Adams</option>
                                    <option>Ezra Belcher</option>
                                </select>
                            </div>
                        </div><!-- end col -->
                        <div class="col-lg-4">
                            <div class="mb-0">
                                <label class="form-label">Location</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>California</option>
                                    <option>Options Enhanced</option>
                                    <option>New York</option>
                                    <option>Florida</option>
                                    <option>Illinois</option>
                                    <option>Georgia</option>
                                    <option>Washington</option>
                                    <option>Arizona</option>
                                </select>
                            </div>
                        </div><!-- end col -->
                        <div class="col-lg-4">
                            <div class="mb-0">
                                <label class="form-label">Practioner</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>Dr. Mick Thompson</option>
                                    <option>Dr. Sarah Johnson</option>
                                    <option>Dr. Emily Carter</option>
                                    <option>Dr. David Lee</option>
                                    <option>Dr. Anna Kim</option>
                                    <option>Dr. John Smith</option>
                                    <option>Dr. Lisa White</option>
                                    <option>Dr. Patrica Brown</option>
                                </select>
                            </div>
                        </div><!-- end col -->
                        <div class="col-lg-4">
                            <div class="mb-0">
                                <label class="form-label">Designation</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>Cardiologist</option>
                                    <option>Orthopedic Surgeon</option>
                                    <option>Pediatrician</option>
                                    <option>Gynecologist</option>
                                    <option>Psychiatrist</option>
                                    <option>Neurosurgeon</option>
                                    <option>Oncologist</option>
                                </select>
                            </div>
                        </div><!-- end col -->
                        <div class="col-lg-4">
                            <div class="mb-0">
                                <label class="form-label">Status</label>
                                <input class="input-tags form-control" id="inputBox" type="text" data-role="tagsinput"  name="specialist" value="Pending">
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
                            <th>Patient</th>
                            <th>Date & Time</th>
                            <th>Invoice ID</th>
                            <th>Practioner</th>
                            <th>Location</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Alberto Ripley</td>
                            <td>May 27 2025 - 09:30 AM</td>
                            <td>#INV0020</td>
                            <td><p class="text-dark fw-medium">Dr. Mick Thompson</p></td>
                            <td>California</td>
                            <td><span class="badge badge-soft-primary border border-primary px-2 py-1 fs-13 fw-medium">Checked Out</span></td>
                        </tr>
                        <tr>
                            <td>Susan Babin</td>
                            <td>May 27 2025 - 10:00 AM</td>
                            <td>#INV0019</td>
                            <td><p class="text-dark fw-medium">Dr. Sarah Johnson</p></td>
                            <td>Texas</td>
                            <td><span class="badge badge-soft-warning border border-warning px-2 py-1 fs-13 fw-medium">Checked in</span></td>
                        </tr>
                        <tr>
                            <td>Carol Lam</td>
                            <td>May 28 2025 - 10:00 AM</td>
                            <td>#INV0018</td>
                            <td><p class="text-dark fw-medium">Dr. Emily Carter</p></td>
                            <td>New York</td>
                            <td><span class="badge badge-soft-danger border border-danger px-2 py-1 fs-13 fw-medium">Cancelled</span></td>
                        </tr>
                        <tr>
                            <td>Marsha Noland</td>
                            <td>May 28 2025 - 10:30 AM</td>
                            <td>#INV0017</td>
                            <td><p class="text-dark fw-medium">Dr. David Lee</p></td>
                            <td>Florida</td>
                            <td><span class="badge badge-soft-info border border-info px-2 py-1 fs-13 fw-medium">Schedule</span></td>
                        </tr>
                        <tr>
                            <td>Irma Armstrong</td>
                            <td>May 28 2025 - 11: 15 AM</td>
                            <td>#INV0016</td>
                            <td><p class="text-dark fw-medium">Dr. Anna Kim</p></td>
                            <td>Illinois</td>
                            <td><span class="badge badge-soft-success border border-success px-2 py-1 fs-13 fw-medium">Confirmed</span></td>
                        </tr>
                        <tr>
                            <td>Jesus Adams</td>
                            <td>May 29 2025 - 11:30 AM</td>
                            <td>#INV0015</td>
                            <td><p class="text-dark fw-medium">Dr. John Smith</p></td>
                            <td>Pennsylvania</td>
                            <td><span class="badge badge-soft-success border border-success px-2 py-1 fs-13 fw-medium">Confirmed</span></td>
                        </tr>
                        <tr>
                            <td>Ezra Belcher</td>
                            <td>May 30 2025 - 09:30 AM</td>
                            <td>#INV0014</td>
                            <td><p class="text-dark fw-medium">Dr. Lisa White</p></td>
                            <td>Ohio</td>
                            <td><span class="badge badge-soft-danger border border-danger px-2 py-1 fs-13 fw-medium">Cancelled</span></td>
                        </tr>
                        <tr>
                            <td>Glen Lentz</td>
                            <td>May 30 2025 - 10:00 AM</td>
                            <td>#INV0013</td>
                            <td><p class="text-dark fw-medium">Dr. Patricia Brown</p></td>
                            <td>Georgia</td>
                            <td><span class="badge badge-soft-success border border-success px-2 py-1 fs-13 fw-medium">Confirmed</span></td>
                        </tr>
                        <tr>
                            <td>Bernard Griffith</td>
                            <td>May 30 2025 - 11:00 AM</td>
                            <td>#INV0012</td>
                            <td><p class="text-dark fw-medium">Dr. Rachel Green</p></td>
                            <td>North Carolina</td>
                            <td><span class="badge badge-soft-primary border border-primary px-2 py-1 fs-13 fw-medium">Checked Out</span></td>
                        </tr>
                        <tr>
                            <td>John Elsass</td>
                            <td>May 30 2025 - 11:00 AM</td>
                            <td>#INV0011</td>
                            <td><p class="text-dark fw-medium">Dr. Michael Smith</p></td>
                            <td>Michigan</td>
                            <td><span class="badge badge-soft-info border border-info px-2 py-1 fs-13 fw-medium">Schedule</span></td>
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