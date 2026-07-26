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
        'historical_label' => 'Ce sont les valeurs enregistrées à la clôture de la consultation, et non les données de maternité actuelles.',
        'abortions' => 'Avortements',
        'living_children' => 'Enfants vivants',
        'blood_pressure' => 'Tension artérielle',
        'weight' => 'Poids',
        'fundal_height' => 'Hauteur utérine',
        'fetal_heart_rate' => 'Rythme cardiaque fœtal',
        'presentation' => 'Présentation',
        'danger_signs' => 'Signes de danger',
        'risk_flags' => 'Marqueurs de risque',
        'latest_observation' => 'Dernière observation',
        'cervical_dilation' => 'Dilatation cervicale',
        'theatre_escalation' => 'Escalade vers le bloc signalée',
        'maternal_condition' => 'État maternel',
        'estimated_blood_loss' => 'Perte sanguine estimée',
        'ready_for_discharge' => 'Prêt pour la sortie',
        'follow_up_date' => 'Date de suivi',
        'latest_mother_observation' => 'Dernière observation de la mère',
        'latest_newborn_observation' => 'Dernière observation du nouveau-né',
        'sex' => 'Sexe',
        'apgar' => 'Apgar 1/5/10',
        'resuscitation' => 'Réanimation',
        'ward' => 'Service',
        'bed' => 'Lit',
        'operational_owner_admission' => 'Responsable opérationnel : hospitalisation',
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


    'snapshot' => [
        'verified' => 'Vérifié par rapport à l\'empreinte enregistrée lors de la capture',
        'verified_short' => 'Vérifié',
        'mismatch_short' => 'Non concordant',
        'hash_mismatch' => 'Le contrôle d\'intégrité de l\'instantané a échoué',
        'tamper_evidence_warning' => 'Le contenu enregistré ne correspond plus à l\'empreinte relevée lors de la capture. Il s\'agit d\'une preuve d\'altération uniquement — ce n\'est pas une signature externe et cela ne prouve pas à soi seul la non-répudiation.',
        'verification_unavailable' => 'La vérification d\'intégrité est indisponible pour cet instantané.',
        'cannot_be_edited' => 'Les instantanés ne peuvent pas être modifiés.',
        'cannot_be_deleted' => 'Les instantanés ne peuvent pas être supprimés.',
        'historical_versions' => 'Versions historiques',
        'latest' => 'Dernière',
        'previous' => 'Instantané précédent',
        'next' => 'Instantané suivant',
        'integrity_verification' => 'Intégrité',
        'none_fabricated' => 'Aucun instantané historique n\'a été fabriqué.',
        'completed_before_capture_enabled' => 'Cette consultation a peut-être été clôturée avant l\'activation de la capture d\'instantanés.',
    ],

    'current' => [
        'view' => 'Voir le dossier de maternité actuel',
        'hide' => 'Masquer le dossier de maternité actuel',
        'not_part_of_snapshot' => 'Ne fait pas partie de l\'instantané de clôture',
        'may_differ' => 'Les valeurs actuelles peuvent différer de l\'instantané historique.',
        'loaded_separately' => 'Le dossier actuel a été chargé séparément et ne modifie pas l\'instantané.',
        'unavailable' => 'Aucun contexte de maternité actuel explicite n\'est disponible.',
        'permission_required' => 'L\'autorisation maternité sous-jacente est requise.',
    ],

    'reopened' => [
        'title' => 'Consultation rouverte',
        'live_values_shown' => 'Les valeurs en direct sont affichées tant que la consultation est active.',
        'recompletion_creates_version' => 'Une nouvelle clôture créera une nouvelle version de l\'instantané.',
        'previous_snapshots' => 'Instantanés de clôture précédents',
    ],

    'print' => [
        'historical_summary' => 'Synthèse de consultation historique',
        'printed_snapshot_version' => 'Version d\'instantané imprimée',
        'current_record_print' => 'Impression du dossier actuel',
    ],

];
