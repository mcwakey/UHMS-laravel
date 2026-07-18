<?php

return [
    'workspace' => ['title' => 'Espace d’hospitalisation'],
    'unauthorized' => 'L’espace d’hospitalisation est disponible uniquement lorsqu’un service d’hospitalisation est actif.',
    'breadcrumbs' => [
        'inpatient' => 'Hospitalisation', 'admissions' => 'Admissions', 'wards' => 'Services', 'beds' => 'Lits',
        'patients' => 'Patients', 'visits' => 'Visites', 'rounds' => 'Visites cliniques', 'sessions' => 'Sessions d’hospitalisation',
        'vitals' => 'Signes vitaux', 'tasks' => 'Tâches infirmières', 'medications' => 'Médicaments', 'treatments' => 'Traitements',
        'procedures' => 'Procédures', 'investigations' => 'Examens', 'handoffs' => 'Transmissions', 'transfers' => 'Transferts',
        'discharges' => 'Sorties', 'readmissions' => 'Réadmission', 'reports' => 'Rapports', 'details' => 'Détails', 'create' => 'Créer', 'edit' => 'Modifier',
    ],
    'menu' => [
        'command' => 'Commande hospitalisation', 'dashboard' => 'Tableau de bord', 'active_admissions' => 'Admissions actives',
        'pending_admissions' => 'Admissions en attente', 'discharged' => 'Sorties aujourd’hui', 'wards_beds' => 'Services et lits',
        'ward_overview' => 'Vue des services', 'bed_availability' => 'Disponibilité des lits', 'patient_care' => 'Soins aux patients',
        'patients' => 'Patients hospitalisés', 'visits' => 'Visites hospitalières', 'rounds' => 'Visites cliniques',
        'sessions' => 'Sessions d’hospitalisation', 'vitals' => 'Signes vitaux', 'tasks' => 'Tâches infirmières',
        'medication_services' => 'Médicaments et services', 'medications' => 'Administration des médicaments',
        'treatments' => 'Traitements', 'investigations' => 'Examens en attente', 'procedures' => 'Procédures',
        'consumables' => 'Consommables du service', 'coordination' => 'Coordination', 'handoffs' => 'Transmissions de garde',
        'transfers' => 'Transferts', 'discharge' => 'Sortie', 'discharge_readiness' => 'Préparation à la sortie',
        'discharges' => 'Sorties terminées', 'readmissions' => 'Réadmissions', 'reports' => 'Rapports d’hospitalisation', 'inpatient_reports' => 'Rapport d’admission',
        'general' => 'Général', 'notifications' => 'Notifications', 'profile' => 'Mon profil',
    ],
    'states' => [
        'pending_acceptance' => 'En attente d’acceptation', 'awaiting_bed' => 'En attente de lit', 'admitted' => 'Admis',
        'active' => 'Actif', 'on_leave' => 'En permission', 'transfer_pending' => 'Transfert en attente',
        'discharge_planned' => 'Sortie planifiée', 'clearance_pending' => 'Validation en attente',
        'ready_for_discharge' => 'Prêt pour la sortie', 'discharged' => 'Sorti', 'readmitted' => 'Réadmis',
    ],
    'clinical' => [
        'assessment' => 'Évaluation infirmière', 'care_plan' => 'Plan de soins', 'intake_output' => 'Entrées et sorties',
        'observation' => 'Observation du patient', 'medication_due' => 'Médicament à administrer', 'medication_overdue' => 'Médicament en retard',
        'treatment_pending' => 'Traitement en attente', 'investigation_pending' => 'Examen en attente',
        'session_reopen' => 'Rouvrir la session', 'reopen_reason' => 'Motif de réouverture',
    ],
    'empty' => [
        'admissions' => 'Aucune admission ne correspond aux filtres.', 'beds' => 'Aucun lit ne correspond aux filtres.',
        'tasks' => 'Aucune tâche infirmière en attente.', 'medications' => 'Aucun médicament n’est à administrer.',
        'investigations' => 'Aucun examen n’attend de suivi.', 'handoffs' => 'Aucune transmission en attente.',
    ],
    'actions' => [
        'accept_admission' => 'Accepter l’admission', 'assign_bed' => 'Attribuer un lit', 'transfer' => 'Transférer le patient',
        'record_vitals' => 'Enregistrer les signes vitaux', 'start_round' => 'Commencer la visite clinique', 'complete_task' => 'Terminer la tâche',
        'review_clearances' => 'Vérifier les validations', 'discharge' => 'Faire sortir le patient', 'readmit' => 'Réadmettre le patient', 'view_admission' => 'Voir l’admission',
    ],
    'readmission' => [
        'title' => 'Réadmettre le patient', 'confirm' => 'Confirmer la réadmission',
        'history_notice' => 'La sortie précédente, les dossiers cliniques, les factures et les paiements resteront conservés.',
        'previous_discharge' => 'Sortie précédente', 'reason' => 'Motif de réadmission',
        'submit' => 'Confirmer la réadmission', 'success' => 'Le patient a été réadmis pour poursuivre les soins hospitaliers.',
    ],
    'alerts' => [
        'allergy' => 'Alerte allergie', 'critical_vitals' => 'Signes vitaux critiques', 'overdue_task' => 'Tâche infirmière en retard',
        'missing_clearance' => 'Une validation obligatoire de sortie est incomplète.', 'bed_unavailable' => 'Le lit sélectionné n’est plus disponible.',
    ],
    'reports' => [
        'admissions' => 'Rapport des admissions', 'occupancy' => 'Rapport d’occupation des lits', 'census' => 'Recensement du service',
        'length_of_stay' => 'Rapport de durée de séjour', 'nursing' => 'Rapport d’activité infirmière',
        'medication' => 'Rapport d’administration des médicaments', 'discharges' => 'Rapport des sorties', 'readmissions' => 'Rapport des réadmissions',
    ],
];
