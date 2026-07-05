<?php

return [
    'maternity_billing' => 'Facturation maternité',
    'billing_preview' => 'Aperçu de facturation',
    'billing_posting_disabled' => 'Comptabilisation désactivée',
    'billing_preview_no_posting' => 'Aperçu uniquement. Ce panneau ne crée pas de factures, ne comptabilise pas de frais, ne recalcule pas les soldes, ne sort pas de stock et ne modifie pas le grand livre.',
    'billing_event' => 'Événement de facturation',
    'billing_audit' => 'Audit de facturation',
    'duplicate_prevented' => 'Doublon évité',
    'ready_to_post' => 'Prêt à comptabiliser',
    'mother_billing' => 'Facturation mère',
    'newborn_billing' => 'Facturation nouveau-né',
    'newborn_billing_disabled' => 'Facturation nouveau-né désactivée',
    'newborn_billing_to_mother' => 'Le frais nouveau-né reste sur le contexte de la mère sauf si une politique de facturation nouveau-né lié est activée.',
    'newborn_billing_if_linked' => 'Facturer le nouveau-né si un patient nouveau-né est lié',
    'posting_not_implemented' => 'La comptabilisation est réservée à une phase ultérieure contrôlée de comptabilisation manuelle.',

    'billing_preview_statuses' => [
        'missing_mapping' => 'Correspondance manquante',
        'mapping_disabled' => 'Correspondance désactivée',
        'service_inactive' => 'Service inactif',
        'already_posted' => 'Déjà comptabilisé',
        'billing_disabled' => 'Facturation désactivée',
        'newborn_billing_disabled' => 'Facturation nouveau-né désactivée',
        'ready_to_post' => 'Prêt à comptabiliser',
        'posting_not_implemented' => 'Comptabilisation non implémentée',
    ],
    'billing_preview_reasons' => [
        'missing_mapping' => 'Aucune correspondance de service active n’est configurée pour cet événement.',
        'mapping_disabled' => 'La correspondance existe mais elle est désactivée.',
        'service_inactive' => 'Le service lié est inactif.',
        'already_posted' => 'Un événement de facturation maternité comptabilisé ou une ligne de facture existe déjà pour cette source.',
        'billing_disabled' => 'La facturation maternité est désactivée par configuration.',
        'newborn_billing_disabled' => 'La politique de facturation nouveau-né désactive ces frais.',
        'ready_to_post' => 'La correspondance et le contexte sont prêts, mais aucune action de comptabilisation n’est exposée dans cette phase.',
        'posting_not_implemented' => 'La comptabilisation sera implémentée dans une phase ultérieure contrôlée de comptabilisation manuelle.',
    ],
    'newborn_billing_policies' => [
        'mother' => 'Facturer les soins nouveau-né à la mère',
        'newborn_if_linked' => 'Facturer le nouveau-né si lié',
        'disabled' => 'Désactiver la facturation nouveau-né',
    ],
];
