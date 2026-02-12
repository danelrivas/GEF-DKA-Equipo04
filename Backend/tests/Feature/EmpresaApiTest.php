<?php

use App\Models\User;
use App\Models\Empresa;

// EMPRESA ENDPOINTS

test('list companies successfully', function () {
    $admin = User::factory()->create(['tipo' => 'admin']);
    $token = $admin->createToken('auth_token')->plainTextToken;

    Empresa::create([
        'CIF' => 'LST1234567',
        'Nombre' => 'Empresa Test',
        'Direccion' => 'Calle Principal 123',
        'Email' => 'test@empresa.com',
        'N_Tel' => '600000001',
    ]);

    $response = $this->withHeader('Authorization', "Bearer $token")
        ->get('/api/allempresas');

    $response->assertStatus(200);
});

test('search companies by name', function () {
    $admin = User::factory()->create(['tipo' => 'admin']);
    $token = $admin->createToken('auth_token')->plainTextToken;

    Empresa::create(['CIF' => 'TEL1234567', 'Nombre' => 'Telefonica', 'Direccion' => 'Av. Test 1', 'Email' => 'tel@test.com', 'N_Tel' => '600000002']);
    Empresa::create(['CIF' => 'VOD1234567', 'Nombre' => 'Vodafone', 'Direccion' => 'Av. Test 2', 'Email' => 'vod@test.com', 'N_Tel' => '600000003']);

    $response = $this->withHeader('Authorization', "Bearer $token")
        ->get('/api/allempresas?q=Telefonica');

    $response->assertStatus(200);
});

test('search companies by CIF', function () {
    $admin = User::factory()->create(['tipo' => 'admin']);
    $token = $admin->createToken('auth_token')->plainTextToken;

    Empresa::create(['CIF' => 'ABC123456', 'Nombre' => 'Empresa A', 'Direccion' => 'Av. CIF', 'Email' => 'ciftest@test.com', 'N_Tel' => '600000004']);

    $response = $this->withHeader('Authorization', "Bearer $token")
        ->get('/api/allempresas?q=ABC123456');

    $response->assertStatus(200);
});

test('create company successfully', function () {
    $admin = User::factory()->create(['tipo' => 'admin']);
    $token = $admin->createToken('auth_token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer $token")
        ->post('/api/empresa/create', [
            'Nombre' => 'Nueva Empresa',
            'Direccion' => 'Calle Test 456',
            'CIF' => 'NEW123456',
            'Email' => 'empresa@example.com',
            'N_Tel' => '912345678',
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['Nombre', 'CIF', 'Email']);

    $this->assertDatabaseHas('empresa', [
        'CIF' => 'NEW123456',
        'Nombre' => 'Nueva Empresa',
    ]);
});

test('create company fails with duplicate CIF', function () {
    Empresa::create(['CIF' => 'DUP123456', 'Nombre' => 'Existing', 'Direccion' => 'Test', 'Email' => 'dup@test.com', 'N_Tel' => '600000005']);
    $admin = User::factory()->create(['tipo' => 'admin']);
    $token = $admin->createToken('auth_token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer $token")
        ->post('/api/empresa/create', [
            'Nombre' => 'Another Company',
            'Direccion' => 'Test Dir',
            'CIF' => 'DUP123456',
            'Email' => 'another@test.com',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('CIF');
});

test('create company fails with invalid phone', function () {
    $admin = User::factory()->create(['tipo' => 'admin']);
    $token = $admin->createToken('auth_token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer $token")
        ->post('/api/empresa/create', [
            'Nombre' => 'Company',
            'CIF' => 'TEL123456',
            'N_Tel' => '12345',  // Invalid: not 9 digits
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('N_Tel');
});

test('create company fails without name', function () {
    $admin = User::factory()->create(['tipo' => 'admin']);
    $token = $admin->createToken('auth_token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer $token")
        ->post('/api/empresa/create', [
            'CIF' => 'NONAME123',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('Nombre');
});

test('create company fails without authentication', function () {
    $response = $this->post('/api/empresa/create', [
        'Nombre' => 'Company',
        'CIF' => 'AUTH123456',
    ]);

    $response->assertStatus(401);
});
