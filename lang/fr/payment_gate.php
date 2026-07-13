<?php

return [
    'title' => 'Application des paiements par departement',
    'subtitle' => 'Consultez comment les operations de flux sont reliees au calendrier de paiement. Les blocages de paiement actifs utilisent les politiques typees de visite quand elles sont activees.',

    // Column / field labels
    'operation' => 'Operation',
    'workflow_stage' => 'Etape du flux',
    'department' => 'Departement',
    'current_status' => 'Statut actuel en production',
    'current_behaviour' => 'Comportement d\'application actuel',
    'configured_mode' => 'Mode configure',
    'missing_billing_context' => 'Contexte de facturation manquant',
    'visit_context_rule' => 'Regle de contexte de visite',
    'override_scope_label' => 'Portee de la derogation',
    'emergency_exempt' => 'Exempte en urgence',
    'inpatient_exempt' => 'Exempte en hospitalisation',
    'typed_enforcement_eligibility' => 'Eligibilite a l\'application typee',
    'legacy_behaviour' => 'Comportement historique',
    'eligibility_status' => 'Eligibilite',
    'compatibility_status' => 'Compatibilite',

    // Safety indicators
    'currently_wired' => 'Appel actif',
    'currently_unwired' => 'Aucun appel de blocage actif',
    'existing_hard_gate' => 'Appel de blocage de paiement',
    'display_readiness_only' => 'Affichage / preparation uniquement',
    'compatibility_specific_rule' => 'Regle de compatibilite specifique',

    // Notices / actions
    'read_only_notice' => 'Les lignes marquees comme appels actifs sont des points de controle en production. Leur comportement de paiement est unifie par les politiques de calendrier de paiement.',
    'not_operational_notice' => 'Les politiques de calendrier de paiement controlent maintenant chaque appel actif de blocage de paiement. Les lignes sans appel actif sont seulement des notes de couverture et ne bloquent pas les patients.',
    'configuration_not_operational' => 'Note de couverture uniquement',
    'save' => 'Enregistrer',
    'updated_successfully' => 'Parametres d\'application des paiements par departement mis a jour.',
    'no_operations' => 'Aucune operation n\'est enregistree.',
    'yes' => 'Oui',
    'no' => 'Non',
    'not_applicable' => 'Non applicable',
    'eligible' => 'Eligible',
    'not_eligible' => 'Non eligible',

    'modes' => [
        'disabled' => 'Desactive',
        'observe' => 'Observation uniquement',
        'legacy' => 'Metadonnees de compatibilite',
        'typed' => 'Politique de paiement typee',
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
        'department_service_or_item' => 'Departement, service ou ligne',
        'visit_wide' => 'Toute la visite',
        'preserve_legacy' => 'Conserver le comportement existant',
    ],

    'eligibility' => [
        'eligible' => 'Eligible',
        'ineligible_unwired' => 'Aucun appel de blocage actif',
        'ineligible_missing_stage' => 'Non eligible - etape manquante',
        'ineligible_missing_invoice_resolution' => 'Non eligible - resolution de facture manquante',
        'ineligible_emergency_boundary' => 'Non eligible - limite d\'urgence manquante',
        'ineligible_compatibility_rule' => 'Non eligible - regle de compatibilite',
        'ineligible_unapproved_policy' => 'Non eligible - politique non approuvee',
    ],

    'compatibility' => [
        'compatible' => 'Compatible',
        'configuration_non_operational' => 'Note de couverture',
        'legacy_hard_gate_protected' => 'Unifie par la politique de calendrier de paiement',
        'typed_cutover_not_ready' => 'Metadonnees de politique typee',
        'unwired_operation' => 'Aucun appel de blocage actif',
        'emergency_boundary_missing' => 'Limite d\'urgence manquante',
        'invoice_resolution_missing' => 'Resolution de facture manquante',
        'compatibility_rule_conflict' => 'Conflit de regle de compatibilite',
    ],

    'family' => [
        'consultation' => 'Consultation',
        'laboratory' => 'Laboratoire',
        'pharmacy' => 'Pharmacie',
        'investigation' => 'Examens et radiologie',
        'procedure' => 'Procedures',
        'service' => 'Prestation de services',
        'theatre' => 'Bloc operatoire',
        'treatment' => 'Traitement',
        'nursing' => 'Soins infirmiers',
        'blood_bank' => 'Banque de sang',
        'ambulance' => 'Ambulance',
        'other' => 'Autre',
    ],

    'errors' => [
        'duplicate_operation' => 'Cette operation a ete soumise plusieurs fois.',
        'unknown_operation' => 'Code d\'operation inconnu.',
        'hard_gate_read_only' => 'Cette operation est un appel actif de blocage de paiement et elle est controlee centralement par les politiques de calendrier de paiement.',
    ],
];
