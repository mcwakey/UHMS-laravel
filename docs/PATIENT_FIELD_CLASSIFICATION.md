# Patient Field Classification

Phase: Patient privacy and data protection phase 1.

## Levels

| Level | Meaning | Default handling |
| --- | --- | --- |
| 0 | Safe operational identifiers | Visible to users with normal workflow access |
| 1 | Operational demographic data | Visible to users with normal workflow access |
| 2 | Personally identifiable information | Mask unless a granular patient privacy permission is present |
| 3 | Highly sensitive clinical/confidential information | Mask unless `patients.clinical_sensitive.view` is present |

## Field Register

| Field | Level | Permission | Masking |
| --- | ---: | --- | --- |
| `id`, `patient_number`, `age`, `gender`, `date_of_birth` | 0 | none | none/date |
| `nationality`, `occupation`, `marital_status`, `religion`, `language`, `city`, `town`, `region` | 1 | none | none |
| `phone`, `phone_secondary`, `email` | 2 | `patients.contact.view` or `patients.pii.view` | phone/email mask |
| `address`, `digital_address` | 2 | `patients.address.view` or `patients.pii.view` | hidden |
| `ghana_card_number`, passport, driving licence, voter ID | 2 | `patients.identity.view` or `patients.pii.view` | identifier mask |
| `membership_number`, `policy_number`, `ccc_code` | 2 | `patients.insurance.view` or `patients.pii.view` | identifier mask |
| emergency contact phone/address | 2 | `patients.emergency_contact.view` or `patients.pii.view` | phone/hidden |
| allergies, chronic conditions, confidential clinical notes, HIV, mental health, sexual health, child protection, court restrictions | 3 | `patients.clinical_sensitive.view` | hidden |

## Implemented Registry

The executable registry lives in [config/patient_privacy.php](/C:/dev/projects/web/UHMS-laravel/config/patient_privacy.php). Services must read from this file instead of duplicating field rules in Blade, controllers, exports, or JSON resources.

## New Permissions

| Permission | Purpose |
| --- | --- |
| `patients.pii.view` | Broad level-2 PII visibility |
| `patients.contact.view` | Phone and email |
| `patients.identity.view` | National and legal identifiers |
| `patients.address.view` | Residential and digital address |
| `patients.insurance.view` | Insurance member/policy/verification identifiers |
| `patients.emergency_contact.view` | Emergency contact details |
| `patients.clinical_sensitive.view` | Level-3 sensitive clinical data |
| `patients.export_sensitive.view` | Full sensitive-data exports/prints |

