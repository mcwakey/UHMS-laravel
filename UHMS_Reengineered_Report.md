# UHMS (Ultimate Hospital Management System)
## Reengineered System Analysis & Implementation Plan

---

# 1. Executive Summary

This document presents a full analysis and redesigned implementation plan for rebuilding the legacy UHMS (v5.2.0) system into a modern, scalable Laravel-based platform.

The goal is to:
- Preserve proven workflows and business logic
- Eliminate legacy technical debt
- Introduce clean architecture and scalability
- Prepare the system for future intelligent features

---

# 2. Reengineering Strategy

## Approach
- DO NOT replicate legacy code
- Extract business rules and workflows
- Redesign using Laravel best practices

## Key Principles
- Separation of concerns
- Service-based architecture
- Explicit workflows
- Secure and auditable data handling

---

# 3. Legacy Strengths to Preserve

- Full patient lifecycle workflow
- Multi-department structure
- Multi-tier billing (Cash, NHIS, Private)
- Claims processing
- Pharmacy + Inventory integration
- Analyzer integration (HL7/ASTM)
- Notification system

---

# 4. Legacy Weaknesses to Fix

- Plain-text passwords → hashing
- SQL injection risks → ORM
- Mixed data access → unified Eloquent
- Hidden UI state → explicit backend logic
- Weak workflow modeling → status engine

---

# 5. New System Architecture

## Layers

### 1. Presentation Layer
- Blade + Alpine.js (MVP)
- Vue (future upgrade)

### 2. Application Layer
- Controllers
- Requests (validation)

### 3. Domain Layer
- Services (business logic)
- Actions (single operations)

### 4. Data Layer
- Eloquent Models
- Repositories (optional)

### 5. Infrastructure Layer
- Notifications
- Queues
- Analyzer integration

---

# 6. Core Domains

## Clinical
- Patients
- Visits
- Medical Records

## Operations
- Lab
- Pharmacy
- Ward

## Business
- Billing
- Claims
- Accounting

## Admin
- Users
- Roles
- Settings

## Infrastructure
- Notifications
- Analyzer

---

# 7. Patient Workflow (Preserved & Improved)

## Status Flow

REGISTERED → WAITING → VITALS → CONSULTING → INVESTIGATION → PHARMACY → BILLING → COMPLETED

## Inpatient Flow

ADMITTED → TREATMENT → DISCHARGED

---

# 8. Database Redesign Strategy

## Key Improvements
- Normalize all tables
- Use foreign keys
- Add audit fields (created_by, updated_by)
- Separate concerns (e.g., prescriptions vs items)

## Core Tables

- patients
- visits
- visit_status_logs
- medical_records
- prescriptions
- lab_orders
- invoices
- payments
- users
- roles

---

# 9. Module Implementation Plan

## Phase 1: Core Foundation
- Authentication & Roles
- Patient Module
- Visit Module (workflow engine)

## Phase 2: Clinical
- EHR (Doctor module)
- Ward (Vitals + Admission)

## Phase 3: Operations
- Lab / Investigations
- Pharmacy

## Phase 4: Business
- Billing & Payments
- Claims (NHIS / Private)

## Phase 5: Inventory
- Store
- Stock management

## Phase 6: Infrastructure
- Notifications (events)
- Analyzer integration (async jobs)

## Phase 7: Advanced Features
- MedicalPattern engine
- Analytics dashboard

---

# 10. Workflow Engine Design

- Enum-based status system
- Transition validation
- Status history logging
- Service-driven updates

---

# 11. Notification System (New)

- Laravel Events
- Listeners
- Database notifications
- Optional real-time (WebSockets)

---

# 12. Analyzer Integration (Modernized)

- Queue-based processing
- Raw message storage
- Protocol adapters (HL7, ASTM)
- Result mapping + validation

---

# 13. Security Enhancements

- Password hashing
- Role-based access control
- Input validation
- Audit logs

---

# 14. Deployment Architecture

- Nginx + PHP-FPM
- PostgreSQL
- Redis (queues)
- Supervisor (workers)

---

# 15. Risks & Mitigation

## Risks
- Overengineering early
- Complex workflows
- Data migration issues

## Mitigation
- Build MVP first
- Iterate per module
- Test with real scenarios

---

# 16. Roadmap (Execution Plan)

## Week 1–2
- Database design
- Auth + Roles

## Week 3–4
- Patient + Visit

## Week 5–6
- EHR + Ward

## Week 7–8
- Lab + Pharmacy

## Week 9
- Billing + Claims

## Week 10
- Notifications + Testing

## Week 11+
- Analyzer + Intelligence features

---

# 17. Final Vision

UHMS is evolving into a:

“Smart Clinical Management Platform”

Future-ready for:
- AI-assisted diagnosis
- Data analytics
- Multi-hospital deployment

---

END OF REPORT
