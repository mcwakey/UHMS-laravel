<?php

namespace App\Services\LegacyMigration\Foundation\Runtime;

enum ProhibitedSubsystem: string
{
    case LaravelEvents = 'events';
    case ModelObservers = 'model_observers';
    case OperationalActivityLog = 'activity_log';
    case Notifications = 'notifications';
    case Mail = 'mail';
    case Sms = 'sms';
    case QueueDispatch = 'queue';
    case BusJobs = 'bus';
    case ScheduledCommands = 'scheduler';
    case ExternalAuditForwarding = 'audit_forwarding';
    case PaymentIntegrations = 'payments';
    case InsuranceEligibilityIntegrations = 'insurance_eligibility';
    case BillingCreation = 'billing';
    case AccountingPosting = 'accounting';
    case PaymentAllocation = 'payment_allocation';
    case StockMovement = 'stock';
    case PharmacyDispensing = 'pharmacy_dispensing';
    case BedOccupancy = 'bed_state';
    case QueuePathwayMutation = 'queue_pathway';
    case AppointmentReminders = 'appointment_reminders';
    case JourneyNotifications = 'journey_notifications';
    case FileAvatarWrites = 'file_writes';
    case SearchIndexing = 'search_indexing';
    case Webhooks = 'webhooks';
    case ExternalIntegrations = 'external_integrations';
}
