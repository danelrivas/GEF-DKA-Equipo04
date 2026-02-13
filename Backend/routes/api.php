<?php

use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\EstanciaCompetenciaController;
use App\Http\Controllers\GradoController;
use App\Http\Controllers\NotasCompetenciaController;
use App\Http\Controllers\TutorController;
use App\Http\Controllers\InstructorController;
use App\Http\Controllers\AlumnoController;
use App\Http\Controllers\AlumnoEntregaController;
use App\Http\Controllers\AlumnoImportController;
use App\Http\Controllers\AsignacionImportController;
use App\Http\Controllers\CompRaController;
use App\Http\Controllers\EntregaCuadernoController;
use App\Http\Controllers\EstanciaController;
use App\Http\Controllers\NotaCuadernoController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\NotasEmpresaController;
use App\Http\Controllers\SeguimientoController;
use App\Http\Controllers\AsignaturaController;
use App\Http\Controllers\RaController;
use App\Http\Controllers\CompetenciaController;
use App\Http\Controllers\TransversalController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Gate;

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
*/

Route::post('/login', [UserController::class, 'login']);

Route::middleware('auth:sanctum')->get('/test-gates', function (Request $request) {
    $user = $request->user();

    return response()->json([
        'user_id' => $user->id,
        'user_tipo' => $user->tipo,
        'gates' => [
            'es-admin' => Gate::allows('es-admin', $user),
            'es-tutor' => Gate::allows('es-tutor', $user),
            'es-instructor' => Gate::allows('es-instructor', $user),
            'es-alumno' => Gate::allows('es-alumno', $user),
        ],
        'gate_check_raw' => Gate::check('es-admin', $user),
    ]);
});

