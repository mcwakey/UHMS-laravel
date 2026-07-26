<?php

/*
 * Phase 14R.6 — disponibilité indicative, projection de synthèse et instantanés
 * de clôture. Parité récursive stricte avec le fichier en/.
 */

return [

    'readiness' => [
        'title' => 'Disponibilité maternité',
        'advisory_notice' => 'Indicatif uniquement — la clôture reste possible.',
        'record_in_maternity' => 'Saisissez les informations manquantes dans la maternité.',
        'statuses' => [
            'ready' => 'Prêt',
            'warning' => 'Avertissement indicatif',
            'unavailable' => 'Indisponible',
        ],
        'modes' => [
            'general' => 'Revue générale',
            'antenatal_review' => 'Revue prénatale',
            'labor_review' => 'Revue du travail',
            'postnatal_review' => 'Revue post-natale',
        ],
        'warnings' => [
            'context_confirmation_required' => 'Confirmez et liez d\'abord ce contexte de maternité.',
            'context_ambiguous' => 'Plusieurs profils de grossesse correspondent — choisissez-en un explicitement.',
            'context_invalid' => 'Le contexte de maternité lié est invalide.',
            'pregnancy_profile_missing' => 'Aucun profil de grossesse n\'est lié.',
            'anc_visit_not_recorded' => 'Aucune visite prénatale n\'est liée ou enregistrée.',
            'labor_episode_missing' => 'Aucun épisode de travail n\'est lié.',
            'labor_episode_profile_mismatch' => 'L\'épisode de travail lié ne correspond plus au profil de grossesse.',
            'postnatal_case_missing' => 'Aucun dossier post-natal n\'est lié.',
            'postnatal_referral_required' => 'Le dossier post-natal est signalé pour référence.',
            'postnatal_readiness_unavailable' => 'La disponibilité post-natale n\'est pas encore enregistrée.',
        ],
    ],

    'summary' => [
        'section_title' => 'Contexte de maternité',
        'current_record' => 'Dossier de maternité actuel',
        'completion_snapshot_title' => 'Contexte de maternité à la clôture de la consultation',
        'completion_snapshot' => 'Instantané de clôture',
        'snapshot_version' => 'Version de l\'instantané',
        'captured_at' => 'Capturé le',
        'captured_by' => 'Capturé par',
        'snapshot_hash' => 'Empreinte de l\'instantané',
        'hash_verified' => 'Empreinte vérifiée',
        'hash_mismatch' => 'Empreinte non concordante — ce dossier a été modifié après la capture',
        'no_snapshot_available' => 'Aucun instantané de clôture de maternité n\'a été capturé pour cette consultation.',
        'view_current_record' => 'Voir le dossier de maternité actuel',
        'historical_snapshots' => 'Instantanés de clôture historiques',
        'source_of_truth' => 'Source de vérité : maternité',
        'encounter_source' => 'Source de l\'épisode : consultation',
        'admission_owns_ward' => 'Le service et le lit sont gérés par l\'hospitalisation.',
        'sections' => [
            'pregnancy' => 'Grossesse',
            'anc' => 'Prénatal',
            'labor' => 'Travail',
            'delivery' => 'Accouchement',
            'newborn' => 'Nouveau-né',
            'postnatal' => 'Post-natal',
            'admission' => 'Contexte d\'hospitalisation',
        ],
    ],

    'warnings' => [
        'confirm_context_first' => 'Confirmez et liez d\'abord ce contexte de maternité.',
        'context_ambiguous' => 'Plusieurs profils de grossesse correspondent — choisissez-en un explicitement.',
        'context_invalid' => 'Le contexte de maternité lié est invalide.',
        'context_warnings' => 'Le contexte de maternité lié a signalé des avertissements.',
    ],

];
