<?php

declare(strict_types=1);

use App\Models\Applicant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed();
});

it('returns score 470 for applicant 1', function (): void {
    $this->getJson('/api/v1/applicants/'.Applicant::CASE_1_UUID.'/score')
        ->assertSuccessful()
        ->assertJson([
            'data' => [
                'osszpontszam' => 470,
                'alappont' => 370,
                'tobbletpont' => 100,
            ],
        ]);
});

it('returns score 476 for applicant 2', function (): void {
    $this->getJson('/api/v1/applicants/'.Applicant::CASE_2_UUID.'/score')
        ->assertSuccessful()
        ->assertJson([
            'data' => [
                'osszpontszam' => 476,
                'alappont' => 376,
                'tobbletpont' => 100,
            ],
        ]);
});

it('returns 422 for applicant 3 missing global mandatory subjects', function (): void {
    $this->getJson('/api/v1/applicants/'.Applicant::CASE_3_UUID.'/score')
        ->assertStatus(422)
        ->assertJson([
            'error' => 'nem lehetséges a pontszámítás a kötelező érettségi tárgyak hiánya miatt',
        ]);
});

it('returns 422 for applicant 4 with failed magyar exam', function (): void {
    $this->getJson('/api/v1/applicants/'.Applicant::CASE_4_UUID.'/score')
        ->assertStatus(422)
        ->assertJson([
            'error' => 'nem lehetséges a pontszámítás a magyar nyelv és irodalom tárgyból elért 20% alatti eredmény miatt',
        ]);
});

it('returns 404 for unknown applicant', function (): void {
    $this->getJson('/api/v1/applicants/00000000-0000-0000-0000-000000000000/score')
        ->assertNotFound();
});
