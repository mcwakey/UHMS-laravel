import { existsSync, readFileSync } from 'node:fs';
import { execFileSync } from 'node:child_process';
import { resolve } from 'node:path';
import { laravelRoot } from './laravel-root';

const phpBinary = process.env.UHMS_PHP_BINARY ?? 'php';

export type ConsultationWorkspaceFixture = {
  email: string;
  password: string;
  consultation_url: string;
  consultation_absolute_url: string;
  visit_id: number;
  patient_number: string;
  consultation_route_id: number;
  procedure_department_id: number;
  procedure_service_id: number;
  lab_department_id: number;
  lab_service_id: number;
  drug_id: number;
  metadata_path: string;
};

export function consultationWorkspaceFixture(): ConsultationWorkspaceFixture {
  const output = execFileSync(
    phpBinary,
    ['artisan', 'consultation:e2e-fixture', '--json'],
    {
      cwd: laravelRoot,
      env: process.env,
      stdio: ['ignore', 'pipe', 'pipe'],
      encoding: 'utf8',
      timeout: 120_000,
    },
  );

  const fixture = JSON.parse(output) as ConsultationWorkspaceFixture;

  if (!fixture.consultation_url || !fixture.email || !fixture.password || !fixture.consultation_route_id) {
    throw new Error('Consultation E2E fixture metadata is incomplete.');
  }

  const metadataPath = fixture.metadata_path || resolve(laravelRoot, 'storage/app/testing/consultation-workspace-e2e.json');
  if (!existsSync(metadataPath)) {
    throw new Error(`Consultation E2E fixture metadata file was not written: ${metadataPath}`);
  }

  const stored = JSON.parse(readFileSync(metadataPath, 'utf8')) as ConsultationWorkspaceFixture;
  if (stored.consultation_route_id !== fixture.consultation_route_id) {
    throw new Error('Consultation E2E fixture command output does not match stored metadata.');
  }

  return fixture;
}
