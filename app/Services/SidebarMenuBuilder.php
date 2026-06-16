<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class SidebarMenuBuilder
{
    public function __construct(
        protected ModuleService $moduleService,
    ) {}

    public function build(?User $user, string $currentRouteName = '', int $unreadNotifications = 0): array
    {
        if (! $user) {
            return [];
        }

        // Non-admin clinical staff get a focused consultation sidebar
        if ($user->isConsultationUser() && ! $user->isAdminUser()) {
            return $this->finaliseSections(
                $this->consultationSections($unreadNotifications),
                $user,
                $currentRouteName
            );
        }

        $sections = [
            [
                'title' => 'Main Menu',
                'items' => [
                    [
                        'label' => 'Dashboard',
                        'icon' => 'ti ti-layout-dashboard',
                        'route' => 'admin.dashboard',
                        'active_patterns' => ['admin.dashboard'],
                    ],
                    [
                        'label' => 'Other Dashboards',
                        'icon' => 'ti ti-layout-dashboard',
                        'route' => 'admin.my-dashboard',
                        'active_patterns' => [ 'admin.my-dashboard', 'doctor.dashboard', 'staff.dashboard'],
                    ],
                ],
            ],
            [
                'title' => 'Patient Services',
                'items' => [
                    [
                        'label' => 'Patients',
                        'icon' => 'ti ti-user-heart',
                        'route' => 'admin.patients.index',
                        'active_patterns' => ['admin.patients.*'],
                        'permission' => 'patients.view',
                        'module' => 'patients',
                    ],
                    // [
                    //     'label' => 'Folder Merge',
                    //     'icon' => 'ti ti-git-merge',
                    //     'route' => 'admin.patients.merge.index',
                    //     'active_patterns' => ['admin.patients.merge.*'],
                    //     'permission' => 'patients.merge.view',
                    //     'module' => 'patients',
                    // ],
                    [
                        'label' => 'Appointments',
                        'icon' => 'ti ti-calendar-event',
                        'route' => 'admin.appointments.index',
                        'permission' => 'appointments.view',
                        'active_patterns' => ['admin.appointments.*'],
                        'module' => 'appointments',
                        // 'children' => [
                        //     [
                        //         'label' => 'All Appointments',
                        //         'route' => 'admin.appointments.index',
                        //         'active_patterns' => ['admin.appointments.index'],
                        //         'permission' => 'appointments.view',
                        //     ],
                        //     [
                        //         'label' => 'Calendar View',
                        //         'route' => 'admin.appointments.calendar',
                        //         'active_patterns' => ['admin.appointments.calendar'],
                        //         'permission' => 'appointments.view',
                        //     ],
                        //     [
                        //         'label' => 'Schedule New',
                        //         'route' => 'admin.appointments.create',
                        //         'active_patterns' => ['admin.appointments.create'],
                        //         'permission' => 'appointments.create',
                        //     ],
                        // ],
                    ],
                    [
                        'label' => 'Visits / OPD',
                        'icon' => 'ti ti-calendar-check',
                        'route' => 'admin.visits.index',
                        'active_patterns' => ['admin.visits.*'],
                        'permission' => 'visits.view',
                        'module' => 'visits',
                    ],
                    [
                        'label' => 'Queue',
                        'icon' => 'ti ti-list-numbers',
                        'permission' => 'queue.view',
                        'active_patterns' => ['admin.queue.*'],
                        'children' => [
                            [
                                'label' => 'Manage Queue',
                                'route' => 'admin.queue.manage',
                                'active_patterns' => ['admin.queue.manage'],
                                'permission' => 'queue.view',
                            ],
                            [
                                'label' => 'Queue Board',
                                'route' => 'admin.queue.board',
                                'active_patterns' => ['admin.queue.board'],
                                'permission' => 'queue.view',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Clinical',
                'items' => [
                    // [
                    //     'label' => 'Emergency',
                    //     'icon' => 'ti ti-ambulance',
                    //     'permission' => 'emergency.board.view',
                    //     'active_patterns' => ['admin.emergency.*'],
                    //     'children' => [
                    //         [
                    //             'label' => 'Emergency Board',
                    //             'route' => 'admin.emergency.board',
                    //             'active_patterns' => ['admin.emergency.board'],
                    //             'permission' => 'emergency.board.view',
                    //         ],
                    //         [
                    //             'label' => 'New Emergency Case',
                    //             'route' => 'admin.emergency.cases.create',
                    //             'active_patterns' => ['admin.emergency.cases.create'],
                    //             'permission' => 'emergency.case.create',
                    //         ],
                    //         [
                    //             'label' => 'Emergency MAR',
                    //             'route' => 'admin.emergency.medication-board',
                    //             'active_patterns' => ['admin.emergency.medication-board', 'admin.emergency.mar-chart'],
                    //             'permission' => 'emergency.medication_board.view',
                    //         ],
                    //         [
                    //             'label' => 'Emergency Bays',
                    //             'route' => 'admin.emergency.bays.index',
                    //             'active_patterns' => ['admin.emergency.bays.*'],
                    //             'permission' => 'emergency.settings.manage',
                    //         ],
                    //         [
                    //             'label' => 'Reports',
                    //             'route' => 'admin.emergency.reports.index',
                    //             'active_patterns' => ['admin.emergency.reports.*'],
                    //             'permission' => 'emergency.reports.view',
                    //         ],
                    //     ],
                    // ],
                    // [
                    //     'label' => 'Vitals / Triage',
                    //     'icon' => 'ti ti-heartbeat',
                    //     'route' => 'admin.vitals.create',
                    //     'active_patterns' => ['admin.vitals.*'],
                    //     'permission' => 'vitals.view',
                    //     'module' => 'triage',
                    // ],
                    [
                        'label' => 'Vitals / Triage',
                        'icon' => 'ti ti-ambulance',
                        'route' => 'admin.triage.index',
                        'active_patterns' => ['admin.triage.*'],
                        'permission' => 'vitals.view',
                        'module' => 'triage',
                    ],
                    [
                        'label' => 'Consultations',
                        'icon' => 'ti ti-stethoscope',
                        'route' => 'admin.consultations.index',
                        'active_patterns' => ['admin.consultations.*'],
                        'permission' => 'consultations.view',
                        'module' => 'consultation',
                    ],
                    [
                        'label' => 'Service Rendering',
                        'icon' => 'ti ti-clipboard-check',
                        'route' => 'admin.service-renderings.index',
                        'active_patterns' => ['admin.service-renderings.*'],
                        'permission' => 'service_rendering.view',
                    ],
                    // [
                    //     'label' => 'Procedures',
                    //     'icon' => 'ti ti-surgery',
                    //     'permission' => 'procedures.view',
                    //     'active_patterns' => ['admin.procedures.*', 'admin.procedure-catalogue.*'],
                    //     'children' => [
                    //         [
                    //             'label' => 'Procedure Catalog',
                    //             'route' => 'admin.procedure-catalogue.index',
                    //             'active_patterns' => ['admin.procedure-catalogue.*'],
                    //             'permission' => 'procedure_catalogue.view',
                    //         ],
                    //         [
                    //             'label' => 'Scheduled Procedures',
                    //             'route' => 'admin.procedures.schedule',
                    //             'active_patterns' => ['admin.procedures.schedule'],
                    //             'permission' => 'procedures.view',
                    //         ],
                    //     ],
                    // ],
                    // [
                    //     'label' => 'Theatre / Procedures',
                    //     'icon' => 'ti ti-stethoscope',
                    //     'permission' => 'procedure.view',
                    //     'active_patterns' => ['admin.theatre.*'],
                    //     'children' => [
                    //         [
                    //             'label' => 'Procedure Catalogue',
                    //             'route' => 'admin.procedure-catalogue.index',
                    //             'active_patterns' => ['admin.procedure-catalogue.*'],
                    //             'permission' => 'procedure_catalogue.view',
                    //         ],
                    //         [
                    //             'label' => 'Procedure Consumables',
                    //             'route' => 'admin.theatre.consumables.index',
                    //             'active_patterns' => ['admin.theatre.consumables.*'],
                    //             'permission' => 'procedure.view',
                    //         ],
                    //         // [
                    //         //     'label' => 'Scheduled Procedures',
                    //         //     'route' => 'admin.procedures.schedule',
                    //         //     'active_patterns' => ['admin.procedures.schedule'],
                    //         //     'permission' => 'procedures.view',
                    //         // ],
                    //         [
                    //             'label' => 'Scheduled Procedures',
                    //             'route' => 'admin.theatre.index',
                    //             'route_params' => ['tab' => 'pending'],
                    //             'active_patterns' => ['admin.theatre.index'],
                    //             'permission' => 'procedure.view',
                    //         ],
                    //         // [
                    //         //     'label' => 'Scheduled',
                    //         //     'route' => 'admin.theatre.index',
                    //         //     'route_params' => ['tab' => 'scheduled'],
                    //         //     'active_patterns' => ['admin.theatre.index'],
                    //         //     'permission' => 'procedure.view',
                    //         // ],
                    //         // [
                    //         //     'label' => 'In Theatre',
                    //         //     'route' => 'admin.theatre.index',
                    //         //     'route_params' => ['tab' => 'in_theatre'],
                    //         //     'active_patterns' => ['admin.theatre.index'],
                    //         //     'permission' => 'procedure.view',
                    //         // ],
                    //         // [
                    //         //     'label' => 'Recovery',
                    //         //     'route' => 'admin.theatre.index',
                    //         //     'route_params' => ['tab' => 'recovery'],
                    //         //     'active_patterns' => ['admin.theatre.index'],
                    //         //     'permission' => 'procedure.view',
                    //         // ],
                    //         // [
                    //         //     'label' => 'Completed',
                    //         //     'route' => 'admin.theatre.index',
                    //         //     'route_params' => ['tab' => 'completed'],
                    //         //     'active_patterns' => ['admin.theatre.index'],
                    //         //     'permission' => 'procedure.view',
                    //         // ],
                    //     ],
                    // ],
                ],
            ],
            [
                'title' => 'Ward / Emergency',
                'items' => [
                    // [
                    //     'label' => 'Emergency',
                    //     'icon' => 'ti ti-ambulance',
                    //     'permission' => 'emergency.board.view',
                    //     'active_patterns' => ['admin.emergency.*'],
                    //     'children' => [
                            
                    //     ],
                    // ],
                    // [
                    //     'label' => 'Vitals / Triage',
                    //     'icon' => 'ti ti-heartbeat',
                    //     'route' => 'admin.vitals.create',
                    //     'active_patterns' => ['admin.vitals.*'],
                    //     'permission' => 'vitals.view',
                    //     'module' => 'triage',
                    // ],

                    [
                        'label' => 'Emergency Board',
                        'icon' => 'ti ti-bolt',
                        'route' => 'admin.emergency.board',
                        'active_patterns' => ['admin.emergency.board'],
                        'permission' => 'emergency.board.view',
                        'module' => 'emergency',
                    ],
                    [
                        'label' => 'Emergency Consumables',
                        'icon' => 'ti ti-package',
                        'route' => 'admin.emergency.consumables.index',
                        'active_patterns' => ['admin.emergency.consumables.*'],
                        'permission' => 'emergency.board.view',
                        'module' => 'emergency',
                    ],
                    // [
                    //     'label' => 'New Emergency Case',
                    //     'icon' => 'ti ti-ambulance',
                    //     'route' => 'admin.emergency.cases.create',
                    //     'active_patterns' => ['admin.emergency.cases.create'],
                    //     'permission' => 'emergency.case.create',
                    //     'module' => 'emergency',
                    // ],
                    [
                        'label' => 'Emergency Medication Board',
                        'icon' => 'ti ti-pill',
                        'route' => 'admin.emergency.medication-board',
                        'active_patterns' => ['admin.emergency.medication-board', 'admin.emergency.mar-chart'],
                        'permission' => 'emergency.medication_board.view',
                        'module' => 'emergency',
                    ],
                    [
                        'label' => 'Emergency Bays',
                        'icon' => 'ti ti-ambulance',
                        'route' => 'admin.emergency.bays.index',
                        'active_patterns' => ['admin.emergency.bays.*'],
                        'permission' => 'emergency.settings.manage',
                        'module' => 'emergency',
                    ],

                    // TODO: Re-add reports link when we have emergency reports ready
                    // [
                    //     'label' => 'Reports',
                    //     'icon' => 'ti ti-ambulance',
                    //     'route' => 'admin.emergency.reports.index',
                    //     'active_patterns' => ['admin.emergency.reports.*'],
                    //     'permission' => 'emergency.reports.view',
                    //     'module' => 'emergency',
                    // ],


                    // [
                    //     'label' => 'Vitals / Triage',
                    //     'icon' => 'ti ti-ambulance',
                    //     'route' => 'admin.triage.index',
                    //     'active_patterns' => ['admin.triage.*'],
                    //     'permission' => 'vitals.view',
                    //     'module' => 'triage',
                    // ],
                    // [
                    //     'label' => 'Consultations',
                    //     'icon' => 'ti ti-stethoscope',
                    //     'route' => 'admin.consultations.index',
                    //     'active_patterns' => ['admin.consultations.*'],
                    //     'permission' => 'consultations.view',
                    //     'module' => 'consultation',
                    // ],
                    // [
                    //     'label' => 'Procedures',
                    //     'icon' => 'ti ti-surgery',
                    //     'permission' => 'procedures.view',
                    //     'active_patterns' => ['admin.procedures.*', 'admin.procedure-catalogue.*'],
                    //     'children' => [
                    //         [
                    //             'label' => 'Procedure Catalog',
                    //             'route' => 'admin.procedure-catalogue.index',
                    //             'active_patterns' => ['admin.procedure-catalogue.*'],
                    //             'permission' => 'procedure_catalogue.view',
                    //         ],
                    //         [
                    //             'label' => 'Scheduled Procedures',
                    //             'route' => 'admin.procedures.schedule',
                    //             'active_patterns' => ['admin.procedures.schedule'],
                    //             'permission' => 'procedures.view',
                    //         ],
                    //     ],
                    // ],
                    // [
                    //     'label' => 'Theatre / Procedures',
                    //     'icon' => 'ti ti-stethoscope',
                    //     'permission' => 'procedure.view',
                    //     'active_patterns' => ['admin.theatre.*'],
                    //     'children' => [
                    //         [
                    //             'label' => 'Procedure Catalogue',
                    //             'route' => 'admin.procedure-catalogue.index',
                    //             'active_patterns' => ['admin.procedure-catalogue.*'],
                    //             'permission' => 'procedure_catalogue.view',
                    //         ],
                    //         [
                    //             'label' => 'Procedure Consumables',
                    //             'route' => 'admin.theatre.consumables.index',
                    //             'active_patterns' => ['admin.theatre.consumables.*'],
                    //             'permission' => 'procedure.view',
                    //         ],
                    //         // [
                    //         //     'label' => 'Scheduled Procedures',
                    //         //     'route' => 'admin.procedures.schedule',
                    //         //     'active_patterns' => ['admin.procedures.schedule'],
                    //         //     'permission' => 'procedures.view',
                    //         // ],
                    //         [
                    //             'label' => 'Scheduled Procedures',
                    //             'route' => 'admin.theatre.index',
                    //             'route_params' => ['tab' => 'pending'],
                    //             'active_patterns' => ['admin.theatre.index'],
                    //             'permission' => 'procedure.view',
                    //         ],
                    //         // [
                    //         //     'label' => 'Scheduled',
                    //         //     'route' => 'admin.theatre.index',
                    //         //     'route_params' => ['tab' => 'scheduled'],
                    //         //     'active_patterns' => ['admin.theatre.index'],
                    //         //     'permission' => 'procedure.view',
                    //         // ],
                    //         // [
                    //         //     'label' => 'In Theatre',
                    //         //     'route' => 'admin.theatre.index',
                    //         //     'route_params' => ['tab' => 'in_theatre'],
                    //         //     'active_patterns' => ['admin.theatre.index'],
                    //         //     'permission' => 'procedure.view',
                    //         // ],
                    //         // [
                    //         //     'label' => 'Recovery',
                    //         //     'route' => 'admin.theatre.index',
                    //         //     'route_params' => ['tab' => 'recovery'],
                    //         //     'active_patterns' => ['admin.theatre.index'],
                    //         //     'permission' => 'procedure.view',
                    //         // ],
                    //         // [
                    //         //     'label' => 'Completed',
                    //         //     'route' => 'admin.theatre.index',
                    //         //     'route_params' => ['tab' => 'completed'],
                    //         //     'active_patterns' => ['admin.theatre.index'],
                    //         //     'permission' => 'procedure.view',
                    //         // ],
                    //     ],
                    // ],
                ],
            ],
            [
                'title' => 'Ward / Inpatient',
                'items' => [
                    [
                        'label' => 'Admissions Requests',
                        'icon' => 'ti ti-bed',
                        'route' => 'admin.admissions.requests',
                        'active_patterns' => ['admin.admissions.requests'],
                        'permission' => 'ward.view',
                        'module' => 'ward',
                    ],
                    [
                        'label' => 'Admissions Board',
                        'icon' => 'ti ti-bed',
                        'route' => 'admin.admissions.index',
                        'active_patterns' => ['admin.admissions.index'],
                        'permission' => 'ward.view',
                        'module' => 'ward',
                    ],
                    [
                        'label' => 'Medication Board',
                        'icon' => 'ti ti-pill',
                        'route' => 'admin.admissions.medication-board',
                        'active_patterns' => ['admin.admissions.medication-board', 'admin.admissions.medications.*'],
                        'permission' => 'ward.view',
                        'module' => 'ward',
                    ],
                    [
                        'label' => 'Ward Consumables',
                        'icon' => 'ti ti-package',
                        'route' => 'admin.wards.consumables.index',
                        'active_patterns' => ['admin.wards.consumables.*'],
                        'permission' => 'ward.view',
                        'module' => 'ward',
                    ],
                    // [
                    //     'label' => 'Emergency Meds',
                    //     'icon' => 'ti ti-ambulance',
                    //     'route' => 'admin.emergency.medication-board',
                    //     'active_patterns' => ['admin.emergency.medication-board'],
                    //     'permission' => 'ward.view',
                    //     // 'permission' => 'emergency.medication_board.view',
                    // ],
                    // [
                    //     'label' => 'Bed Map',
                    //     'icon' => 'ti ti-map',
                    //     'route' => 'admin.wards.bed-map',
                    //     'active_patterns' => ['admin.wards.bed-map'],
                    //     'permission' => 'ward.view',
                    // ],
                    [
                        'label' => 'Wards / Bed Management',
                        'icon' => 'ti ti-building-hospital',
                        'route' => 'admin.wards.index',
                        'active_patterns' => ['admin.wards.index'],
                        'permission' => 'ward.manage',
                        'module' => 'ward',
                    ],
                    // [
                    //     'label' => 'Bed Management',
                    //     'icon' => 'ti ti-bed-flat',
                    //     'route' => 'admin.wards.beds',
                    //     'active_patterns' => ['admin.wards.beds'],
                    //     'permission' => 'beds.manage',
                    // ],
                ],
            ],
            [
                'title' => 'Blood Bank',
                'items' => [
                    [
                        'label' => 'Blood Bank Dashboard',
                        'icon' => 'ti ti-droplet',
                        'route' => 'admin.blood-bank.dashboard',
                        'active_patterns' => ['admin.blood-bank.dashboard'],
                        'permission' => 'blood_bank.view',
                        'module' => 'blood_bank',
                    ],
                    [
                        'label' => 'Donors',
                        'icon' => 'ti ti-user',
                        'route' => 'admin.blood-bank.donors.index',
                        'active_patterns' => ['admin.blood-bank.donors.*'],
                        'permission' => 'blood_bank.donors.manage',
                        'module' => 'blood_bank',
                    ],
                    [
                        'label' => 'Donations',
                        'icon' => 'ti ti-medicine-syrup',
                        'route' => 'admin.blood-bank.donations.index',
                        'active_patterns' => ['admin.blood-bank.donations.*'],
                        'permission' => 'blood_bank.donations.record',
                        'module' => 'blood_bank',
                    ],
                    [
                        'label' => 'Units',
                        'icon' => 'ti ti-droplet',
                        'route' => 'admin.blood-bank.units.index',
                        'active_patterns' => ['admin.blood-bank.units.*'],
                        'permission' => 'blood_bank.units.view',
                        'module' => 'blood_bank',
                    ],
                    [
                        'label' => 'Requests',
                        'icon' => 'ti ti-receipt',
                        'route' => 'admin.blood-bank.requests.index',
                        'active_patterns' => ['admin.blood-bank.requests.*'],
                        'permission' => 'blood_bank.requests.view',
                        'module' => 'blood_bank',
                    ],
                    [
                        'label' => 'Reports',
                        'icon' => 'ti ti-report',
                        'route' => 'admin.blood-bank.reports.index',
                        'active_patterns' => ['admin.blood-bank.reports.*'],
                        'permission' => 'blood_bank.reports.view',
                        'module' => 'blood_bank',
                    ],
                    // [
                    //     'label' => 'Blood Bank',
                    //     'icon' => 'ti ti-droplet',
                    //     'permission' => 'blood_bank.view',
                    //     'module' => 'blood_bank',
                    //     'active_patterns' => ['admin.blood-bank.*'],
                    //     'children' => [
                    //         ['label' => 'Dashboard', 'route' => 'admin.blood-bank.dashboard', 'active_patterns' => ['admin.blood-bank.dashboard'], 'permission' => 'blood_bank.view', 'module' => 'blood_bank'],
                    //         ['label' => 'Donors', 'route' => 'admin.blood-bank.donors.index', 'active_patterns' => ['admin.blood-bank.donors.*'], 'permission' => 'blood_bank.donors.manage', 'module' => 'blood_bank'],
                    //         ['label' => 'Donations', 'route' => 'admin.blood-bank.donations.index', 'active_patterns' => ['admin.blood-bank.donations.*'], 'permission' => 'blood_bank.donations.record', 'module' => 'blood_bank'],
                    //         ['label' => 'Units', 'route' => 'admin.blood-bank.units.index', 'active_patterns' => ['admin.blood-bank.units.*'], 'permission' => 'blood_bank.units.view', 'module' => 'blood_bank'],
                    //         ['label' => 'Requests', 'route' => 'admin.blood-bank.requests.index', 'active_patterns' => ['admin.blood-bank.requests.*'], 'permission' => 'blood_bank.requests.view', 'module' => 'blood_bank'],
                    //         ['label' => 'Reports', 'route' => 'admin.blood-bank.reports.index', 'active_patterns' => ['admin.blood-bank.reports.*'], 'permission' => 'blood_bank.reports.view', 'module' => 'blood_bank'],
                    //     ],
                    // ],
                ],
            ],
            [
                'title' => 'Pharmacy',
                'items' => [
                    [
                        'label' => 'Prescriptions',
                        'icon' => 'ti ti-prescription',
                        'route' => 'admin.prescriptions.index',
                        'active_patterns' => ['admin.prescriptions.*'],
                        'permission' => 'prescriptions.view',
                        'module' => 'pharmacy',
                    ],
                    [
                        'label' => 'Dispensing',
                        'icon' => 'ti ti-pill',
                        'route' => 'admin.pharmacy.dispensing.index',
                        'active_patterns' => ['admin.pharmacy.dispensing.*'],
                        'permission' => 'pharmacy.dispensing.view',
                        'module' => 'pharmacy',
                    ],
                    [
                        'label' => 'Drug Catalog',
                        'icon' => 'ti ti-medicine-syrup',
                        'route' => 'admin.pharmacy.drugs.index',
                        'active_patterns' => ['admin.pharmacy.drugs.*'],
                        'permission' => 'pharmacy.drugs.manage',
                        'module' => 'pharmacy',
                    ],
                ],
            ],
            [
                'title' => 'Investigations',
                'items' => [
                    [
                        'label' => 'Tests',
                        'icon' => 'ti ti-test-pipe',
                        'route' => 'admin.lab.requests.index',
                        'active_patterns' => ['admin.lab.requests.*'],
                        'permission' => 'lab.requests.view',
                        'module' => 'investigations',
                    ],
                    [
                        'label' => 'Results',
                        'icon' => 'ti ti-report-medical',
                        'route' => 'admin.lab.results.index',
                        'active_patterns' => ['admin.lab.results.*'],
                        'permission' => 'lab.results.view',
                        'module' => 'investigations',
                    ],
                    [
                        'label' => 'Tests Catalogue',
                        'icon' => 'ti ti-flask',
                        'route' => 'admin.investigation-catalogue.index',
                        'active_patterns' => ['admin.investigation-catalogue.*'],
                        'permission' => 'lab.tests.manage',
                        'module' => 'investigations',
                    ],
                    [
                        'label' => 'Items Usage',
                        'icon' => 'ti ti-microscope',
                        'route' => 'admin.investigations.items.index',
                        'active_patterns' => ['admin.investigations.items.*'],
                        'permission' => 'lab.tests.manage',
                        'module' => 'investigations',
                    ],
                    // [
                    //     'label' => 'Investigation Stock',
                    //     'icon' => 'ti ti-packages',
                    //     'route' => 'admin.investigations.stock.index',
                    //     'active_patterns' => ['admin.investigations.stock.*'],
                    //     'permission' => 'lab.tests.manage',
                    //     'module' => 'investigations',
                    // ],
                ],
            ],
            [
                'title' => 'Theatre / Procedures',
                'items' => [
                    [
                        'label' => 'Procedure Requests',
                        'icon' => 'ti ti-test-pipe',
                        'route' => 'admin.theatre.index',
                        'active_patterns' => ['admin.theatre.index', 'admin.theatre.board', 'admin.theatre.show', 'admin.theatre.report'],
                        'permission' => 'procedure.view',
                        'module' => 'procedure',
                    ],
                    [
                        'label' => 'Theatre Calendar',
                        'icon' => 'ti ti-calendar-time',
                        'route' => 'admin.theatre.calendar',
                        'active_patterns' => ['admin.theatre.calendar'],
                        'permission' => 'procedure.view',
                        'module' => 'procedure',
                    ],
                    // [
                    //     'label' => 'Investigation Results',
                    //     'icon' => 'ti ti-report-medical',
                    //     'route' => 'admin.lab.results.index',
                    //     'active_patterns' => ['admin.lab.results.*'],
                    //     'permission' => 'lab.results.view',
                    //     'module' => 'investigations',
                    // ],
                    [
                        'label' => 'Procedure Catalogue',
                        'icon' => 'ti ti-flask',
                        'route' => 'admin.procedure-catalogue.index',
                        'active_patterns' => ['admin.procedure-catalogue.*'],
                        'permission' => 'procedure_catalogue.view',
                        'module' => 'procedure_catalogue',
                    ],
                    [
                        'label' => 'Procedure Consumables',
                        'icon' => 'ti ti-microscope',
                        'route' => 'admin.theatre.consumables.index',
                        'active_patterns' => ['admin.theatre.consumables.*'],
                        'permission' => 'procedure.view',
                        'module' => 'procedure',
                    ],
                    [
                        'label' => 'Theatre Rooms',
                        'icon' => 'ti ti-door',
                        'route' => 'admin.theatre.rooms.index',
                        'active_patterns' => ['admin.theatre.rooms.*'],
                        'permission' => 'theatre.rooms.view',
                        'module' => 'procedure',
                    ],
                    // [
                    //     'label' => 'Investigation Stock',
                    //     'icon' => 'ti ti-packages',
                    //     'route' => 'admin.investigations.stock.index',
                    //     'active_patterns' => ['admin.investigations.stock.*'],
                    //     'permission' => 'lab.tests.manage',
                    //     'module' => 'investigations',
                    // ],
                    // [
                    //     'label' => 'Analyzers',
                    //     'icon' => 'ti ti-device-analytics',
                    //     'route' => 'admin.analyzers.index',
                    //     'active_patterns' => ['admin.analyzers.index', 'admin.analyzers.show'],
                    //     'permission' => 'analyzer.manage',
                    //     'module' => 'analyzer',
                    // ],
                    // [
                    //     'label' => 'Analyzer Messages',
                    //     'icon' => 'ti ti-activity',
                    //     'route' => 'admin.analyzers.diagnostics',
                    //     'active_patterns' => ['admin.analyzers.diagnostics'],
                    //     'permission' => 'analyzer.manage',
                    //     'module' => 'analyzer',
                    // ],
                ],
            ],
            [
                'title' => 'Claims & Insurance',
                'items' => [
                    [
                        'label' => 'Claims',
                        'icon' => 'ti ti-file-check',
                        'route' => 'admin.claims.index',
                        'active_patterns' => ['admin.claims.index', 'admin.claims.show', 'admin.claims.review'],
                        'permission' => 'claims.view',
                        'module' => 'claims',
                    ],
                    [
                        'label' => 'Eligible Visits',
                        'icon' => 'ti ti-user-check',
                        'route' => 'admin.claims.eligible-visits',
                        'active_patterns' => ['admin.claims.eligible-visits'],
                        'permission' => 'claims.view',
                        'module' => 'claims',
                    ],
                    [
                        'label' => 'NHIA Claims',
                        'icon' => 'ti ti-shield-check',
                        'route' => 'admin.claims.nhia.index',
                        'active_patterns' => ['admin.claims.nhia.*'],
                        'permission' => 'claims.nhia.view',
                        'module' => 'claims',
                    ],
                    // [
                    //     'label' => 'New Claim',
                    //     'icon' => 'ti ti-file-plus',
                    //     'route' => 'admin.claims.create',
                    //     'active_patterns' => ['admin.claims.create'],
                    //     'permission' => 'claims.create',
                    //     'module' => 'claims',
                    // ],
                ],
            ],
            [
                'title' => 'Store & Procurement',
                'items' => [
                    [
                        'label' => 'Products',
                        'icon' => 'ti ti-box',
                        'route' => 'admin.products.index',
                        'active_patterns' => ['admin.products.*'],
                        'permission' => 'product.view',
                        'module' => 'inventory',
                    ],
                    [
                        'label' => 'Suppliers',
                        'icon' => 'ti ti-truck',
                        'route' => 'admin.store.suppliers.index',
                        'active_patterns' => ['admin.store.suppliers.*'],
                        'permission' => 'store.purchase.view',
                        'module' => 'inventory',
                    ],
                    [
                        'label' => 'Purchase Orders',
                        'icon' => 'ti ti-file-text',
                        'route' => 'admin.store.purchase-orders.index',
                        'active_patterns' => ['admin.store.purchase-orders.*'],
                        'permission' => 'store.purchase.view',
                        'module' => 'inventory',
                    ],
                    [
                        'label' => 'Purchase Returns',
                        'icon' => 'ti ti-file-minus',
                        'route' => 'admin.store.purchase-returns.index',
                        'active_patterns' => ['admin.store.purchase-returns.*'],
                        'permission' => 'store.return.view',
                        'module' => 'inventory',
                    ],
                    [
                        'label' => 'Stock Requisitions',
                        'icon' => 'ti ti-clipboard-list',
                        'route' => 'admin.store.stock-requisitions.index',
                        'active_patterns' => ['admin.store.stock-requisitions.*'],
                        'permission' => 'store.requisition.view',
                        'module' => 'inventory',
                    ],
                    [
                        'label' => 'Stock Ledger',
                        'icon' => 'ti ti-history',
                        'route' => 'admin.product-stock.ledger',
                        'active_patterns' => ['admin.product-stock.ledger'],
                        'permission' => 'stock.view',
                        'module' => 'inventory',
                    ],
                    [
                        'label' => 'Stock Balances',
                        'icon' => 'ti ti-list-numbers',
                        'route' => 'admin.product-stock.balances',
                        'active_patterns' => ['admin.product-stock.balances'],
                        'permission' => 'stock.view',
                        'module' => 'inventory',
                    ],
                    [
                        'label' => 'Inventory Valuation',
                        'icon' => 'ti ti-report-money',
                        'route' => 'admin.store.stock.valuation',
                        'active_patterns' => ['admin.store.stock.valuation'],
                        'permission' => 'reports.inventory_valuation.view',
                        'module' => 'inventory',
                    ],
                    [
                        'label' => 'Stock Locations',
                        'icon' => 'ti ti-building-warehouse',
                        'route' => 'admin.stock-locations.index',
                        'active_patterns' => ['admin.stock-locations.*'],
                        'permission' => 'stock.location.manage',
                        'module' => 'inventory',
                    ],
                    [
                        'label' => 'Stock Transfers',
                        'icon' => 'ti ti-transfer',
                        'route' => 'admin.store.stock.transfers.index',
                        'active_patterns' => ['admin.store.stock.transfers.*'],
                        'permission' => 'store.purchase.view',
                        'module' => 'inventory',
                    ],
                    [
                        'label' => 'Stock Adjustments',
                        'icon' => 'ti ti-adjustments',
                        'route' => 'admin.store.stock.adjustments.index',
                        'active_patterns' => ['admin.store.stock.adjustments.*', 'admin.store.stock.movements.*'],
                        'permission' => 'store.purchase.view',
                        'module' => 'inventory',
                    ],
                    [
                        'label' => 'Stock Returns',
                        'icon' => 'ti ti-arrow-back-up',
                        'route' => 'admin.store.stock.returns.index',
                        'active_patterns' => ['admin.store.stock.returns.*'],
                        'permission' => 'store.purchase.view',
                        'module' => 'inventory',
                    ],
                ],
            ],
            [
                'title' => 'Accounts & Finance',
                'items' => [
                    [
                        'label' => 'Billing Dashboard',
                        'icon' => 'ti ti-layout-dashboard',
                        'route' => 'admin.billing.dashboard',
                        'active_patterns' => ['admin.billing.dashboard'],
                        'permission' => 'invoices.view',
                        'module' => 'billing',
                    ],
                    [
                        'label' => 'Accounting Dashboard',
                        'icon' => 'ti ti-calculator',
                        'route' => 'admin.accounting.dashboard',
                        'active_patterns' => ['admin.accounting.dashboard'],
                        'permission' => 'accounting.dashboard.view',
                    ],
                    [
                        'label' => 'Chart of Accounts',
                        'icon' => 'ti ti-list-tree',
                        'route' => 'admin.accounting.accounts.index',
                        'active_patterns' => ['admin.accounting.accounts.*'],
                        'permission' => 'accounting.accounts.view',
                    ],
                    [
                        'label' => 'Journal Entries',
                        'icon' => 'ti ti-journal',
                        'route' => 'admin.accounting.journals.index',
                        'active_patterns' => ['admin.accounting.journals.*'],
                        'permission' => 'accounting.journals.view',
                    ],
                    [
                        'label' => 'General Ledger',
                        'icon' => 'ti ti-books',
                        'route' => 'admin.accounting.general-ledger',
                        'active_patterns' => ['admin.accounting.general-ledger'],
                        'permission' => 'accounting.reports.general_ledger',
                    ],
                    [
                        'label' => 'Trial Balance',
                        'icon' => 'ti ti-scale',
                        'route' => 'admin.accounting.trial-balance',
                        'active_patterns' => ['admin.accounting.trial-balance'],
                        'permission' => 'accounting.reports.trial_balance',
                    ],
                    [
                        'label' => 'Profit & Loss',
                        'icon' => 'ti ti-chart-bar',
                        'route' => 'admin.accounting.reports.profit-loss',
                        'active_patterns' => ['admin.accounting.reports.profit-loss'],
                        'permission' => 'accounting.reports.profit_loss',
                    ],
                    [
                        'label' => 'Balance Sheet',
                        'icon' => 'ti ti-report-analytics',
                        'route' => 'admin.accounting.reports.balance-sheet',
                        'active_patterns' => ['admin.accounting.reports.balance-sheet'],
                        'permission' => 'accounting.reports.balance_sheet',
                    ],
                    [
                        'label' => 'Cashbook',
                        'icon' => 'ti ti-cash',
                        'route' => 'admin.accounting.reports.cashbook',
                        'active_patterns' => ['admin.accounting.reports.cashbook'],
                        'permission' => 'accounting.reports.cashbook',
                    ],
                    [
                        'label' => 'Cash Flow',
                        'icon' => 'ti ti-arrows-exchange',
                        'route' => 'admin.accounting.reports.cash-flow',
                        'active_patterns' => ['admin.accounting.reports.cash-flow'],
                        'permission' => 'accounting.reports.cash_flow',
                    ],
                    [
                        'label' => 'Revenue by Dept',
                        'icon' => 'ti ti-building-bank',
                        'route' => 'admin.accounting.reports.revenue-by-department',
                        'active_patterns' => ['admin.accounting.reports.revenue-by-department'],
                        'permission' => 'accounting.reports.revenue_by_department',
                    ],
                    [
                        'label' => 'Expense by Dept',
                        'icon' => 'ti ti-building-bank',
                        'route' => 'admin.accounting.reports.expense-by-department',
                        'active_patterns' => ['admin.accounting.reports.expense-by-department'],
                        'permission' => 'accounting.reports.expense_by_department',
                    ],
                    [
                        'label' => 'Fiscal Years',
                        'icon' => 'ti ti-calendar-stats',
                        'route' => 'admin.accounting.fiscal-years.index',
                        'active_patterns' => ['admin.accounting.fiscal-years.*'],
                        'permission' => 'accounting.fiscal_years.view',
                    ],
                    [
                        'label' => 'Accounting Periods',
                        'icon' => 'ti ti-calendar-time',
                        'route' => 'admin.accounting.periods.index',
                        'active_patterns' => ['admin.accounting.periods.*'],
                        'permission' => 'accounting.periods.view',
                    ],
                    [
                        'label' => 'Accounting Settings',
                        'icon' => 'ti ti-settings-dollar',
                        'route' => 'admin.accounting.settings.index',
                        'active_patterns' => ['admin.accounting.settings.*'],
                        'permission' => 'accounting.settings.view',
                        'module' => 'accounting_advanced',
                    ],
                    [
                        'label' => 'Basic Posting Bridge',
                        'icon' => 'ti ti-arrows-transfer-up',
                        'route' => 'admin.accounting.basic-bridge.index',
                        'active_patterns' => ['admin.accounting.basic-bridge.*'],
                        'permission' => 'accounting.basic.batch.view',
                        'module' => 'accounting_advanced',
                    ],
                    [
                        'label' => 'Posting Templates',
                        'icon' => 'ti ti-template',
                        'route' => 'admin.accounting.posting-templates.index',
                        'active_patterns' => ['admin.accounting.posting-templates.*'],
                        'permission' => 'accounting.posting_templates.view',
                        'module' => 'accounting_advanced',
                    ],
                    [
                        'label' => 'Failed Postings',
                        'icon' => 'ti ti-alert-triangle',
                        'route' => 'admin.accounting.failed-postings.index',
                        'active_patterns' => ['admin.accounting.failed-postings.*'],
                        'permission' => 'accounting.failed_postings.view',
                        'module' => 'accounting_advanced',
                    ],
                    [
                        'label' => 'Subledger Reconciliation',
                        'icon' => 'ti ti-scale',
                        'route' => 'admin.accounting.subledger-reconciliation.index',
                        'active_patterns' => ['admin.accounting.subledger-reconciliation.*'],
                        'permission' => 'accounting.subledger_reconciliation.view',
                        'module' => 'accounting_advanced',
                    ],
                    [
                        'label' => 'Payroll Posting',
                        'icon' => 'ti ti-cash-banknote',
                        'route' => 'admin.accounting.payroll-posting.index',
                        'active_patterns' => ['admin.accounting.payroll-posting.*'],
                        'permission' => 'accounting.payroll_posting.view',
                        'module' => 'accounting_advanced',
                    ],
                    [
                        'label' => 'Bank Accounts',
                        'icon' => 'ti ti-building-bank',
                        'route' => 'admin.accounting.bank.accounts.index',
                        'active_patterns' => ['admin.accounting.bank.accounts.*'],
                        'permission' => 'accounting.bank_accounts.view',
                        'module' => 'accounting_advanced',
                    ],
                    [
                        'label' => 'Statement Imports',
                        'icon' => 'ti ti-file-import',
                        'route' => 'admin.accounting.bank.imports.index',
                        'active_patterns' => ['admin.accounting.bank.imports.*'],
                        'permission' => 'accounting.bank_statements.view',
                        'module' => 'accounting_advanced',
                    ],
                    [
                        'label' => 'Bank Reconciliation',
                        'icon' => 'ti ti-arrows-diff',
                        'route' => 'admin.accounting.bank.reconciliations.index',
                        'active_patterns' => ['admin.accounting.bank.reconciliations.*'],
                        'permission' => 'accounting.bank_reconciliation.view',
                        'module' => 'accounting_advanced',
                    ],
                    [
                        'label' => 'Receive Payments',
                        'icon' => 'ti ti-cash',
                        'route' => 'admin.billing.payments.receive',
                        'active_patterns' => ['admin.billing.payments.receive'],
                        'permission' => 'payments.create',
                        'module' => 'billing',
                    ],
                    [
                        'label' => 'Income',
                        'icon' => 'ti ti-trending-up',
                        'route' => 'admin.accounts.income.index',
                        'active_patterns' => ['admin.accounts.income.*'],
                        'permission' => 'accounts.entries.view',
                    ],
                    [
                        'label' => 'Expenses',
                        'icon' => 'ti ti-trending-down',
                        'route' => 'admin.accounts.expenses.index',
                        'active_patterns' => ['admin.accounts.expenses.*'],
                        'permission' => 'accounts.entries.view',
                    ],
                    [
                        'label' => 'Invoices',
                        'icon' => 'ti ti-file-invoice',
                        'route' => 'admin.billing.invoices.index',
                        'active_patterns' => ['admin.billing.invoices.*'],
                        'permission' => 'invoices.view',
                        'module' => 'billing',
                    ],
                    [
                        'label' => 'Counter Sale',
                        'icon' => 'ti ti-cash-register',
                        'route' => 'admin.billing.counter-sale.create',
                        'active_patterns' => ['admin.billing.counter-sale.*'],
                        'permission' => 'invoices.create',
                        'module' => 'billing',
                    ],
                    [
                        'label' => 'Payments',
                        'icon' => 'ti ti-cash',
                        'route' => 'admin.billing.payments.index',
                        'active_patterns' => ['admin.billing.payments.index', 'admin.billing.payments.receipt'],
                        'permission' => 'payments.view',
                        'module' => 'billing',
                    ],
                    [
                        'label' => 'Credit Notes',
                        'icon' => 'ti ti-receipt-refund',
                        'route' => 'admin.billing.credit-notes.index',
                        'active_patterns' => ['admin.billing.credit-notes.*'],
                        'permission' => 'credit_notes.view',
                        'module' => 'billing',
                    ],
                    [
                        'label' => 'Corporate Sponsors',
                        'icon' => 'ti ti-building-bank',
                        'route' => 'admin.billing.sponsors.index',
                        'active_patterns' => ['admin.billing.sponsors.*'],
                        'permission' => 'sponsors.view',
                        'module' => 'billing',
                    ],
                    [
                        'label' => 'AR Aging',
                        'icon' => 'ti ti-clock-dollar',
                        'route' => 'admin.billing.reports.aging',
                        'active_patterns' => ['admin.billing.reports.aging'],
                        'permission' => 'reports.ar_aging.view',
                        'module' => 'billing',
                    ],
                    [
                        'label' => 'Supplier Payables',
                        'icon' => 'ti ti-file-dollar',
                        'route' => 'admin.accounts-payable.payables',
                        'active_patterns' => ['admin.accounts-payable.payables'],
                        'permission' => 'accounts_payable.view',
                    ],
                    [
                        'label' => 'Supplier Payments',
                        'icon' => 'ti ti-cash',
                        'route' => 'admin.accounts-payable.payments',
                        'active_patterns' => ['admin.accounts-payable.payments', 'admin.accounts-payable.statement'],
                        'permission' => 'accounts_payable.view',
                    ],
                    [
                        'label' => 'AP Aging',
                        'icon' => 'ti ti-clock-dollar',
                        'route' => 'admin.accounts-payable.aging',
                        'active_patterns' => ['admin.accounts-payable.aging'],
                        'permission' => 'reports.ap_aging.view',
                    ],
                    [
                        'label' => 'Discount Report',
                        'icon' => 'ti ti-discount-2',
                        'route' => 'admin.billing.reports.discounts',
                        'active_patterns' => ['admin.billing.reports.discounts'],
                        'permission' => 'billing.discount.report',
                        'module' => 'billing',
                    ],
                    [
                        'label' => 'Patient Statements',
                        'icon' => 'ti ti-file-text',
                        'route' => 'admin.billing.statements.index',
                        'active_patterns' => ['admin.billing.statements.*'],
                        'permission' => 'invoices.view',
                        'module' => 'billing',
                    ],
                    [
                        'label' => 'Daily Collection',
                        'icon' => 'ti ti-report-money',
                        'route' => 'admin.accounts.daily-collection',
                        'active_patterns' => ['admin.accounts.daily-collection'],
                        'permission' => 'accounts.entries.view',
                    ],
                    [
                        'label' => 'Reconciliation',
                        'icon' => 'ti ti-chart-bar',
                        'route' => 'admin.accounts.reconciliation',
                        'active_patterns' => ['admin.accounts.reconciliation'],
                        'permission' => 'accounts.entries.view',
                    ],
                    [
                        'label' => 'Account Categories',
                        'icon' => 'ti ti-category',
                        'route' => 'admin.accounts.categories.index',
                        'active_patterns' => ['admin.accounts.categories.*'],
                        'permission' => 'accounts.manage',
                    ],
                ],
            ],
            [
                'title' => 'HR & Payroll',
                'items' => [
                    [
                        'label' => 'Employees',
                        'icon' => 'ti ti-id-badge-2',
                        'route' => 'admin.hr.employees.index',
                        'active_patterns' => ['admin.hr.employees.*'],
                        'permission' => 'hr.employees.view',
                        'module' => 'hr',
                    ],
                    [
                        'label' => 'Attendance',
                        'icon' => 'ti ti-clock-record',
                        'route' => 'admin.hr.attendance.index',
                        'active_patterns' => ['admin.hr.attendance.*'],
                        'permission' => 'hr.attendance.view',
                        'module' => 'hr',
                    ],
                    [
                        'label' => 'Leave Requests',
                        'icon' => 'ti ti-calendar-off',
                        'route' => 'admin.hr.leave.index',
                        'active_patterns' => ['admin.hr.leave.*'],
                        'permission' => 'hr.leave.view',
                        'module' => 'hr',
                    ],
                    [
                        'label' => 'Payroll',
                        'icon' => 'ti ti-report-money',
                        'route' => 'admin.hr.payroll.index',
                        'active_patterns' => ['admin.hr.payroll.*'],
                        'permission' => 'hr.payroll.view',
                        'module' => 'payroll',
                    ],
                    [
                        'label' => 'HR Configuration',
                        'icon' => 'ti ti-settings',
                        'route' => 'admin.hr.configuration.index',
                        'active_patterns' => ['admin.hr.configuration.*'],
                        'permission' => 'hr.shifts.view',
                        'module' => 'hr',
                    ],
                ],
            ],
            [
                'title' => 'Reports',
                'items' => [
                    [
                        'label' => 'Reports Hub',
                        'icon' => 'ti ti-report-analytics',
                        'route' => 'admin.reports.index',
                        'active_patterns' => ['admin.reports.index'],
                        'permission' => 'reports.view',
                        'module' => 'reports',
                    ],
                    [
                        'label' => 'Statistical Reports',
                        'icon' => 'ti ti-chart-histogram',
                        'permission' => 'statistics.view',
                        'module' => 'reports',
                        'active_patterns' => ['admin.statistics.*'],
                        'children' => [
                            ['label' => 'Analytics Dashboard', 'route' => 'admin.statistics.dashboard', 'active_patterns' => ['admin.statistics.dashboard'], 'permission' => 'statistics.view', 'module' => 'reports'],
                            ['label' => 'Hospital Activity', 'route' => 'admin.statistics.activity', 'active_patterns' => ['admin.statistics.activity'], 'permission' => 'statistics.activity.view', 'module' => 'reports'],
                            ['label' => 'Diagnosis Statistics', 'route' => 'admin.statistics.diagnoses', 'active_patterns' => ['admin.statistics.diagnoses'], 'permission' => 'statistics.diagnosis.view', 'module' => 'reports'],
                            ['label' => 'Complaint Statistics', 'route' => 'admin.statistics.complaints', 'active_patterns' => ['admin.statistics.complaints'], 'permission' => 'statistics.complaints.view', 'module' => 'reports'],
                            ['label' => 'Consultation Statistics', 'route' => 'admin.statistics.consultations', 'active_patterns' => ['admin.statistics.consultations'], 'permission' => 'statistics.consultation.view', 'module' => 'reports'],
                            ['label' => 'Pharmacy Statistics', 'route' => 'admin.statistics.pharmacy', 'active_patterns' => ['admin.statistics.pharmacy'], 'permission' => 'statistics.pharmacy.view', 'module' => 'reports'],
                            ['label' => 'Investigation Statistics', 'route' => 'admin.statistics.investigations', 'active_patterns' => ['admin.statistics.investigations'], 'permission' => 'statistics.investigations.view', 'module' => 'reports'],
                            ['label' => 'Procedure / Theatre', 'route' => 'admin.statistics.procedures', 'active_patterns' => ['admin.statistics.procedures'], 'permission' => 'statistics.procedures.view', 'module' => 'reports'],
                            ['label' => 'Emergency Statistics', 'route' => 'admin.statistics.emergency', 'active_patterns' => ['admin.statistics.emergency'], 'permission' => 'statistics.emergency.view', 'module' => 'reports'],
                            ['label' => 'Admission Statistics', 'route' => 'admin.statistics.admission', 'active_patterns' => ['admin.statistics.admission'], 'permission' => 'statistics.admission.view', 'module' => 'reports'],
                            ['label' => 'MAR Statistics', 'route' => 'admin.statistics.mar', 'active_patterns' => ['admin.statistics.mar'], 'permission' => 'statistics.mar.view', 'module' => 'reports'],
                            ['label' => 'Billing Statistics', 'route' => 'admin.statistics.billing', 'active_patterns' => ['admin.statistics.billing'], 'permission' => 'statistics.billing.view', 'module' => 'reports'],
                            ['label' => 'Claims Statistics', 'route' => 'admin.statistics.claims', 'active_patterns' => ['admin.statistics.claims'], 'permission' => 'statistics.claims.view', 'module' => 'reports'],
                            ['label' => 'Stock Statistics', 'route' => 'admin.statistics.stock', 'active_patterns' => ['admin.statistics.stock'], 'permission' => 'statistics.stock.view', 'module' => 'reports'],
                            ['label' => 'Blood Bank Statistics', 'route' => 'admin.statistics.blood-bank', 'active_patterns' => ['admin.statistics.blood-bank'], 'permission' => 'statistics.blood_bank.view', 'module' => 'reports'],
                            ['label' => 'Staff Performance', 'route' => 'admin.statistics.staff-performance', 'active_patterns' => ['admin.statistics.staff-performance'], 'permission' => 'statistics.staff_performance.view', 'module' => 'reports'],
                        ],
                    ],
                    [
                        'label' => 'Financial Reports',
                        'icon' => 'ti ti-report-money',
                        'permission' => 'reports.view',
                        'module' => 'reports',
                        'active_patterns' => [
                            'admin.reports.index',
                            'admin.reports.dashboard',
                            'admin.reports.income',
                            'admin.reports.daily-collection',
                            'admin.reports.insurance-claims',
                            'admin.reports.claims',
                            'admin.reports.statement-search',
                            'admin.reports.patient-statement',
                        ],
                        'children' => [
                            ['label' => 'Reports Hub', 'route' => 'admin.reports.index', 'active_patterns' => ['admin.reports.index'], 'permission' => 'reports.view', 'module' => 'reports'],
                            ['label' => 'Reports Dashboard', 'route' => 'admin.reports.dashboard', 'active_patterns' => ['admin.reports.dashboard'], 'permission' => 'reports.view', 'module' => 'reports'],
                            ['label' => 'Income Report', 'route' => 'admin.reports.income', 'active_patterns' => ['admin.reports.income'], 'permission' => 'reports.view', 'module' => 'reports'],
                            ['label' => 'Daily Collection', 'route' => 'admin.reports.daily-collection', 'active_patterns' => ['admin.reports.daily-collection'], 'permission' => 'reports.view', 'module' => 'reports'],
                            ['label' => 'Insurance Claims Report', 'route' => 'admin.reports.insurance-claims', 'active_patterns' => ['admin.reports.insurance-claims'], 'permission' => 'reports.view', 'module' => 'reports'],
                            ['label' => 'Billing Operations', 'route' => 'admin.reports.billing', 'active_patterns' => ['admin.reports.billing'], 'permission' => 'reports.billing', 'module' => 'reports'],
                            ['label' => 'Claims Report', 'route' => 'admin.reports.claims', 'active_patterns' => ['admin.reports.claims'], 'permission' => 'reports.view', 'module' => 'reports'],
                            ['label' => 'Patient Statement', 'route' => 'admin.reports.statement-search', 'active_patterns' => ['admin.reports.statement-search', 'admin.reports.patient-statement'], 'permission' => 'reports.view', 'module' => 'reports'],
                        ],
                    ],
                    [
                        'label' => 'Clinical Reports',
                        'icon' => 'ti ti-stethoscope',
                        'permission' => 'reports.view',
                        'module' => 'reports',
                        'active_patterns' => [
                            'admin.reports.patients',
                            'admin.reports.visits',
                            'admin.reports.consultations',
                            'admin.reports.diagnoses',
                            'admin.reports.complaints',
                            'admin.reports.consultation-stats',
                            'admin.reports.admissions',
                            'admin.reports.admission',
                            'admin.reports.discharges',
                            'admin.reports.investigations',
                            'admin.reports.procedures',
                            'admin.reports.theatre',
                            'admin.reports.emergency',
                            'admin.reports.mar',
                            'admin.reports.investigation-revenue',
                        ],
                        'children' => [
                            ['label' => 'Patient Report', 'route' => 'admin.reports.patients', 'active_patterns' => ['admin.reports.patients'], 'permission' => 'reports.view', 'module' => 'reports'],
                            ['label' => 'Visit Report', 'route' => 'admin.reports.visits', 'active_patterns' => ['admin.reports.visits'], 'permission' => 'reports.view', 'module' => 'reports'],
                            ['label' => 'Consultations', 'route' => 'admin.reports.consultations', 'active_patterns' => ['admin.reports.consultations'], 'permission' => 'reports.consultations', 'module' => 'reports'],
                            ['label' => 'Diagnoses', 'route' => 'admin.reports.diagnoses', 'active_patterns' => ['admin.reports.diagnoses'], 'permission' => 'reports.diagnoses', 'module' => 'reports'],
                            ['label' => 'Complaints', 'route' => 'admin.reports.complaints', 'active_patterns' => ['admin.reports.complaints'], 'permission' => 'reports.complaints', 'module' => 'reports'],
                            ['label' => 'Consultation Stats', 'route' => 'admin.reports.consultation-stats', 'active_patterns' => ['admin.reports.consultation-stats'], 'permission' => 'reports.view', 'module' => 'reports'],
                            ['label' => 'Investigations', 'route' => 'admin.reports.investigations', 'active_patterns' => ['admin.reports.investigations'], 'permission' => 'reports.investigations', 'module' => 'reports'],
                            ['label' => 'Procedures', 'route' => 'admin.reports.procedures', 'active_patterns' => ['admin.reports.procedures'], 'permission' => 'reports.procedures', 'module' => 'reports'],
                            ['label' => 'Theatre', 'route' => 'admin.reports.theatre', 'active_patterns' => ['admin.reports.theatre'], 'permission' => 'reports.theatre', 'module' => 'reports'],
                            ['label' => 'Emergency', 'route' => 'admin.reports.emergency', 'active_patterns' => ['admin.reports.emergency'], 'permission' => 'reports.emergency', 'module' => 'reports'],
                            ['label' => 'Admissions Report', 'route' => 'admin.reports.admissions', 'active_patterns' => ['admin.reports.admissions'], 'permission' => 'reports.view', 'module' => 'reports'],
                            ['label' => 'Admission Operations', 'route' => 'admin.reports.admission', 'active_patterns' => ['admin.reports.admission'], 'permission' => 'reports.admission', 'module' => 'reports'],
                            ['label' => 'Medication Administration', 'route' => 'admin.reports.mar', 'active_patterns' => ['admin.reports.mar'], 'permission' => 'reports.mar', 'module' => 'reports'],
                            ['label' => 'Discharges Report', 'route' => 'admin.reports.discharges', 'active_patterns' => ['admin.reports.discharges'], 'permission' => 'reports.view', 'module' => 'reports'],
                            ['label' => 'Investigation Revenue', 'route' => 'admin.reports.investigation-revenue', 'active_patterns' => ['admin.reports.investigation-revenue'], 'permission' => 'reports.view', 'module' => 'reports'],
                        ],
                    ],
                    [
                        'label' => 'Pharmacy & HR',
                        'icon' => 'ti ti-pill',
                        'permission' => 'reports.view',
                        'module' => 'reports',
                        'active_patterns' => [
                            'admin.reports.pharmacy',
                            'admin.reports.pharmacy-sales',
                            'admin.reports.pharmacy-sales-summary',
                            'admin.reports.stock',
                            'admin.reports.blood-bank',
                            'admin.reports.stock-valuation',
                            'admin.reports.expired-stock',
                            'admin.reports.leave',
                            'admin.reports.payroll',
                        ],
                        'children' => [
                            ['label' => 'Pharmacy Operations', 'route' => 'admin.reports.pharmacy', 'active_patterns' => ['admin.reports.pharmacy'], 'permission' => 'reports.pharmacy', 'module' => 'reports'],
                            ['label' => 'Pharmacy Sales', 'route' => 'admin.reports.pharmacy-sales', 'active_patterns' => ['admin.reports.pharmacy-sales'], 'permission' => 'reports.view', 'module' => 'reports'],
                            ['label' => 'Sales Summary', 'route' => 'admin.reports.pharmacy-sales-summary', 'active_patterns' => ['admin.reports.pharmacy-sales-summary'], 'permission' => 'reports.view', 'module' => 'reports'],
                            ['label' => 'Stock Operations', 'route' => 'admin.reports.stock', 'active_patterns' => ['admin.reports.stock'], 'permission' => 'reports.stock', 'module' => 'reports'],
                            ['label' => 'Blood Bank', 'route' => 'admin.reports.blood-bank', 'active_patterns' => ['admin.reports.blood-bank'], 'permission' => 'reports.blood_bank', 'module' => 'reports'],
                            ['label' => 'Stock Valuation', 'route' => 'admin.reports.stock-valuation', 'active_patterns' => ['admin.reports.stock-valuation'], 'permission' => 'reports.view', 'module' => 'reports'],
                            ['label' => 'Expired Stock', 'route' => 'admin.reports.expired-stock', 'active_patterns' => ['admin.reports.expired-stock'], 'permission' => 'reports.view', 'module' => 'reports'],
                            ['label' => 'Leave Report', 'route' => 'admin.reports.leave', 'active_patterns' => ['admin.reports.leave'], 'permission' => 'reports.view', 'module' => 'reports'],
                            ['label' => 'Payroll Report', 'route' => 'admin.reports.payroll', 'active_patterns' => ['admin.reports.payroll'], 'permission' => 'reports.view', 'module' => 'reports'],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Administration',
                'items' => [
                    [
                        'label' => 'Users',
                        'icon' => 'ti ti-users-group',
                        'permission' => 'users.view',
                        'module' => 'users',
                        'active_patterns' => ['admin.users.*'],
                        'children' => [
                            ['label' => 'All Users', 'route' => 'admin.users.index', 'active_patterns' => ['admin.users.index'], 'permission' => 'users.view', 'module' => 'users'],
                            ['label' => 'Add User', 'route' => 'admin.users.create', 'active_patterns' => ['admin.users.create'], 'permission' => 'users.view', 'module' => 'users'],
                        ],
                    ],
                    [
                        'label' => 'Roles & Permissions',
                        'icon' => 'ti ti-shield-lock',
                        'route' => 'admin.roles.index',
                        'active_patterns' => ['admin.roles.*'],
                        'permission' => 'users.view',
                        'module' => 'users',
                    ],
                    [
                        'label' => 'Permissions Dashboard',
                        'icon' => 'ti ti-list-check',
                        'route' => 'admin.permissions.index',
                        'active_patterns' => ['admin.permissions.*'],
                        'permission' => 'permissions.view',
                        'module' => 'users',
                    ],
                    [
                        'label' => 'Departments',
                        'icon' => 'ti ti-building-bank',
                        'route' => 'admin.departments.index',
                        'active_patterns' => ['admin.departments.*'],
                        'permission' => 'departments.view',
                        'module' => 'departments',
                    ],
                    [
                        'label' => 'Designations',
                        'icon' => 'ti ti-user-cog',
                        'route' => 'admin.designations.index',
                        'active_patterns' => ['admin.designations.*'],
                        'permission' => 'departments.view',
                        'module' => 'departments',
                    ],
                ],
            ],
            [
                'title' => 'Configurations',
                'items' => [
                    [
                        'label' => 'Services',
                        'icon' => 'ti ti-list-details',
                        'route' => 'admin.services.index',
                        'active_patterns' => ['admin.services.*'],
                        'permission' => 'services.manage',
                        'module' => 'services',
                    ],
                    [
                        'label' => 'Specialties',
                        'icon' => 'ti ti-stethoscope',
                        'route' => 'admin.specialties.index',
                        'active_patterns' => ['admin.specialties.*'],
                        'permission' => 'services.manage',
                        'module' => 'services',
                    ],
                    [
                        'label' => 'Insurance Providers',
                        'icon' => 'ti ti-shield-check',
                        'route' => 'admin.insurance-providers.index',
                        'active_patterns' => ['admin.insurance-providers.*', 'admin.insurance-tiers.*'],
                        'permission' => 'claims.view',
                        'module' => 'insurance',
                    ],
                    [
                        'label' => 'Medical Patterns',
                        'icon' => 'ti ti-template',
                        'route' => 'admin.patterns.index',
                        'active_patterns' => ['admin.patterns.*'],
                        'permission' => 'consultations.view',
                        'module' => 'medical-patterns',
                    ],
                    [
                        'label' => 'ICD-10 Codes',
                        'icon' => 'ti ti-medical-cross',
                        'route' => 'admin.icd-codes.index',
                        'active_patterns' => ['admin.icd-codes.*'],
                        'permission' => 'icd.manage',
                    ],
                    [
                        'label' => 'Analyzers',
                        'icon' => 'ti ti-device-analytics',
                        'route' => 'admin.analyzers.index',
                        'active_patterns' => ['admin.analyzers.index', 'admin.analyzers.show'],
                        'permission' => 'analyzer.manage',
                        'module' => 'analyzer',
                    ],
                    [
                        'label' => 'Analyzer Messages',
                        'icon' => 'ti ti-activity',
                        'route' => 'admin.analyzers.diagnostics',
                        'active_patterns' => ['admin.analyzers.diagnostics'],
                        'permission' => 'analyzer.manage',
                        'module' => 'analyzer',
                    ],
                ],
            ],
            // [
            //     'title' => null,
            //     'items' => [
            //         [
            //             'label' => 'Notifications',
            //             'icon' => 'ti ti-bell',
            //             'route' => 'admin.notifications.index',
            //             'active_patterns' => ['admin.notifications.*'],
            //             'permission' => 'notifications.view',
            //             'module' => 'notifications',
            //             'badge' => $unreadNotifications > 0 ? $unreadNotifications : null,
            //             'badge_class' => 'badge bg-danger rounded-pill ms-auto',
            //         ],
            //     ],
            // ],
            [
                'title' => 'Settings',
                'items' => [
                    [
                        'label' => 'Settings',
                        'icon' => 'ti ti-settings',
                        'active_patterns' => ['admin.settings.*', 'admin.modules.*', 'admin.complaints.catalogue.*'],
                        'children' => [
                            [
                                'label' => 'Organization',
                                'icon' => 'ti ti-building',
                                'route' => 'admin.settings.organization',
                                'active_patterns' => ['admin.settings.organization'],
                                'permission' => 'settings.manage',
                                'module' => 'settings',
                            ],
                            [
                                'label' => 'Invoice Settings',
                                'icon' => 'ti ti-file-invoice',
                                'route' => 'admin.settings.invoice',
                                'active_patterns' => ['admin.settings.invoice'],
                                'permission' => 'settings.manage',
                                'module' => 'settings',
                            ],
                            [
                                'label' => 'Payment Methods',
                                'icon' => 'ti ti-credit-card',
                                'route' => 'admin.settings.payment-methods',
                                'active_patterns' => ['admin.settings.payment-methods'],
                                'permission' => 'settings.manage',
                                'module' => 'settings',
                            ],
                            [
                                'label' => 'Activity Log',
                                'icon' => 'ti ti-history',
                                'route' => 'admin.settings.activity-log',
                                'active_patterns' => ['admin.settings.activity-log'],
                                'permission' => 'settings.manage',
                                'module' => 'settings',
                            ],
                            [
                                'label' => 'Complaint Catalogue',
                                'icon' => 'ti ti-message-report',
                                'route' => 'admin.complaints.catalogue.index',
                                'active_patterns' => ['admin.complaints.catalogue.*'],
                                'permission' => 'complaints.catalogue.view',
                                'module' => 'settings',
                            ],
                            [
                                'label' => 'Modules',
                                'icon' => 'ti ti-puzzle',
                                'route' => 'admin.modules.index',
                                'active_patterns' => ['admin.modules.*'],
                                'permission' => 'modules.manage',
                                'module' => 'settings',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $sections = $this->splitAccountingSections($sections);

        return $this->finaliseSections($sections, $user, $currentRouteName);
    }

    /**
     * Separate revenue collection, simple cash accounting, and the double-entry
     * ledger so each tier can be enabled and navigated independently.
     */
    protected function splitAccountingSections(array $sections): array
    {
        $result = [];

        foreach ($sections as $section) {
            if (($section['title'] ?? null) !== 'Accounts & Finance') {
                $result[] = $section;
                continue;
            }

            $items = collect($section['items'])->keyBy('label');
            $pick = fn (array $labels, ?string $module = null) => collect($labels)
                ->map(fn (string $label) => $items->get($label))
                ->filter()
                ->map(function (array $item) use ($module) {
                    if ($module) {
                        $item['module'] = $module;
                    }
                    return $item;
                })
                ->values()
                ->all();

            $result[] = [
                'title' => 'Billing & Collections',
                'items' => $pick([
                    'Billing Dashboard',
                    'Invoices',
                    'Counter Sale',
                    'Receive Payments',
                    'Payments',
                    'Credit Notes',
                    'Corporate Sponsors',
                    'Patient Statements',
                    'Discount Report',
                    'AR Aging',
                ]),
            ];

            $basic = $pick([
                'Income',
                'Expenses',
                'Daily Collection',
                'Reconciliation',
                'Account Categories',
            ], 'accounting_basic');
            array_splice($basic, 3, 0, [[
                'label' => 'Cashier Handover',
                'icon' => 'ti ti-arrows-exchange',
                'route' => 'admin.accounts.handover.index',
                'active_patterns' => ['admin.accounts.handover.*'],
                'permission' => 'accounts.cashier',
                'module' => 'accounting_basic',
            ]]);
            $result[] = ['title' => 'Basic Accounting', 'items' => $basic];

            $result[] = [
                'title' => 'Advanced Accounting',
                'items' => $pick([
                    'Accounting Dashboard',
                    'Chart of Accounts',
                    'Journal Entries',
                    'General Ledger',
                    'Trial Balance',
                    'Cashbook',
                    'Profit & Loss',
                    'Balance Sheet',
                    'Revenue by Dept',
                    'Expense by Dept',
                    'Supplier Payables',
                    'Supplier Payments',
                    'AP Aging',
                    'Fiscal Years',
                    'Accounting Periods',
                    'Accounting Settings',
                    'Basic Posting Bridge',
                    'Posting Templates',
                    'Failed Postings',
                    'Subledger Reconciliation',
                    'Bank Accounts',
                    'Statement Imports',
                    'Bank Reconciliation',
                ], 'accounting_advanced'),
            ];
        }

        return $result;
    }

    protected function finaliseSections(array $sections, User $user, string $currentRouteName): array
    {
        return array_values(array_filter(array_map(
            fn (array $section) => $this->filterSection($section, $user, $currentRouteName),
            $sections,
        )));
    }

    // ------------------------------------------------------------------
    // Consultation / Doctor focused sidebar
    // ------------------------------------------------------------------
    protected function consultationSections(int $unreadNotifications): array
    {
        return [
            [
                'title' => 'Main Menu',
                'items' => [
                    [
                        'label' => 'Dashboard',
                        'icon' => 'ti ti-layout-dashboard',
                        'route' => 'admin.dashboard',
                        'active_patterns' => ['admin.dashboard', 'doctor.dashboard', 'dashboard'],
                    ],
                ],
            ],
            [
                'title' => 'Consultation Queue',
                'items' => [
                    [
                        'label' => 'Consultation Queue',
                        'icon' => 'ti ti-list-numbers',
                        'route' => 'admin.queue.manage',
                        'active_patterns' => ['admin.queue.*'],
                        'permission' => 'consultation.queue',
                    ],
                    [
                        'label' => 'My Appointments',
                        'icon' => 'ti ti-calendar-event',
                        'route' => 'admin.appointments.index',
                        'active_patterns' => ['admin.appointments.*'],
                        'permission' => 'appointments.view',
                    ],
                ],
            ],
            [
                'title' => 'Clinical Work',
                'items' => [
                    [
                        'label' => 'Patients',
                        'icon' => 'ti ti-user-heart',
                        'route' => 'admin.patients.index',
                        'active_patterns' => ['admin.patients.*'],
                        'permission' => 'consultation.view_patient',
                    ],
                    [
                        'label' => 'Consultations',
                        'icon' => 'ti ti-stethoscope',
                        'route' => 'admin.consultations.index',
                        'active_patterns' => ['admin.consultations.*'],
                        'permission' => 'consultation.create',
                    ],
                    [
                        'label' => 'Vitals',
                        'icon' => 'ti ti-heartbeat',
                        'route' => 'admin.vitals.create',
                        'active_patterns' => ['admin.vitals.*'],
                        'permission' => 'vitals.view',
                    ],
                    [
                        'label' => 'Visits / OPD',
                        'icon' => 'ti ti-calendar-check',
                        'route' => 'admin.visits.index',
                        'active_patterns' => ['admin.visits.*'],
                        'permission' => 'visits.view',
                    ],
                    [
                        'label' => 'Admissions',
                        'icon' => 'ti ti-bed',
                        'route' => 'admin.admissions.index',
                        'active_patterns' => ['admin.admissions.*'],
                        'permission' => 'ward.view',
                    ],
                ],
            ],
            [
                'title' => 'Requests & Results',
                'items' => [
                    [
                        'label' => 'Prescriptions',
                        'icon' => 'ti ti-prescription',
                        'route' => 'admin.prescriptions.index',
                        'active_patterns' => ['admin.prescriptions.*'],
                        'permission' => 'consultation.prescribe',
                    ],
                    [
                        'label' => 'Lab Requests',
                        'icon' => 'ti ti-test-pipe',
                        'route' => 'admin.lab.requests.index',
                        'active_patterns' => ['admin.lab.requests.*'],
                        'permission' => 'consultation.request_lab',
                    ],
                    [
                        'label' => 'Lab Results',
                        'icon' => 'ti ti-report-medical',
                        'route' => 'admin.lab.results.index',
                        'active_patterns' => ['admin.lab.results.*'],
                        'permission' => 'consultation.view_results',
                    ],
                    [
                        'label' => 'Procedures',
                        'icon' => 'ti ti-surgery',
                        'route' => 'admin.theatre.index',
                        'active_patterns' => ['admin.theatre.*'],
                        'permission' => 'consultation.request_procedure',
                    ],
                ],
            ],
            [
                'title' => 'Clinical Tools',
                'items' => [
                    [
                        'label' => 'ICD-10 Codes',
                        'icon' => 'ti ti-medical-cross',
                        'route' => 'admin.icd-codes.index',
                        'active_patterns' => ['admin.icd-codes.*'],
                        'permission' => 'icd.view',
                    ],
                    [
                        'label' => 'Procedure Catalogue',
                        'icon' => 'ti ti-list-details',
                        'route' => 'admin.procedure-catalogue.index',
                        'active_patterns' => ['admin.procedure-catalogue.*'],
                        'permission' => 'procedure.catalogue.view',
                    ],
                    [
                        'label' => 'Investigation Catalogue',
                        'icon' => 'ti ti-flask',
                        'route' => 'admin.investigation-catalogue.index',
                        'active_patterns' => ['admin.investigation-catalogue.*'],
                        'permission' => 'investigation.catalogue.view',
                    ],
                    [
                        'label' => 'Medical Patterns',
                        'icon' => 'ti ti-template',
                        'route' => 'admin.patterns.index',
                        'active_patterns' => ['admin.patterns.*'],
                        'permission' => 'consultations.view',
                    ],
                ],
            ],
            [
                'title' => 'Reports',
                'items' => [
                    [
                        'label' => 'Clinical Reports',
                        'icon' => 'ti ti-report',
                        'route' => 'admin.reports.visits',
                        'active_patterns' => ['admin.reports.*'],
                        'permission' => 'reports.view',
                    ],
                ],
            ],
            // [
            //     'title' => null,
            //     'items' => [
            //         [
            //             'label' => 'Notifications',
            //             'icon' => 'ti ti-bell',
            //             'route' => 'admin.notifications.index',
            //             'active_patterns' => ['admin.notifications.*'],
            //             'permission' => 'notifications.view',
            //             'badge' => $unreadNotifications > 0 ? $unreadNotifications : null,
            //             'badge_class' => 'badge bg-danger rounded-pill ms-auto',
            //         ],
            //     ],
            // ],
            [
                'title' => 'Profile',
                'items' => [
                    [
                        'label' => 'My Profile',
                        'icon' => 'ti ti-user-circle',
                        'route' => 'admin.profile',
                        'active_patterns' => ['admin.profile'],
                    ],
                ],
            ],
        ];
    }

    protected function filterSection(array $section, User $user, string $currentRouteName): ?array
    {
        $items = array_values(array_filter(array_map(
            fn (array $item) => $this->filterItem($item, $user, $currentRouteName),
            $section['items'] ?? [],
        )));

        if (empty($items)) {
            return null;
        }

        $section['items'] = $items;

        if (! empty($section['title'])) {
            $section['title'] = $this->translateLabel($section['title']);
        }

        return $section;
    }

    /**
     * Translate a menu label using a key derived from the English label
     * (lowercase, non-alphanumeric runs collapsed to '_'). Falls back to
     * the original label when no translation exists, so new menu entries
     * never break.
     */
    protected function translateLabel(string $label): string
    {
        $key = 'menu.' . trim(preg_replace('/[^a-z0-9]+/', '_', strtolower($label)), '_');

        return Lang::has($key) ? __($key) : $label;
    }

    protected function filterItem(array $item, User $user, string $currentRouteName): ?array
    {
        if (! $this->isVisible($item, $user)) {
            return null;
        }

        $children = array_values(array_filter(array_map(
            fn (array $child) => $this->filterItem($child, $user, $currentRouteName),
            $item['children'] ?? [],
        )));

        if (array_key_exists('children', $item)) {
            if (empty($children)) {
                return null;
            }

            $item['children'] = $children;
        }

        if (! empty($item['label'])) {
            $item['label'] = $this->translateLabel($item['label']);
        }

        $item['active'] = $this->isActive($item, $currentRouteName)
            || collect($item['children'] ?? [])->contains(fn (array $child) => $child['active'] ?? false);

        return $item;
    }

    protected function isVisible(array $item, User $user): bool
    {
        // Drop items whose named route doesn't exist in this installation
        if (! empty($item['route']) && ! Route::has($item['route'])) {
            return false;
        }

        if (! empty($item['module']) && ! $this->moduleService->enabled($item['module'])) {
            return false;
        }

        if (! empty($item['permission']) && ! $user->can($item['permission'])) {
            return false;
        }

        if (! empty($item['permissions_any']) && ! $user->canAny($item['permissions_any'])) {
            return false;
        }

        if (! empty($item['role']) && ! $user->hasRole($item['role'])) {
            return false;
        }

        if (! empty($item['roles_any']) && ! $user->hasAnyRole($item['roles_any'])) {
            return false;
        }

        return true;
    }

    protected function isActive(array $item, string $currentRouteName): bool
    {
        $patterns = $item['active_patterns'] ?? [];

        foreach ($patterns as $pattern) {
            if (Str::is($pattern, $currentRouteName)) {
                return true;
            }
        }

        return false;
    }
}
