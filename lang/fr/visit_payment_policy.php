<?php

return [
    'title' => 'Politique de paiement de la visite',
    'worklist_title' => 'Liste de travail — politiques de paiement des visites',
    'report_title' => 'Rapport sur les politiques de paiement des visites',
    'section_title' => 'Politique de paiement',
    'observational_notice' => 'Cet enregistrement est observationnel et ne contrôle pas actuellement l\'accès aux services ni l\'application des paiements.',
    'empty_state' => 'Aucune politique de paiement matérialisée pour cette visite.',
    'no_results' => 'Aucune politique de paiement de visite ne correspond aux filtres sélectionnés.',

    'labels' => [
        'observed_policy' => 'Politique observée',
        'recommended_policy' => 'Politique recommandée',
        'operational_legacy_gate' => 'Blocage opérationnel hérité',
        'policy_source' => 'Source de la politique',
        'resolution_reason' => 'Motif de résolution',
        'finance_review_required' => 'Révision financière requise',
        'risk_snapshot' => 'Instantané de risque',
        'risk_level_snapshot' => 'Instantané du niveau de risque',
        'risk_status_snapshot' => 'Instantané du statut de risque',
        'risk_recommendation' => 'Recommandation de risque',
        'global_default_snapshot' => 'Instantané du défaut global',
        'visit_type_policy_snapshot' => 'Instantané de la politique par type de visite',
        'visit_type_snapshot' => 'Type de visite',
        'emergency_protection' => 'Protection d\'urgence considérée',
        'compatible_override' => 'Dérogation de visite compatible',
        'materialized_at' => 'Matérialisé le',
        'last_refreshed' => 'Dernière actualisation',
        'snapshot_freshness' => 'Fraîcheur de l\'instantané',
        'resolution_version' => 'Version de résolution',
        'visit' => 'Visite',
        'patient' => 'Patient',
        'no_recommendation' => 'Aucune recommandation',
        'prepayment_recommended' => 'Prépaiement recommandé',
        'none' => 'Aucune',
        'considered' => 'Considérée',
        'not_considered' => 'Non considérée',
    ],

    'freshness' => [
        'current' => 'Instantané à jour',
        'stale' => 'Instantané périmé',
    ],

    'events' => [
        'materialized' => 'Matérialisé',
        'refreshed' => 'Actualisé',
        'risk_snapshot_changed' => 'Instantané de risque modifié',
        'baseline_policy_changed' => 'Politique de base actualisée',
        'recommendation_changed' => 'Recommandation modifiée',
        'marked_stale' => 'Marqué périmé',
    ],

    'history' => [
        'title' => 'Historique de la politique de visite',
        'datetime' => 'Date / Heure',
        'event' => 'Événement',
        'previous_policy' => 'Politique précédente',
        'new_policy' => 'Nouvelle politique',
        'previous_recommendation' => 'Recommandation précédente',
        'new_recommendation' => 'Nouvelle recommandation',
        'risk_change' => 'Changement d\'instantané de risque',
        'performed_by' => 'Effectué par',
        'reason_code' => 'Code de motif',
        'empty' => 'Aucun historique enregistré.',
        'system' => 'Système',
    ],

    'filters' => [
        'resolved_policy' => 'Politique observée',
        'recommended_policy' => 'Politique recommandée',
        'resolution_source' => 'Source',
        'risk_level' => 'Instantané du niveau de risque',
        'visit_type' => 'Type de visite',
        'finance_review' => 'Révision financière requise',
        'active_only' => 'Visites actives uniquement',
        'from' => 'Matérialisé à partir du',
        'to' => 'Matérialisé jusqu\'au',
        'all' => 'Tous',
        'apply' => 'Appliquer',
        'reset' => 'Réinitialiser',
    ],

    'report' => [
        'total' => 'Politiques matérialisées',
        'finance_review' => 'Nécessitant une révision financière',
        'with_recommendation' => 'Avec une recommandation',
        'high_risk_snapshot' => 'Instantanés à risque élevé',
        'blocked_credit_snapshot' => 'Instantanés à crédit bloqué',
        'materialized_this_month' => 'Matérialisées ce mois-ci',
    ],

    'actions' => [
        'refresh' => 'Actualiser l\'instantané',
        'view' => 'Voir',
        'open_worklist' => 'Ouvrir la liste de travail',
        'report' => 'Rapport',
    ],

    'flash' => [
        'refreshed' => 'Politique de paiement de la visite actualisée.',
    ],
];
