<?php

return [
    // Phase 14R.2 — passerelle Consultation ↔ Maternité (messages de domaine).
    'maternity_context' => 'Contexte de maternité',

    'context_types' => [
        'pregnancy_profile' => 'Profil de grossesse',
        'maternity_case' => 'Dossier de maternité',
        'anc_visit' => 'Visite prénatale',
        'labor' => 'Épisode de travail',
        'delivery' => 'Dossier d\'accouchement',
        'newborn' => 'Dossier du nouveau-né',
        'postnatal' => 'Dossier postnatal',
    ],

    'link_roles' => [
        'primary' => 'Principal',
        'reviewed' => 'Consulté',
        'created' => 'Créé',
        'handoff' => 'Transfert',
        'historical' => 'Historique',
    ],

    'resolution_sources' => [
        'explicit' => 'Lien explicite',
        'visit' => 'Contexte de la même visite',
        'admission' => 'Contexte de la même admission',
        'active_profile' => 'Contexte de grossesse active',
        'none' => 'Aucun contexte de maternité',
    ],

    'statuses' => [
        'resolved' => 'Contexte de maternité résolu',
        'ambiguous' => 'Contexte de maternité ambigu',
        'none' => 'Aucun contexte de maternité',
        'invalid' => 'Contexte de maternité invalide',
    ],

    'context_linked' => 'Contexte de maternité lié.',
    'context_relinked' => 'Contexte de maternité relié.',
    'context_unlinked' => 'Contexte de maternité dissocié.',

    'link_reason' => 'Motif de la liaison',
    'unlink_reason' => 'Motif de la dissociation',

    'no_maternity_context' => 'Aucun contexte de maternité n\'est lié à cette consultation.',
    'ambiguous_maternity_context' => 'Le contexte de maternité est ambigu et doit être sélectionné explicitement.',
    'multiple_active_pregnancy_profiles' => 'Cette patiente a plusieurs profils de grossesse actifs.',

    'errors' => [
        'unsupported_target' => 'Cible de maternité non prise en charge : :target.',
        'invalid_target' => 'Le dossier de maternité n\'a pas pu être validé.',
        'patient_mismatch' => 'Le dossier de maternité appartient à une autre patiente.',
        'inconsistent_context' => 'La chaîne du dossier de maternité est incohérente et ne peut pas être liée.',
        'relink_required' => 'Un autre dossier est déjà lié pour ce contexte. Utilisez la reliaison avec un motif.',
        'reason_required' => 'Un motif est requis.',
        'link_not_found' => 'Aucun lien de maternité actif n\'a été trouvé pour ce contexte.',
    ],

    // ── Phase 14R.3 — espace Obstétrique adapté au stade ──────────────────
    'dating_methods' => [
        'lmp' => 'DDR',
        'early_ultrasound' => 'Échographie précoce',
        'late_ultrasound' => 'Échographie tardive',
        'assisted_reproduction' => 'Procréation assistée',
        'clinical_estimate' => 'Estimation clinique',
        'unknown' => 'Inconnue',
    ],

    'ribbon' => [
        'title' => 'Contexte de maternité',
        'explicitly_linked' => 'Lié explicitement',
        'suggested' => 'Contexte de maternité suggéré',
        'source_maternity' => 'Source : Maternité',
        'source_same_visit' => 'Source : même visite',
        'source_same_admission' => 'Source : même admission',
        'source_active_profile' => 'Source : profil de grossesse actif',
        'gestational_age' => 'Âge gestationnel',
        'gestational_age_source' => 'Source de l\'ÂG',
        'dating_method' => 'Méthode de datation',
        'edd' => 'DPA',
        'risk_level' => 'Niveau de risque',
        'latest_anc' => 'Dernière CPN',
        'next_anc' => 'Prochaine CPN',
        'admission' => 'Admission',
        'ward_bed' => 'Service / Lit',
        'labor_stage' => 'Stade du travail',
        'delivery_status' => 'Accouchement',
        'newborn_records' => 'Dossiers nouveau-nés',
        'postnatal_status' => 'Postnatal',
        'workspace_disabled' => 'L\'intégration de l\'espace maternité est désactivée.',
        'pilot_mode' => 'Mode pilote — projections affichées, les champs existants restent modifiables.',
    ],

    'panel' => [
        'title' => 'Contexte de maternité',
        'pregnancy' => 'Grossesse',
        'anc' => 'CPN',
        'labor_delivery' => 'Travail et accouchement',
        'newborn' => 'Nouveau-né',
        'postnatal' => 'Postnatal',
        'pregnancy_summary' => 'Résumé de la grossesse',
        'anc_summary' => 'Résumé CPN',
        'labor_summary' => 'Résumé du travail',
        'delivery_summary' => 'Résumé de l\'accouchement',
        'newborn_summary' => 'Résumé du nouveau-né',
        'postnatal_summary' => 'Résumé postnatal',
        'legacy_entry' => 'Saisie de consultation héritée',
        'legacy_entry_hint' => 'Enregistrée avant que la maternité ne devienne la source de vérité. Conservée pour audit ; non utilisée comme vérité clinique actuelle.',
        'read_only_reason' => 'Lecture seule : la maternité fait foi.',
    ],

    'actions' => [
        'confirm_and_link' => 'Confirmer et lier',
        'select_profile' => 'Sélectionner un profil de grossesse',
        'link_profile' => 'Lier un profil de grossesse',
        'create_profile' => 'Créer un profil de grossesse',
        'link_or_create_profile' => 'Lier ou créer un profil de grossesse',
        'relink_profile' => 'Relier un autre profil de grossesse',
        'unlink_profile' => 'Dissocier le profil de grossesse',
        'record_anc' => 'Enregistrer une visite CPN',
        'start_labor' => 'Démarrer un épisode de travail',
        'open_labor' => 'Ouvrir l\'espace travail',
        'open_maternity' => 'Ouvrir l\'espace maternité',
        'view_delivery' => 'Voir le dossier d\'accouchement',
        'view_newborn' => 'Voir les dossiers nouveau-nés',
        'view_postnatal' => 'Ouvrir l\'espace postnatal',
    ],

    'messages' => [
        'profile_created' => 'Profil de grossesse créé et lié à cette consultation.',
        'profile_linked' => 'Profil de grossesse lié à cette consultation.',
        'context_confirmed' => 'Contexte de maternité confirmé et lié.',
        'anc_recorded' => 'Visite CPN enregistrée et liée à cette consultation.',
        'labor_started' => 'Épisode de travail démarré et lié à cette consultation.',
        'labor_opened' => 'Un épisode de travail actif existe déjà.',
        'explicit_link_required' => 'Confirmez le contexte de maternité avant d\'enregistrer des données de maternité.',
        'maternity_permission_required' => 'Vous devez également disposer de la permission maternité correspondante.',
        'workspace_disabled' => 'L\'intégration de l\'espace maternité n\'est pas activée.',
        'invalid_context_actions_disabled' => 'Les actions de maternité sont désactivées tant que le contexte invalide n\'est pas corrigé.',
    ],

    'write_guard' => [
        'field_blocked' => ':field est géré dans le dossier de maternité et ne peut pas être enregistré ici.',
        'section_read_only' => 'Ces valeurs sont en lecture seule : la maternité fait foi.',
        'guard_disabled' => 'Mode pilote : les champs de maternité restent modifiables dans cette consultation.',
    ],

    'rollout' => [
        'pilot_title' => 'Mode pilote du contexte de maternité',
        'pilot_hint' => 'Les projections sources sont visibles ; les anciens champs obstétriques restent modifiables.',
        'guarded_title' => 'La maternité fait foi pour les champs de grossesse liés',
        'guarded_hint' => 'Les champs appartenant à la maternité sont en lecture seule ici et gérés dans le dossier de maternité.',
        'completed_review' => 'Cette consultation est terminée. Le contexte de maternité peut être consulté et corrigé, mais les nouveaux dossiers cliniques doivent être créés ailleurs.',
        'context_linking_available' => 'La liaison du contexte reste disponible sur les consultations terminées.',
        'open_existing_record' => 'Ouvrir le dossier de maternité existant',
        'start_new_consultation' => 'Démarrez une nouvelle consultation pour enregistrer des données cliniques.',
        'return_to_consultation' => 'Retour à la consultation',
        'unsaved_changes' => 'Vous avez des modifications de consultation non enregistrées.',
        'blocked_completed' => 'Cette action n\'est pas disponible sur une consultation terminée.',
        'blocked_paused' => 'Cette action n\'est pas disponible pendant que la consultation est en pause.',
    ],

    'warnings' => [
        'multiple_active_profiles' => 'Cette patiente a plusieurs profils de grossesse actifs. Sélectionnez-en un explicitement.',
        'multiple_candidate_profiles' => 'Plusieurs profils de grossesse correspondent à ce contexte. Sélectionnez-en un explicitement.',
        'link_target_missing' => 'Le dossier :context lié est introuvable.',
        'link_patient_mismatch' => 'Le dossier :context lié appartient à une autre patiente.',
        'source_of_truth' => 'Les dossiers de maternité font foi pour cette information.',
        'duplicate_data' => 'Cette information est déjà enregistrée dans le dossier de maternité.',
    ],
];