Route::middleware('auth:sanctum')->group(function () {

    // ========================================
    // RUTAS COMUNES (todos los autenticados)
    // ========================================
    Route::post('/logout', [UserController::class, 'logout']);
    Route::get('/auth', [UserController::class, 'auth']);
    Route::post('/change-password', [UserController::class, 'changePassword']);
    // ========================================
    // RUTAS SOLO ADMIN
    // ========================================
    Route::middleware('can:es-admin')->group(function () {
        // Gestión de usuarios
        Route::post('/user/create', [UserController::class, 'create']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'delete']);

        // Gestión de empresas
        Route::post('/empresa/create', [EmpresaController::class, 'create']);

        // Gestión de instructores
        Route::post('/empresa/instructor/create', [InstructorController::class, 'crearInstructor']);

        // Gestión de grados
        Route::post('/grados', [GradoController::class, 'crearGrado']);
        Route::delete('/grados/{id}', [GradoController::class, 'eliminarGrado']);
        Route::post('/asignaturas', [AsignaturaController::class, 'store']);
        Route::delete('/asignaturas/{id}', [AsignaturaController::class, 'destroy']);
        Route::post('/competencias', [CompetenciaController::class, 'store']);
        Route::delete('/competencias/{id}', [CompetenciaController::class, 'destroy']);
        Route::post('/ras', [RaController::class, 'store']);
        Route::delete('/ras/{id}', [RaController::class, 'destroy']);

        // Transversales
        Route::post('/transversales', [TransversalController::class, 'crearTransversal']);
        Route::put('/transversales/{id}', [TransversalController::class, 'actualizarTransversal']);
        Route::delete('/transversales/{id}', [TransversalController::class, 'eliminarTransversal']);

        Route::get('/users', [UserController::class, 'getUsers']);
        Route::get('/empresa/{cif}/instructores', [InstructorController::class, 'getCompanyInstructor']);
        Route::get('/tutores/disponibles', [TutorController::class, 'getTutoresDisponibles']);
        Route::get('/grados', [GradoController::class, 'getGrados']);
        Route::get('/gradosTodos', [GradoController::class, 'getTodosGrados']);

        // Gestión de instructores y alumnos
        Route::get('/instructores/{id}/alumnos', [AlumnoController::class, 'alumnosDeInstructor']);
        Route::get('/alumno/{id}', [AlumnoController::class, 'getGrado']);

        // Subir archivos CSV y XLSX
        Route::post('/alumnos/importar', [AlumnoImportController::class, 'importar']);
        Route::post('/asignaciones/importar', [AsignacionImportController::class, 'importar']);
    });

    // ========================================
    // RUTAS SOLO TUTOR
    // ========================================
    Route::middleware('can:es-tutor')->group(function () {
        Route::put('/alumnos/{id}/asignar-tutor', [AlumnoController::class, 'asignarTutor']);
        Route::put('/alumnos/{id}/desasignar-tutor', [AlumnoController::class, 'desasignarTutor']);
        Route::get('/tutor/alumno/{id}/estancias', [EstanciaController::class, 'historialEstanciasAlumno']);
        Route::post('/asignarEstancia', [EstanciaController::class, 'asignarEstancia']);
        Route::delete('/estancia/{id}', [EstanciaController::class, 'eliminarEstancia']);
        Route::put('/alumnos/{idAlumno}/asignar-instructor', [AlumnoController::class, 'asignarInstructor']);
        Route::post('/grado/{gradoId}/entregas', [EntregaCuadernoController::class, 'store']);
        Route::delete('/grado/{gradoId}/entregas/{entregaId}', [EntregaCuadernoController::class, 'destroy']);
        Route::post('/nota-cuaderno', [NotaCuadernoController::class, 'notaCuaderno']);
        Route::post('/observacionesCuadernoAlumno', [NotaCuadernoController::class, 'observacionesCuadernoAlumno']);
        Route::get('/tutor/{id}/grados', [TutorController::class, 'grados']);
        Route::get('/tutor/{id}/notas-cuaderno', [NotaCuadernoController::class, 'notasPorTutor']);
        Route::get('/mi-grado/gestion', [GradoController::class, 'getDatosGestionTutor']);
        Route::post('/alumnos/{idAlumno}/nota-egibide', [AlumnoController::class, 'guardarNotaEgibide']);
    });

    // ========================================
    // RUTAS SOLO INSTRUCTOR
    // ========================================
    Route::middleware('can:es-instructor')->group(function () {
        Route::post('/alumnos/{idAlumno}/notas', [NotasEmpresaController::class, 'store']);
        Route::get('/alumnos/{idAlumno}/notas', [NotasEmpresaController::class, 'show']);
        Route::post('/seguimiento', [SeguimientoController::class, 'crearSeguimiento']);
        Route::put('/seguimiento/{id}', [SeguimientoController::class, 'ModificarSeguimiento']);
        Route::delete('/seguimiento/{id}', [SeguimientoController::class, 'eliminarSeguimiento']);
        Route::post('/estancias/{estancia}/competencias', [EstanciaCompetenciaController::class, 'create']);
        Route::delete('estancias/{estanciaId}/competencias/{competenciaId}', [EstanciaCompetenciaController::class, 'delete']);
        Route::put('/alumnos/{alumnoId}/competencias/{competenciaId}/nota', [NotasCompetenciaController::class, 'guardarNota']);
        Route::put('/alumnos/{idAlumno}/transversales/{transversalId}/nota', [TransversalController::class, 'actualizarNotaTransversal']);
    });

    // ========================================
    // RUTAS SOLO ALUMNO
    // ========================================
    Route::middleware('can:es-alumno')->group(function () {
        Route::get('/alumno/{id}/estancia', [EstanciaController::class, 'getEstanciaActual']);
        Route::get('/entregas/alumno/{id}', [EntregaCuadernoController::class, 'entregasAlumno']);
        Route::post('/entregarCuaderno/alumno/{id}', [AlumnoEntregaController::class, 'entregarCuaderno']);
        Route::get('/alumno/entregas/descargar/{id}', [AlumnoEntregaController::class, 'descargarCuaderno']);
        Route::get('/alumno/{id}/mis-notas', [AlumnoController::class, 'misNotas']);
        Route::get('/alumno/{id}/mis-notasAlumno', [AlumnoController::class, 'misNotasAlumno']);
    });

    // ========================================
    // RUTAS COMPARTIDAS (múltiples roles)
    // ========================================

    Route::get('/alumno/{id}', [AlumnoController::class, 'getGrado']);
    Route::get('/allempresas', [EmpresaController::class, 'getCompanys']);
    Route::get('/instructores/{id}/alumnos', [AlumnoController::class, 'alumnosDeInstructor']);
    Route::get('/empresa/{cif}/instructores', [InstructorController::class, 'getCompanyInstructor']);
    Route::get('/tutores/{id}/alumnos', [AlumnoController::class, 'alumnosDeTutor']);
    Route::get('/tutor/alumnos-sin-asignar', [AlumnoController::class, 'alumnosSinAsignarParaTutor']);
    Route::get('/alumno/{id}/mis-notas', [AlumnoController::class, 'misNotas']);
    Route::put('/alumnos/{idAlumno}/desasignar-instructor', [AlumnoController::class, 'desasignarInstructor']);


    Route::post('compRa/create', [CompRaController::class, 'createOrDelete']);
    // Tutor + Instructor pueden ver empresa/alumnos
    Route::get('/empresa/{cif}/alumnos', [EstanciaController::class, 'getCompanyAlumnos']);

    // Tutor + Alumno pueden ver entregas por grado
    Route::get('/grado/{id}/entregas', [EntregaCuadernoController::class, 'porGrado']);

    // Instructor + Tutor pueden ver seguimientos
    Route::get('/estancia/{id}/seguimientos', [SeguimientoController::class, 'index']);

    // Varios pueden consultar grados/asignaturas/competencias
    Route::get('/grados/{id}/asignaturas', [GradoController::class, 'getAsignaturas']);
    Route::get('/grados/{id}/competencias', [GradoController::class, 'getCompetencias']);
    Route::get('/asignaturas/{id}/ras', [AsignaturaController::class, 'getRas']);
    Route::get('/grado/{id}/matriz-competencias/', [CompRaController::class, 'getCompRa']);
    Route::get('/estancias/{id}/competencias', [EstanciaController::class, 'competencias']);
    Route::get('/transversales', [TransversalController::class, 'getTransversales']);
    Route::get('/transversales/alumno/{idAlumno}', [TransversalController::class, 'getTransversalesAlumno']);
});
