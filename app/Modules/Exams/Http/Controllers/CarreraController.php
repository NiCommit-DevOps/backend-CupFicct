<?php

namespace App\Modules\Exams\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Exams\Http\Requests\StoreCarreraRequest;
use App\Modules\Exams\Http\Requests\UpdateCarreraRequest;
use App\Modules\Exams\Http\Resources\CarreraResource;
use App\Modules\Exams\Models\Carrera;
use App\Modules\Exams\Services\CarreraService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * CU08 — Gestionar Cupos por Carrera.
 */
class CarreraController extends Controller
{
    public function __construct(private readonly CarreraService $carreras)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        return CarreraResource::collection($this->carreras->listar());
    }

    /**
     * Catálogos para los selectores del formulario (modalidades y áreas).
     */
    public function catalogos(): JsonResponse
    {
        return response()->json([
            'modalidades' => Carrera::MODALIDADES,
            'areas' => Carrera::AREAS,
        ]);
    }

    public function show(int $carrera): CarreraResource
    {
        return new CarreraResource($this->carreras->obtener($carrera));
    }

    public function store(StoreCarreraRequest $request): JsonResponse
    {
        $carrera = $this->carreras->crear($request->validated());

        return (new CarreraResource($carrera))->response()->setStatusCode(201);
    }

    public function update(UpdateCarreraRequest $request, int $carrera): CarreraResource
    {
        return new CarreraResource($this->carreras->actualizar($carrera, $request->validated()));
    }

    public function destroy(int $carrera): JsonResponse
    {
        $this->carreras->eliminar($carrera);

        return response()->json(['message' => 'Carrera eliminada correctamente.']);
    }
}
