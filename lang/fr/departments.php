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

    // Noms des tableaux de bord par type de département.
    'dashboards' => [
        'consultation' => ['name' => 'Tableau de bord Consultation'],
        'emergency' => ['name' => 'Tableau de bord Urgences'],
        'investigation' => ['name' => 'Tableau de bord Analyses'],
        'radiology' => ['name' => 'Tableau de bord Radiologie'],
        'procedure' => ['name' => 'Tableau de bord Actes'],
        'theatre' => ['name' => 'Tableau de bord Bloc opératoire'],
        'treatment' => ['name' => 'Tableau de bord Soins'],
        'nursing' => ['name' => 'Tableau de bord Soins infirmiers'],
        'pharmacy' => ['name' => 'Tableau de bord Pharmacie'],
        'inpatient' => ['name' => 'Tableau de bord Hospitalisation'],
        'maternity' => ['name' => 'Tableau de bord Maternité'],
        'blood_bank' => ['name' => 'Tableau de bord Banque de sang'],
        'mortuary' => ['name' => 'Tableau de bord Morgue'],
        'ambulance' => ['name' => 'Tableau de bord Ambulance'],
        'records' => ['name' => 'Tableau de bord Dossiers'],
        'finance' => ['name' => 'Tableau de bord Finances'],
        'stores' => ['name' => 'Tableau de bord Magasin'],
        'support' => ['name' => 'Tableau de bord Support'],
        'administrative' => ['name' => 'Tableau de bord Administration'],
        'generic' => ['name' => 'Tableau de bord du département'],
    ],

    'dashboard' => [
        'subtitle' => ':department · :type',
        'welcome_user' => 'Bienvenue, :name',
        'welcome_to_department' => 'Bienvenue à :department',
        'scoped_to_department_name' => 'Données limitées à :department uniquement',
        'viewing_department_data_only' => 'Vous consultez uniquement les données de :department.',
        'viewing_as_department' => 'Affichage en tant que :department',
        'global_preview_mode' => 'Aperçu global',
        'no_department_assigned_dashboard' => 'Aucun département assigné — affichage d\'une vue générale.',
    ],

    'menu_profiles' => [
        'workbench' => 'Atelier :department',
        'command_center' => 'Centre de commande :department',
        'operations' => 'Opérations :department',
        'control_room' => 'Salle de contrôle :department',
        'inventory' => 'Inventaire :department',
        'records_office' => 'Bureau :department',
        'generic' => 'Tableau de bord :department',
    ],

    'families' => [
        'clinical_queue' => 'File clinique',
        'emergency_command' => 'Commande des urgences',
        'diagnostic_workbench' => 'Atelier de diagnostic',
        'imaging_workbench' => 'Atelier d\'imagerie',
        'surgery_board' => 'Tableau de chirurgie',
        'ward_board' => 'Tableau de service',
        'dispensing_stock' => 'Dispensation et stock',
        'finance_control' => 'Contrôle financier',
        'stores_inventory' => 'Inventaire du magasin',
        'records_office' => 'Bureau des dossiers',
        'generic_department' => 'Département',
    ],

    'sections' => [
        'samples' => 'Échantillons',
        'imaging_schedule' => 'Planning d\'imagerie',
        'dispensing_queue' => 'File de dispensation',
        'bed_occupancy' => 'Occupation des lits',
        'triage_status' => 'État du triage',
        'surgery_schedule' => 'Planning chirurgical',
        'stock_movements' => 'Mouvements de stock',
        'folder_requests' => 'Demandes de dossiers',
        'cashier_sessions' => 'Sessions de caisse',
        'priority_alerts' => 'Alertes prioritaires',
        'rapid_actions' => 'Actions rapides',
    ],
];
