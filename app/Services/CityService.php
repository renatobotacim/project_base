<?php

namespace App\Services;

use App\Repositories\CityRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as HTTP_RESPONSE;

class CityService extends Service
{

    private CityRepositoryInterface $repository;

    public function __construct(
        CityRepositoryInterface $repository,
    )
    {
        $this->repository = $repository;
    }

    /**
     * show list records for model
     * @return JsonResponse
     */
    public function index1(): JsonResponse
    {

        try {

            $dataCity = $this->repository->index();

            $dataState = [
                [1, 'Acre', 'AC', 'Norte'],
                [2, 'Alagoas', 'AL', 'Nordeste'],
                [3, 'Amapá', 'AP', 'Norte'],
                [4, 'Amazonas', 'AM', 'Norte'],
                [5, 'Bahia', 'BA', 'Nordeste'],
                [6, 'Ceará', 'CE', 'Nordeste'],
                [7, 'Distrito Federal', 'DF', 'Centro-Oeste'],
                [8, 'Espírito Santo', 'ES', 'Sudeste'],
                [9, 'Goiás', 'GO', 'Centro-Oeste'],
                [10, 'Maranhão', 'MA', 'Nordeste'],
                [11, 'Mato Grosso', 'MT', 'Centro-Oeste'],
                [12, 'Mato Grosso do Sul', 'MS', 'Centro-Oeste'],
                [13, 'Minas Gerais', 'MG', 'Sudeste'],
                [14, 'Pará', 'PA', 'Nordeste'],
                [15, 'Paraíba', 'PB', 'Nordeste'],
                [16, 'Paraná', 'PR', 'Sul'],
                [17, 'Pernambuco', 'PE', 'Nordeste'],
                [18, 'Piauí', 'PI', 'Nordeste'],
                [19, 'Rio de Janeiro', 'RJ', 'Sudeste'],
                [20, 'Rio Grande do Norte', 'RN', 'Nordeste'],
                [21, 'Rio Grande do Sul', 'RS', 'Sul'],
                [22, 'Rondônia', 'RO', 'Norte'],
                [23, 'Roraima', 'RR', 'Norte'],
                [24, 'Santa Catarina', 'SC', 'Sul'],
                [25, 'São Paulo', 'SP', 'Sudeste'],
                [26, 'Sergipe', 'SE', 'Nordeste'],
                [27, 'Tocantins', 'TO', 'Norte']
            ];

            foreach ($dataState as $x) {
                $aa[] = [
                    'name' => $x['1'],
                    "type" => "state"
                ];
            }
            $dataCity[] = $aa;

            foreach ($dataState as $x) {
                $aa[] = [
                    'name' => $x['2']
                ];
            }
            $dataCity[] = $aa;


            return $this->jsonResponse([
                'message' => __('Record queried successfully'),
                'data' => $dataCity
            ], HTTP_RESPONSE::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json(
                [
                    'message' => __('OPSS! An internal error has occurred. Try again later.'),
                ], HTTP_RESPONSE::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * List users according paramiter ID
     * @param int $id
     * @return JsonResponse
     */
    public function index(string $search): JsonResponse
    {
        try {
            return response()->json([
                'message' => __('Record queried successfully'),
                'data' => $this->repository->index($search)
            ], HTTP_RESPONSE::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'message' => __('OPSS! An internal error has occurred. Try again later.'),
                ], HTTP_RESPONSE::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * List users according paramiter ID
     * @param int $id
     * @return object
     */
    public function show(int $id): object
    {
        try {
            return $this->repository->show($id);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'message' => __('OPSS! An internal error has occurred. Try again later.'),
                ], HTTP_RESPONSE::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * List users according paramiter ID
     * @param int $id
     * @return object
     */
    public function states($state): object
    {
        try {
            return $this->repository->states($state);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'message' => __('OPSS! An internal error has occurred. Try again later.'),
                ], HTTP_RESPONSE::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
