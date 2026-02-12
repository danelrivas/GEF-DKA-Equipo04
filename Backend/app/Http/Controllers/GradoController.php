<?php

namespace App\Http\Controllers;

use App\Http\Services\NotasAlumnoService;
use App\Models\Grado;
use App\Models\Asignatura;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GradoController extends Controller {
    protected $notasService;

    public function __construct(NotasAlumnoService $notasService) {
        $this->notasService = $notasService;
    }

    public function getGrados(Request $request) {
        // Iniciamos la consulta
        $query = Grado::query();

        // Si recibimos el parámetro 'q' (búsqueda), filtramos
        if ($request->has('q') && $request->input('q') != '') {
            $search = $request->input('q');

            // Buscamos coincidencias en Nombre O en Curso
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'LIKE', "%{$search}%")
                    ->orWhere('curso', 'LIKE', "%{$search}%");
            });
        }

        // Paginamos el resultado (ya filtrado o completo)
        $grados = $query->orderBy('id', 'desc')->get();

        return response()->json($grados);
    }

    public function getTodosGrados() {
        $grados = Grado::all();
        return response()->json($grados);
    }

    /**
     * Crear un nuevo grado
     */
    public function crearGrado(Request $request) {
        $request->validate([
            'nombre' => 'required|string|max:150',
            'curso' => 'nullable|string|max:50'
        ], [
            'nombre.required' => 'El nombre del grado es obligatorio',
            'nombre.max' => 'El nombre no puede superar los 150 caracteres',
            'curso.max' => 'El curso no puede superar los 50 caracteres'
        ]);

        if ($request->id_tutor) {
            $existe = DB::table('grado')->where('ID_Tutor', $request->id_tutor)->exists();
            if ($existe) {
                return response()->json(['message' => 'Este tutor ya tiene un grado asignado.'], 422);
            }
        }

        $grado = Grado::create([
            'Nombre' => $request->nombre,
            'Curso' => $request->curso,
            'ID_Tutor' => $request->id_tutor
        ]);

        return response()->json([
            'message' => 'Grado creado correctamente',
            'grado' => $grado
        ], 201);
    }

    /**
     * Eliminar un grado
     */
    public function eliminarGrado($id) {
        $grado = Grado::findOrFail($id);

        // Verificar si tiene alumnos asignados
        $alumnosCount = $grado->alumnos()->count();

        if ($alumnosCount > 0) {
            return response()->json([
                'message' => "No se puede eliminar el grado porque tiene {$alumnosCount} alumno(s) asignado(s)"
            ], 422);
        }

        $grado->delete();

        return response()->json([
            'message' => 'Grado eliminado correctamente'
        ]);
    }

    // Obtener asignaturas simples
    public function getAsignaturas($id) {
        // Buscamos las asignaturas que pertenezcan a este grado
        // Asegúrate de usar 'asignaturas' si es así en tu modelo
        $asignaturas = Asignatura::where('ID_Grado', $id)->orderBy('id')->get();
        return response()->json($asignaturas);
    }

    // Obtener competencias (Grado -> Asignatura -> Ra -> CompRa -> Competencia)
    public function getCompetencias($id) {
        $grado = Grado::find($id);

        if (!$grado) {
            return response()->json([], 404);
        }

        $competencias = $grado->competencias()->orderBy('id')->get();

        return response()->json($competencias);
    }

    public function getDatosGestionTutor(Request $request) {
        $user = $request->user();

        // 1. Obtener Grado del Tutor
        $grado = DB::table('grado')->where('ID_Tutor', $user->id)->first();
        if (!$grado) return response()->json(['message' => 'Sin grado asignado'], 404);

        // 2. Obtener Asignaturas del Grado
        $asignaturas = Asignatura::where('ID_Grado', $grado->id)->get();

        // 3. Parámetros de paginación
        $perPage = $request->input('per_page', 5);
        $page = $request->input('page', 1);

        // 4. Obtener Alumnos matriculados en ese grado CON PAGINACIÓN
        $alumnosQuery = User::where('tipo', 'alumno')
            ->whereHas('alumno', function ($q) use ($grado) {
                $q->where('ID_Grado', $grado->id)
                ->whereHas('estancias');
            })
            ->with('alumno')
            ->orderBy('apellidos', 'asc');
        // Aplicar paginación
        $alumnosPaginados = $alumnosQuery->paginate($perPage, ['*'], 'page', $page);

        // 5. CALCULAR NOTAS PARA CADA ALUMNO
        $alumnosConNotas = $alumnosPaginados->getCollection()->map(function ($usuarioAlumno) use ($asignaturas) {

            $idAlumno = $usuarioAlumno->id;

            // A. Notas Globales
            $notaCuaderno    = $this->notasService->obtenerNotaCuaderno($idAlumno);
            $notaTransversal = $this->notasService->obtenerNotaTransversal($idAlumno);

            // B. Notas Técnicas por asignatura
            $notasTecnicas   = $this->notasService->obtenerNotaTecnicaPorAsignatura($idAlumno, $asignaturas);

            // C. Notas de Empresa
            $notasEmpresa    = $this->notasService->calcularNotaFinalEmpresa($notaCuaderno, $notaTransversal, $notasTecnicas);

            // D. Notas de Egibide
            $notasEgibide    = $this->notasService->obtenerNotasEgibide($idAlumno);

            // E. NOTA FINAL ABSOLUTA
            $notasFinales    = $this->notasService->calcularNotasFinalesPorAsignatura($notasEmpresa, $notasEgibide);

            // F. Empaquetar notas
            $packNotas = [];
            foreach ($asignaturas as $asig) {
                $id = $asig->id;
                $packNotas[$id] = [
                    'cuaderno'    => $notaCuaderno,
                    'transversal' => $notaTransversal,
                    'tecnica'     => $notasTecnicas[$id] ?? '-',
                    'egibide'     => $notasEgibide[$id] ?? '-',
                    'nota_empresa_calculada' => $notasEmpresa[$id] ?? '-',
                    'final'       => $notasFinales[$id] ?? '-'
                ];
            }

            $usuarioAlumno->notas_calculadas = $packNotas;

            return $usuarioAlumno;
        });

        // Reemplazar la colección con la versión procesada
        $alumnosPaginados->setCollection($alumnosConNotas);

        return response()->json([
            'grado' => $grado,
            'alumnos' => $alumnosPaginados,
            'asignaturas' => $asignaturas
        ]);
    }
}
