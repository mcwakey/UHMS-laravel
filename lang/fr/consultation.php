<?php

return [
    'safety' => [
        'prescription_warning' => 'Alerte de sécurité de l’ordonnance',
        'override_required' => 'Examinez les alertes de prescription et saisissez un motif de dérogation pour continuer.',
        'override_reason' => 'Motif de dérogation',
        'allergy_conflict' => 'Cette ordonnance peut entrer en conflit avec une allergie enregistrée pour le patient.',
        'duplicate_active_medication' => 'Ce patient a déjà une ordonnance active ou récente pour ce médicament.',
        'missing_diagnosis' => 'Ajoutez un diagnostic avant de prescrire.',
        'unusual_dose_unverified' => 'La dose n’a pas pu être vérifiée. Veuillez la contrôler avant de continuer.',
        'missing_required_field' => 'Complétez tous les champs obligatoires de l’ordonnance avant de continuer.',
    ],
    'completion' => [
        'ready' => 'Prêt pour clôture',
        'not_ready' => 'Pas prêt pour clôture',
        'missing_requirements' => 'Complétez les éléments de consultation requis avant de clôturer cette séance.',
        'blocked' => 'Clôture de consultation bloquée jusqu’à l’enregistrement des éléments cliniques requis.',
        'override_reason' => 'Motif de dérogation de clôture',
        'readiness_title' => 'Préparation à la clôture',
        'requirement' => [
            'complaint' => 'Motif de consultation ou raison de visite',
            'examination' => 'Constatations d’examen',
            'diagnosis' => 'Diagnostic',
            'plan_or_disposition' => 'Plan de traitement ou note de disposition',
        ],
    ],
];
