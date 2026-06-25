<?php

/*
| Vocabulaire dynamique du flux de visite : sources, classes de fréquentation,
| libellés UI et messages flash. Les libellés de statut sont dans statuses.php.
*/

return [

    'source' => [
        'direct' => 'Direct',
        'appointment' => 'Rendez-vous',
        'emergency' => 'Urgence',
        'referral' => 'Référence',
        'follow_up' => 'Suivi',
        'review' => 'Contrôle',
        'online_booking' => 'Réservation en ligne',
        'walk_in' => 'Sans rendez-vous',
    ],

    'attendance_class' => [
        'first_ever' => 'Première venue',
        'first_attendance_of_year' => 'Première venue de l\'année',
        'subsequent_attendance' => 'Venue ultérieure',
        'emergency_attendance' => 'Venue en urgence',
        'referral_attendance' => 'Venue sur référence',
    ],

    'ui' => [
        'badge_first_ever' => 'Première venue',
        'badge_first_attendance_of_year' => 'Première venue cette année',
        'badge_subsequent_attendance' => 'Venue ultérieure',
        'badge_emergency_attendance' => 'Venue en urgence',
        'badge_referral_attendance' => 'Venue sur référence',
        'attendance_label' => 'Fréquentation',
        'source_label' => 'Source',
        'status_label' => 'Statut',
        'status_flow' => 'Flux de statut de la visite',
        'status_history' => 'Historique des statuts',
        'computing' => 'Vérification de la fréquentation…',
    ],

    'messages' => [
        'transition_blocked' => 'Impossible de faire passer la visite de :from à :to.',
        'override_required' => 'Vous n\'avez pas la permission de forcer cette transition.',
        'transitioned' => 'Statut de la visite mis à jour : :status.',
    ],
];
