<?php

return [
    'title' => 'Application des paiements par département',
    'subtitle' => 'Configurez la relation de chaque opération de flux avec l\'application des paiements. Cette configuration n\'est pas encore opérationnelle.',

    // Column / field labels
    'operation' => 'Opération',
    'workflow_stage' => 'Étape du flux',
    'department' => 'Département',
    'current_status' => 'Statut actuel en production',
    'current_behaviour' => 'Comportement d\'application actuel',
    'configured_mode' => 'Mode configuré',
    'missing_billing_context' => 'Contexte de facturation manquant',
    'visit_context_rule' => 'Règle de contexte de visite',
    'override_scope_label' => 'Portée de la dérogation',
    'emergency_exempt' => 'Exempté en urgence',
    'inpatient_exempt' => 'Exempté en hospitalisation',
    'typed_enforcement_eligibility' => 'Éligibilité à l\'application typée',
    'legacy_behaviour' => 'Comportement hérité',
    'eligibility_status' => 'Éligibilité',
    'compatibility_status' => 'Compatibilité',

    // Safety indicators
    'currently_wired' => 'Actuellement câblé',
    'currently_unwired' => 'Actuellement non câblé',
    'existing_hard_gate' => 'Blocage strict existant',
    'display_readiness_only' => 'Affichage / préparation uniquement',
    'compatibility_specific_rule' => 'Règle de compatibilité spécifique',

    // Notices / actions
    'read_only_notice' => 'Les blocages stricts existants sont en lecture seule dans cette phase afin que leur protection actuelle ne puisse pas être assouplie par inadvertance.',
    'not_operational_notice' => 'La configuration seule n\'active aucun blocage en production. Une phase d\'implémentation future est requise pour câbler les opérations approuvées.',
    'configuration_not_operational' => 'La configuration n\'est pas encore opérationnelle',
    'save' => 'Enregistrer',
    'updated_successfully' => 'Paramètres d\'application des paiements par département mis à jour.',
    'no_operations' => 'Aucune opération n\'est enregistrée.',
    'yes' => 'Oui',
    'no' => 'Non',
    'not_applicable' => 'Non applicable',
    'eligible' => 'Éligible',
    'not_eligible' => 'Non éligible',

    'modes' => [
        'disabled' => 'Désactivé',
        'observe' => 'Observation uniquement',
        'legacy' => 'Utiliser le blocage hérité existant',
        'typed' => 'Application typée',
    ],

    'missing_context' => [
        'preserve_legacy' => 'Conserver le comportement existant',
        'allow' => 'Autoriser',
        'block' => 'Bloquer',
        'not_applicable' => 'Non applicable',
    ],

    'visit_context' => [
        'use_visit_policy' => 'Utiliser la politique de visite',
        'always_running_bill' => 'Toujours facture en cours',
        'preserve_legacy' => 'Conserver le comportement existant',
        'not_applicable' => 'Non applicable',
    ],

    'override_scope' => [
        'none' => 'Aucune',
        'invoice_item_only' => 'Ligne de facture uniquement',
        'service_or_item' => 'Service ou ligne',
        'department_service_or_item' => 'Département, service ou ligne',
        'visit_wide' => 'Toute la visite',
        'preserve_legacy' => 'Conserver le comportement existant',
    ],

    'eligibility' => [
        'eligible' => 'Éligible',
        'ineligible_unwired' => 'Non éligible — non câblé',
        'ineligible_missing_stage' => 'Non éligible — étape manquante',
        'ineligible_missing_invoice_resolution' => 'Non éligible — résolution de facture manquante',
        'ineligible_emergency_boundary' => 'Non éligible — limite d\'urgence manquante',
        'ineligible_compatibility_rule' => 'Non éligible — règle de compatibilité',
        'ineligible_unapproved_policy' => 'Non éligible — politique non approuvée',
    ],

    'compatibility' => [
        'compatible' => 'Compatible',
        'configuration_non_operational' => 'Configuration non opérationnelle',
        'legacy_hard_gate_protected' => 'Blocage strict hérité protégé',
        'typed_cutover_not_ready' => 'Bascule typée non prête',
        'unwired_operation' => 'Opération non câblée',
        'emergency_boundary_missing' => 'Limite d\'urgence manquante',
        'invoice_resolution_missing' => 'Résolution de facture manquante',
        'compatibility_rule_conflict' => 'Conflit de règle de compatibilité',
    ],

    'family' => [
        'consultation' => 'Consultation',
        'laboratory' => 'Laboratoire',
        'pharmacy' => 'Pharmacie',
        'investigation' => 'Examens et radiologie',
        'procedure' => 'Procédures',
        'service' => 'Prestation de services',
        'theatre' => 'Bloc opératoire',
        'treatment' => 'Traitement',
        'nursing' => 'Soins infirmiers',
        'blood_bank' => 'Banque de sang',
        'ambulance' => 'Ambulance',
        'other' => 'Autre',
    ],

    'errors' => [
        'duplicate_operation' => 'Cette opération a été soumise plusieurs fois.',
        'unknown_operation' => 'Code d\'opération inconnu.',
        'hard_gate_read_only' => 'Cette opération est un blocage strict existant et est en lecture seule dans cette phase.',
    ],
];
