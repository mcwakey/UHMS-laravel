<?php

/*
| Libellés des types de département, indexés par la valeur App\Enums\DepartmentType.
| Résolus via DepartmentType::translatedLabel() → departments.types.{value}.
*/

return [
    'switch_department' => 'Changer de département',
    'current_department' => 'Département actuel',
    'primary_department' => 'Département principal',
    'available_departments' => 'Départements disponibles',

    'types' => [
        'consultation' => 'Consultation',
        'emergency' => 'Urgences',
        'investigation' => 'Analyses',
        'radiology' => 'Radiologie',
        'procedure' => 'Opération',
        'theatre' => 'Bloc opératoire',
        'treatment' => 'Soins',
        'nursing' => 'Soins infirmiers',
        'pharmacy' => 'Pharmacie',
        'inpatient' => 'Hospitalisation',
        'maternity' => 'Maternité',
        'blood_bank' => 'Banque de sang',
        'mortuary' => 'Morgue',
        'ambulance' => 'Ambulance',
        'records' => 'Dossiers médicaux',
        'finance' => 'Finances',
        'stores' => 'Magasin',
        'support' => 'Support',
        'administrative' => 'Administration',
    ],
];
