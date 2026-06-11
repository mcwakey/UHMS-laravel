<?php

return [

    /* ------------------------------------------------------------------ */
    /* Shared / common                                                      */
    /* ------------------------------------------------------------------ */
    'hub_title'         => 'Rapports',
    'hub_description'   => 'Rapports par module, soumis aux permissions.',
    'generated_by'      => 'Généré par',
    'generated_at'      => 'Généré le',
    'date_from'         => 'Du',
    'date_to'           => 'Au',
    'date'              => 'Date',
    'department'        => 'Service',
    'all_departments'   => 'Tous les services',
    'user_staff'        => 'Utilisateur / Personnel',
    'all_staff'         => 'Tout le personnel',
    'status'            => 'Statut',
    'all_statuses'      => 'Tous les statuts',
    'payment_method'    => 'Mode de paiement',
    'all_methods'       => 'Tous les modes',
    'provider'          => 'Prestataire',
    'all_providers'     => 'Tous les prestataires',
    'doctor'            => 'Médecin',
    'all_doctors'       => 'Tous les médecins',
    'blood_group'       => 'Groupe sanguin',
    'all_groups'        => 'Tous les groupes',
    'run'               => 'Exécuter',
    'clear'             => 'Effacer',
    'filter'            => 'Filtrer',
    'export_csv'        => 'Exporter CSV',
    'export_excel'      => 'Exporter Excel',
    'export_pdf'        => 'Exporter PDF',
    'print'             => 'Imprimer',
    'refresh_dashboard' => 'Actualiser le tableau de bord',
    'no_data'           => 'Aucune donnée ne correspond à ce rapport.',
    'no_records'        => 'Aucun enregistrement trouvé.',
    'total_rows'        => 'Total des lignes',
    'total'             => 'Total',
    'subtotal'          => 'Sous-total',
    'grand_total'       => 'Total général',
    'view_report'       => 'Voir le rapport',
    'reports_dashboard' => 'Tableau de bord des rapports',
    'search'            => 'Rechercher',

    /* ------------------------------------------------------------------ */
    /* Generic column headings                                              */
    /* ------------------------------------------------------------------ */
    'col_date'          => 'Date',
    'col_visit'         => 'Visite',
    'col_patient'       => 'Patient',
    'col_department'    => 'Service',
    'col_status'        => 'Statut',
    'col_doctor'        => 'Médecin',
    'col_services'      => 'Services',
    'col_started'       => 'Débuté',
    'col_completed'     => 'Terminé',
    'col_icd_code'      => 'Code CIM',
    'col_diagnosis'     => 'Diagnostic',
    'col_type'          => 'Type',
    'col_primary'       => 'Principal',
    'col_entered_by'    => 'Saisi par',
    'col_complaint'     => 'Plainte',
    'col_duration'      => 'Durée',
    'col_severity'      => 'Gravité',
    'col_dispensed_at'  => 'Dispensé le',
    'col_prescription'  => 'Ordonnance',
    'col_medication'    => 'Médicament',
    'col_quantity'      => 'Quantité',
    'col_dispensed_by'  => 'Dispensé par',
    'col_request'       => 'Demande',
    'col_urgency'       => 'Urgence',
    'col_items'         => 'Articles',
    'col_requested_by'  => 'Demandé par',
    'col_service'       => 'Service',
    'col_priority'      => 'Priorité',
    'col_emergency_no'  => 'N° urgence',
    'col_arrival'       => 'Arrivée',
    'col_triage'        => 'Triage',
    'col_disposition'   => 'Disposition',
    'col_bay'           => 'Baie',
    'col_team'          => 'Équipe',
    'col_admission_no'  => 'N° admission',
    'col_ward_bed'      => 'Unité / Lit',
    'col_admission_date'=> 'Date d\'admission',
    'col_los'           => 'Durée de séjour',
    'col_admitted_by'   => 'Admis par',
    'col_discharged'    => 'Sorti',
    'col_location'      => 'Emplacement',
    'col_reason'        => 'Motif / Réaction',
    'col_dose'          => 'Dose',
    'col_nurse'         => 'Infirmier(e)',
    'col_invoice'       => 'Facture',
    'col_billing_type'  => 'Type de facturation',
    'col_total'         => 'Total',
    'col_paid'          => 'Payé',
    'col_balance'       => 'Solde',
    'col_claim'         => 'Demande remboursement',
    'col_claim_type'    => 'Type',
    'col_claim_amount'  => 'Montant réclamé',
    'col_item'          => 'Article',
    'col_direction'     => 'Direction',
    'col_batch'         => 'Lot',
    'col_expiry'        => 'Expiration',
    'col_performed_by'  => 'Effectué par',
    'col_blood_group'   => 'Groupe sanguin',
    'col_units'         => 'Unités',
    'col_collected_at'  => 'Collecté le',
    'col_issued_to'     => 'Délivré à',
    'col_issued_by'     => 'Délivré par',

    /* ------------------------------------------------------------------ */
    /* Section headings                                                     */
    /* ------------------------------------------------------------------ */
    'sections' => [
        'management'    => 'Vue d\'ensemble Direction',
        'clinical'      => 'Rapports cliniques',
        'patients'      => 'Rapports patients / visites',
        'emergency'     => 'Rapports urgences',
        'admissions'    => 'Rapports admissions / unités',
        'pharmacy'      => 'Rapports pharmacie',
        'investigations'=> 'Rapports examens',
        'theatre'       => 'Rapports bloc opératoire',
        'billing'       => 'Rapports facturation',
        'claims'        => 'Rapports assurances / réclamations',
        'accounting'    => 'Rapports comptabilité',
        'receivables'   => 'Rapports débiteurs',
        'payables'      => 'Rapports créanciers',
        'stock'         => 'Rapports stock / approvisionnement',
        'hr'            => 'Rapports RH',
        'audit'         => 'Journaux d\'audit',
    ],

    /* ------------------------------------------------------------------ */
    /* Management                                                           */
    /* ------------------------------------------------------------------ */
    'management' => [
        'title'       => 'Vue d\'ensemble Direction',
        'description' => 'Activité hospitalière globale, position financière et résumé des risques.',
        'financial_summary'  => 'Synthèse financière',
        'rendering_risk'     => 'Risque de prestation',
        'report_catalogue'   => 'Catalogue des rapports',
        'billed'             => 'Facturé',
        'collected'          => 'Encaissé',
        'outstanding'        => 'Impayé',
        'claims_total'       => 'Total réclamations',
        'billed_not_rendered'=> 'Facturé non rendu',
        'rendered_unpaid'    => 'Rendu non payé',
        'open_service_rendering_reports' => 'Rapports de prestations de service',
    ],

    /* ------------------------------------------------------------------ */
    /* Clinical / operational catalogue                                    */
    /* ------------------------------------------------------------------ */
    'consultations' => [
        'title'       => 'Rapport de consultations',
        'description' => 'Sessions par service, médecin, prestations liées et statuts.',
        'sessions'    => 'Sessions',
        'doctor'      => 'Médecin',
        'department'  => 'Service',
        'service'     => 'Prestation',
    ],
    'diagnoses' => [
        'title'       => 'Rapport de diagnostics',
        'description' => 'Diagnostics cliniques par visite, service, médecin, code CIM et type.',
        'icd_code'    => 'Code CIM',
        'diagnosis'   => 'Diagnostic',
        'type'        => 'Type',
    ],
    'complaints' => [
        'title'       => 'Rapport de plaintes',
        'description' => 'Plaintes et antécédents recueillis lors des soins.',
    ],

    /* ------------------------------------------------------------------ */
    /* Patients / Visits                                                    */
    /* ------------------------------------------------------------------ */
    'patients' => [
        'title'             => 'Rapport patients',
        'description'       => 'Patients enregistrés, données démographiques et activité d\'enregistrement.',
        'total_patients'    => 'Total des patients',
        'new_patients'      => 'Nouveaux patients',
        'gender_breakdown'  => 'Répartition par sexe',
        'patient_number'    => 'N° patient',
        'name'              => 'Nom',
        'gender'            => 'Sexe',
        'age'               => 'Âge',
        'phone'             => 'Téléphone',
        'registered'        => 'Enregistré',
    ],
    'visits' => [
        'title'             => 'Rapport de visites',
        'description'       => 'Visites ambulatoires par type, service, médecin et statut.',
        'total_visits'      => 'Total des visites',
        'visit_number'      => 'N° visite',
        'visit_type'        => 'Type de visite',
        'all_types'         => 'Tous les types',
        'department'        => 'Service',
        'doctor'            => 'Médecin',
        'visit_date'        => 'Date de visite',
    ],

    /* ------------------------------------------------------------------ */
    /* Emergency                                                            */
    /* ------------------------------------------------------------------ */
    'emergency' => [
        'title'             => 'Rapport urgences',
        'description'       => 'Présence aux urgences, triage, orientation et statut opérationnel.',
        'total_cases'       => 'Total des cas',
        'active_cases'      => 'Cas actifs',
        'triage_breakdown'  => 'Répartition triage',
        'disposition'       => 'Orientation',
        'case_number'       => 'N° cas',
        'triage_category'   => 'Catégorie de triage',
        'arrival_time'      => 'Heure d\'arrivée',
    ],

    /* ------------------------------------------------------------------ */
    /* Admissions                                                           */
    /* ------------------------------------------------------------------ */
    'admissions' => [
        'title'              => 'Registre des admissions',
        'description'        => 'Admissions en hospitalisation, unité/lit, durée de séjour et statut de sortie.',
        'total_admissions'   => 'Total des admissions',
        'current_admissions' => 'Admissions en cours',
        'discharges'         => 'Sorties',
        'avg_los'            => 'DMS (jours)',
        'admission_date'     => 'Date d\'admission',
        'ward'               => 'Unité',
        'bed'                => 'Lit',
        'discharge_date'     => 'Date de sortie',
        'los_days'           => 'DMS (j)',
        'discharge_title'    => 'Registre des sorties',
        'discharge_description' => 'Sorties de patients, durée de séjour et activité par unité.',
    ],
    'mar' => [
        'title'       => 'Rapport MAR',
        'description' => 'Administration des médicaments, tâches en retard, doses omises/retenues/refusées et activité infirmière.',
        'administered'=> 'Administré',
        'overdue'     => 'En retard',
        'missed'      => 'Omis',
        'refused'     => 'Refusé',
    ],

    /* ------------------------------------------------------------------ */
    /* Pharmacy                                                             */
    /* ------------------------------------------------------------------ */
    'pharmacy' => [
        'title'              => 'Rapport pharmacie',
        'description'        => 'Ordonnances, activité de délivrance et quantités fournies.',
        'sales_title'        => 'Rapport des ventes pharmacie',
        'sales_description'  => 'Ventes de médicaments, quantités délivrées et revenus par produit/période.',
        'summary_title'      => 'Synthèse des ventes pharmacie',
        'summary_description'=> 'Ventes de médicaments résumées par produit sur une période.',
        'total_sales'        => 'Total des ventes',
        'total_qty'          => 'Quantité totale',
        'drug'               => 'Médicament',
        'qty_dispensed'      => 'Qté délivrée',
        'unit_price'         => 'Prix unitaire',
        'total_amount'       => 'Montant total',
        'prescription_no'    => 'N° ordonnance',
    ],

    /* ------------------------------------------------------------------ */
    /* Investigations / Lab                                                 */
    /* ------------------------------------------------------------------ */
    'investigations' => [
        'title'            => 'Rapport d\'examens',
        'description'      => 'Demandes, services cibles, urgence et statut du flux de résultats.',
        'revenue_title'    => 'Rapport de revenus examens',
        'revenue_description' => 'Revenus générés par les services d\'examens/laboratoire.',
        'total_requests'   => 'Total des demandes',
        'pending'          => 'En attente',
        'in_progress'      => 'En cours',
        'completed'        => 'Terminées',
        'urgent'           => 'Urgentes',
        'request_no'       => 'N° demande',
        'test'             => 'Examen',
        'urgency'          => 'Urgence',
        'requested_by'     => 'Demandé par',
        'result_status'    => 'Statut résultat',
    ],

    /* ------------------------------------------------------------------ */
    /* Theatre / Procedures                                                 */
    /* ------------------------------------------------------------------ */
    'procedures' => [
        'title'       => 'Rapport des procédures',
        'description' => 'Demandes de procédures, facturation, acceptation, planification et état de réalisation.',
    ],
    'theatre' => [
        'title'       => 'Rapport bloc opératoire',
        'description' => 'Charge de travail du bloc opératoire et statut du flux opératoire.',
        'scheduled'   => 'Planifiées',
        'in_theatre'  => 'En salle',
        'completed'   => 'Terminées',
        'cancelled'   => 'Annulées',
    ],

    /* ------------------------------------------------------------------ */
    /* Billing                                                              */
    /* ------------------------------------------------------------------ */
    'billing' => [
        'title'              => 'Rapport de facturation',
        'description'        => 'Factures, paiements, soldes impayés et risque de prestation non rendue.',
        'daily_collections'  => 'Encaissements du jour',
        'daily_collections_description' => 'Tous les paiements reçus sur une date donnée, par mode et caissier.',
        'invoice_register'   => 'Registre des factures',
        'income_title'       => 'Rapport de revenus',
        'income_description' => 'Revenus encaissés par mode de paiement sur une période.',
        'total_collected'    => 'Total encaissé',
        'transactions'       => 'Transactions',
        'payment_methods'    => 'Modes de paiement',
        'collection_by_method' => 'Encaissements par mode de paiement',
        'receipt_no'         => 'N° reçu',
        'time'               => 'Heure',
        'invoice_no'         => 'N° facture',
        'method'             => 'Mode',
        'received_by'        => 'Reçu par',
        'amount'             => 'Montant',
        'billed'             => 'Facturé',
        'paid'               => 'Payé',
        'balance'            => 'Solde',
        'discount_title'     => 'Rapport des remises',
        'discount_description'=> 'Remises approuvées et en attente sur les factures.',
        'ar_aging_title'     => 'Balance âgée débiteurs',
        'ar_aging_description'=> 'Comptes débiteurs vieillis par tranche.',
        'aging_current'      => 'Courant',
        'aging_0_30'         => '0–30 jours',
        'aging_31_60'        => '31–60 jours',
        'aging_61_90'        => '61–90 jours',
        'aging_91_120'       => '91–120 jours',
        'aging_120_plus'     => '120+ jours',
        'create_invoice'     => 'Créer une facture',
    ],

    /* ------------------------------------------------------------------ */
    /* Claims / Insurance                                                   */
    /* ------------------------------------------------------------------ */
    'claims' => [
        'title'             => 'Rapport des réclamations',
        'description'       => 'Réclamations d\'assurance par flux, prestataire, statut et résultat financier.',
        'total_claims'      => 'Total des réclamations',
        'approved'          => 'Approuvées',
        'rejected'          => 'Rejetées',
        'submitted'         => 'Soumises',
        'claim_no'          => 'N° réclamation',
        'provider'          => 'Prestataire',
        'claim_amount'      => 'Montant réclamé',
        'approved_amount'   => 'Montant approuvé',
        'claim_date'        => 'Date de réclamation',
    ],

    /* ------------------------------------------------------------------ */
    /* Accounting                                                           */
    /* ------------------------------------------------------------------ */
    'accounting' => [
        'title'              => 'Rapports comptabilité',
        'description'        => 'États financiers, grand livre, journaux et intégrité des écritures.',
        'trial_balance'      => 'Balance de vérification',
        'general_ledger'     => 'Grand livre',
        'profit_loss'        => 'Compte de résultat',
        'balance_sheet'      => 'Bilan',
        'cashbook'           => 'Livre de caisse',
        'failed_postings'    => 'Écritures comptables échouées',
        'revenue_by_dept'    => 'Revenus par service',
        'expense_by_dept'    => 'Dépenses par service',
    ],

    /* ------------------------------------------------------------------ */
    /* Receivables                                                          */
    /* ------------------------------------------------------------------ */
    'receivables' => [
        'title'       => 'Rapports débiteurs',
        'description' => 'Balance âgée, débiteurs patients et assurances, soldes en retard.',
        'ar_aging'    => 'Balance âgée débiteurs',
        'patient_ar'  => 'Débiteurs patients',
        'insurance_ar'=> 'Débiteurs assurances',
        'sponsor_ar'  => 'Débiteurs sponsors',
    ],

    /* ------------------------------------------------------------------ */
    /* Payables                                                             */
    /* ------------------------------------------------------------------ */
    'payables' => [
        'title'          => 'Rapports créanciers',
        'description'    => 'Balance âgée fournisseurs, soldes et paiements.',
        'ap_aging'       => 'Balance âgée fournisseurs',
        'supplier_stmt'  => 'Relevé fournisseur',
        'supplier_payments'=> 'Paiements fournisseurs',
    ],

    /* ------------------------------------------------------------------ */
    /* Stock                                                                */
    /* ------------------------------------------------------------------ */
    'stock' => [
        'title'              => 'Rapport de stock',
        'description'        => 'Mouvements de stock et inventaire sensible aux dates de péremption.',
        'stock_valuation'    => 'Valorisation du stock',
        'stock_valuation_description' => 'Valeur actuelle de l\'inventaire par emplacement et produit.',
        'expired_stock'      => 'Stock expiré / endommagé',
        'expired_description'=> 'Produits dépassant la date de péremption ou déclarés endommagés.',
        'total_value'        => 'Valeur totale',
        'product'            => 'Produit',
        'location'           => 'Emplacement',
        'qty_on_hand'        => 'Qté en stock',
        'unit_cost'          => 'Coût unitaire',
        'total_cost'         => 'Coût total',
        'expiry_date'        => 'Date de péremption',
        'batch'              => 'Lot',
        'type'               => 'Type',
        'all_types'          => 'Tous les types',
    ],

    /* ------------------------------------------------------------------ */
    /* HR                                                                   */
    /* ------------------------------------------------------------------ */
    'hr' => [
        'leave_title'        => 'Rapport des congés',
        'leave_description'  => 'Demandes de congé par type, statut et membre du personnel.',
        'payroll_title'      => 'Rapport de paie',
        'payroll_description'=> 'Bulletins de paie par période et statut d\'approbation.',
        'leave_type'         => 'Type de congé',
        'all_leave_types'    => 'Tous les types',
        'pay_period'         => 'Période de paie',
        'staff'              => 'Personnel',
        'days'               => 'Jours',
        'net_pay'            => 'Net à payer',
    ],

    /* ------------------------------------------------------------------ */
    /* Blood Bank                                                           */
    /* ------------------------------------------------------------------ */
    'blood_bank' => [
        'title'       => 'Rapport banque de sang',
        'description' => 'Inventaire du sang, demandes, distribution, transfusion, expiration et gaspillage.',
    ],

    /* ------------------------------------------------------------------ */
    /* Audit                                                                */
    /* ------------------------------------------------------------------ */
    'audit' => [
        'title'       => 'Rapport journal d\'activité',
        'description' => 'Actions des utilisateurs, événements à haut risque et activité système.',
    ],

    /* ------------------------------------------------------------------ */
    /* Patient statement                                                    */
    /* ------------------------------------------------------------------ */
    'statement' => [
        'title'          => 'Relevé patient',
        'description'    => 'Relevé financier d\'un patient sur une période.',
        'search_title'   => 'Recherche relevé patient',
        'search_prompt'  => 'Recherchez un patient pour générer son relevé.',
        'search_placeholder' => 'Nom ou numéro du patient...',
    ],
];
