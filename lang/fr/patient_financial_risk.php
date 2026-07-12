<?php

return [
    'title' => 'Risque financier',
    'section_title' => 'Risque financier du patient',
    'worklist_title' => 'Liste de travail — risque financier',
    'report_title' => 'Rapport sur le risque financier',
    'subtitle' => 'Classification administrative contrôlée de la flexibilité de paiement. Ceci n\'est pas une décision de paiement.',
    'empty_state' => 'Aucune classification de risque financier active pour ce patient.',
    'no_results' => 'Aucun profil de risque financier ne correspond aux filtres sélectionnés.',
    'not_authorised' => 'Vous n\'êtes pas autorisé à consulter les informations de risque financier.',

    'fields' => [
        'risk_level' => 'Niveau de risque',
        'primary_reason' => 'Motif principal',
        'reason_details' => 'Détails du motif',
        'credit_limit' => 'Limite de crédit',
        'status' => 'Statut',
        'effective_from' => 'Date d\'effet',
        'review_due_at' => 'Révision prévue',
        'expires_at' => 'Date d\'expiration',
        'reference' => 'Référence',
        'set_by' => 'Défini par',
        'reviewed_by' => 'Dernière révision par',
        'reviewed_at' => 'Révisé le',
        'cleared_by' => 'Levé par',
        'last_updated' => 'Dernière mise à jour',
        'patient' => 'Patient',
    ],

    'levels' => [
        'normal' => [
            'label' => 'Normal',
            'description' => 'Aucune restriction financière active n\'est enregistrée.',
        ],
        'watchlist' => [
            'label' => 'Sous surveillance',
            'description' => 'Nécessite une révision financière supplémentaire mais la flexibilité de paiement n\'est pas automatiquement refusée.',
        ],
        'high_risk' => [
            'label' => 'Risque élevé',
            'description' => 'Exposition financière importante nécessitant une approbation contrôlée avant d\'accorder un crédit dans une phase ultérieure.',
        ],
        'blocked_credit' => [
            'label' => 'Crédit bloqué',
            'description' => 'Les nouveaux crédits ou paiements différés sont formellement restreints, sous réserve d\'une dérogation autorisée dans une phase ultérieure.',
        ],
    ],

    'reasons' => [
        'previous_unpaid_visits' => 'Visites impayées antérieures',
        'repeated_abandoned_invoices' => 'Factures abandonnées à répétition',
        'credit_limit_exceeded' => 'Limite de crédit dépassée',
        'invalid_corporate_guarantee' => 'Garantie d\'entreprise invalide',
        'insurance_eligibility_unresolved' => 'Éligibilité d\'assurance non résolue',
        'payment_commitment_breached' => 'Engagement de paiement non respecté',
        'management_decision' => 'Décision de la direction',
        'other' => 'Autre',
    ],

    'statuses' => [
        'active' => 'Actif',
        'under_review' => 'En révision',
        'suspended' => 'Suspendu',
        'cleared' => 'Levé',
        'expired' => 'Expiré',
    ],

    'events' => [
        'created' => 'Créé',
        'updated' => 'Mis à jour',
        'submitted_for_review' => 'Soumis pour révision',
        'review_completed' => 'Révision terminée',
        'suspended' => 'Suspendu',
        'reactivated' => 'Réactivé',
        'cleared' => 'Levé',
        'expired' => 'Expiré',
    ],

    'actions' => [
        'classify' => 'Classifier le patient',
        'edit' => 'Modifier la classification',
        'submit_review' => 'Soumettre pour révision',
        'complete_review' => 'Terminer la révision',
        'suspend' => 'Suspendre',
        'reactivate' => 'Réactiver',
        'clear' => 'Lever la restriction',
        'view_history' => 'Voir l\'historique',
        'save' => 'Enregistrer',
        'cancel' => 'Annuler',
        'confirm' => 'Confirmer',
        'export' => 'Exporter CSV',
        'open_worklist' => 'Ouvrir la liste de travail',
    ],

    'badges' => [
        'review_overdue' => 'Révision en retard',
        'expires_soon' => 'Expire bientôt',
        'finance_review_required' => 'Révision financière requise',
    ],

    'history' => [
        'title' => 'Historique du risque financier',
        'datetime' => 'Date / Heure',
        'event' => 'Événement',
        'change' => 'Changement',
        'performed_by' => 'Effectué par',
        'reason' => 'Motif',
        'empty' => 'Aucun historique enregistré.',
        'from_to' => ':from → :to',
        'system' => 'Système',
    ],

    'filters' => [
        'search' => 'Rechercher un patient',
        'level' => 'Niveau de risque',
        'status' => 'Statut',
        'review_due' => 'À réviser',
        'expired' => 'Expiré',
        'restriction' => 'Restriction active',
        'all' => 'Tous',
        'apply' => 'Appliquer',
        'reset' => 'Réinitialiser',
    ],

    'report' => [
        'active_watchlist' => 'Patients sous surveillance actifs',
        'active_high_risk' => 'Patients à risque élevé actifs',
        'active_blocked_credit' => 'Patients à crédit bloqué actifs',
        'due_for_review' => 'Profils à réviser',
        'expiring_soon' => 'Profils expirant bientôt',
        'cleared_this_month' => 'Levés ce mois-ci',
        'created_this_month' => 'Créés ce mois-ci',
    ],

    'export' => [
        'patient_number' => 'Numéro de patient',
        'patient_name' => 'Nom du patient',
    ],

    'form' => [
        'classify_heading' => 'Classifier le risque financier du patient',
        'edit_heading' => 'Modifier la classification du risque financier',
        'reason_placeholder' => 'Fournir un contexte justificatif',
        'clearance_reason' => 'Motif de la levée',
        'suspend_reason' => 'Motif de la suspension',
        'reactivate_reason' => 'Motif de la réactivation',
        'review_note' => 'Note de révision (facultatif)',
        'credit_limit_help' => 'Informatif uniquement dans cette phase — non appliqué aux factures.',
    ],

    'validation' => [
        'details_required_for_other' => 'Les détails du motif sont requis lorsque le motif est « Autre ».',
        'details_or_reference_required_for_management' => 'Une décision de la direction nécessite des détails ou une référence.',
    ],

    'flash' => [
        'classified' => 'Classification de risque financier enregistrée.',
        'updated' => 'Classification de risque financier mise à jour.',
        'submitted_for_review' => 'Profil soumis pour révision.',
        'review_completed' => 'Révision terminée.',
        'suspended' => 'Profil suspendu.',
        'reactivated' => 'Profil réactivé.',
        'cleared' => 'Restriction de risque financier levée.',
        'invalid_transition' => 'Ce changement de statut n\'est pas autorisé.',
    ],
];
