<?php

/* Phase 14R.6 — politique de déduplication de facturation (indicative). */

return [
    'title' => 'Politique de déduplication de facturation',
    'posting_unavailable' => 'La comptabilisation reste indisponible.',
    'base_encounter_charge' => 'Frais de base de l\'épisode',
    'event_specific_consultation_charge' => 'Frais de consultation spécifiques à l\'acte',
    'maternity_event_charge' => 'Frais d\'acte de maternité',
    'consultation_source_suppressed' => 'Source consultation supprimée',
    'maternity_source_suppressed' => 'Source maternité supprimée',

    'policies' => [
        'consultation_only' => 'Consultation uniquement',
        'maternity_event_only' => 'Acte de maternité uniquement',
        'both_when_configured' => 'Les deux si configuré',
        'manual_selection' => 'Sélection manuelle',
    ],

    'statuses' => [
        'policy_disabled' => 'Politique de facturation désactivée',
        'no_conflict' => 'Aucun conflit',
        'duplicate_source_suppressed' => 'Source en double supprimée',
        'both_disabled' => 'Les deux sources sont désactivées',
        'manual_review_required' => 'Examen manuel requis',
    ],

    'warnings' => [
        'event_specific_duplicate_risk' => 'Risque de doublon spécifique à l\'acte',
        'both_disabled' => 'La facturation des deux sources est configurée mais globalement désactivée.',
        'manual_selection_disabled' => 'Une sélection manuelle est requise mais globalement désactivée.',
        'missing_source_identity' => 'Le dossier source n\'a pas d\'identité de facturation fiable.',
        'ambiguous_legacy_source' => 'La source de maternité héritée ne peut pas être interprétée automatiquement.',
    ],
];
