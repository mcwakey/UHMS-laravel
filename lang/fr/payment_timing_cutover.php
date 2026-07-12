<?php

return [
    'title' => 'Bascule opérationnelle des paiements',
    'master_controls' => 'Contrôles principaux',
    'rollback_title' => 'Retour arrière immédiat',
    'eligible_operations' => 'Opérations éligibles',
    'no_operations' => 'Aucune opération n\'est éligible à la bascule typée.',
    'view_only' => 'Vous disposez d\'un accès en lecture seule.',
    'on' => 'Activé',
    'off' => 'Désactivé',
    'emergency_not_supported' => 'Urgence non prise en charge',
    'force_legacy_on' => 'Le forçage hérité par environnement est ACTIVÉ',
    'force_legacy_help' => 'Toutes les opérations de paiement suivent l\'autorité héritée, quelles que soient ces configurations, tant que le coupe-circuit d\'environnement n\'est pas désactivé.',
    'master_help' => 'Passer à Actif permet aux opérations configurées en Typé de renvoyer des décisions typées. Tout le reste reste sous autorité héritée.',
    'rollback_help' => 'Ramener immédiatement toutes les opérations de paiement à l\'autorité héritée. Les arrangements approuvés sont conservés.',

    'modes' => [
        'disabled' => 'Désactivé',
        'observe' => 'Observation uniquement',
        'active' => 'Actif',
    ],

    'labels' => [
        'configured_mode' => 'Mode configuré',
        'effective_mode' => 'Mode effectif',
        'force_legacy' => 'Forçage hérité par environnement',
        'failure_fallback' => 'Repli hérité en cas d\'échec',
        'supported_visit_types' => 'Types de visite pris en charge',
        'reason' => 'Motif de la bascule',
        'confirm_active' => 'Je confirme l\'activation de l\'application typée opérationnelle.',
        'acknowledge_compatibility' => 'Je reconnais le changement de compatibilité.',
    ],

    'actions' => [
        'save_master' => 'Enregistrer le mode principal',
        'save_operation' => 'Enregistrer',
        'rollback' => 'Ramener toutes les opérations à l\'hérité',
    ],

    'blockers' => [
        'force_legacy' => 'Forçage hérité actif',
        'master_not_active' => 'Mode principal non actif',
        'acknowledgement_missing' => 'Reconnaissance manquante',
    ],

    'reasons' => [
        'TYPED_PREPAYMENT_REQUIRED' => 'Le paiement est requis avant que ce service puisse se poursuivre.',
        'TYPED_PREPAYMENT_SETTLED' => 'Les exigences de règlement avant service sont satisfaites.',
        'TYPED_PAY_AFTER_SERVICES_ALLOWED' => 'Le paiement après les services est autorisé pour cette visite.',
        'TYPED_RUNNING_BILL_ALLOWED' => 'Cette visite est sur facture en cours ; le service peut se poursuivre.',
        'APPROVED_ARRANGEMENT_PREPAYMENT_REQUIRED' => 'Un arrangement approuvé exige un prépaiement avant ce service.',
        'APPROVED_ARRANGEMENT_DEFERRED_SETTLEMENT' => 'Un arrangement approuvé autorise un règlement différé.',
        'APPROVED_ARRANGEMENT_RUNNING_BILL' => 'Un arrangement approuvé autorise une facture en cours.',
        'APPROVED_ARRANGEMENT_INELIGIBLE' => 'L\'arrangement approuvé n\'est pas éligible opérationnellement.',
        'TYPED_OPERATION_UNSUPPORTED' => 'L\'application typée n\'est pas prise en charge pour cette opération.',
        'TYPED_EMERGENCY_FALLBACK' => 'Les soins d\'urgence suivent l\'autorité héritée.',
        'TYPED_FAILURE_LEGACY_FALLBACK' => 'Un problème technique est survenu ; l\'autorité héritée s\'applique.',
        'TYPED_LEGACY_OVERRIDE_CONFLICT' => 'Une dérogation héritée conflictuelle s\'applique ; l\'arrangement est ignoré.',
    ],

    'errors' => [
        'activate_not_permitted' => 'Vous n\'êtes pas autorisé à activer la bascule opérationnelle.',
        'confirmation_required' => 'Veuillez confirmer l\'activation.',
        'unknown_operation' => 'Opération inconnue.',
        'operation_not_typed_eligible' => 'Cette opération n\'est pas éligible à l\'application typée.',
        'acknowledgement_required' => 'Une reconnaissance de compatibilité est requise pour le mode typé.',
    ],

    'flash' => [
        'master_updated' => 'Mode de bascule principal mis à jour.',
        'operation_updated' => 'Mode de bascule de l\'opération mis à jour.',
        'rolled_back' => 'Toutes les opérations de paiement ramenées à l\'autorité héritée.',
    ],
];
