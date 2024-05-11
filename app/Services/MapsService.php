<?php

namespace App\Services;

use App\Models\Maps;
use App\Repositories\MapsRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as HTTP_RESPONSE;

class MapsService extends Service
{

    private MapsRepositoryInterface $repository;
    private SectorService $sectorService;

    public function __construct(
        MapsRepositoryInterface $repository,
        SectorService           $sectorService
    )
    {
        $this->repository = $repository;
        $this->sectorService = $sectorService;
    }

    /**
     * show list records for model
     * @param int $paginate
     * @param int $page
     * @param string $search
     * @return JsonResponse
     */
    public function index(int $paginate, int $page, string $search): JsonResponse
    {
        try {
            $data = $this->repository->index($paginate, $page, $search, $this->myUser(self::GET_USER_PRODUCER));
            return $this->returnRequestSucess($data);
        } catch (\Exception $e) {
            return $this->returnRequestError((array)$e);
        }
    }

    /**
     * List users according paramiter ID
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $data = $this->repository->show($id);
            return $this->returnRequestSucess($data);
        } catch (\Exception $e) {
            return $this->returnRequestError((array)$e);
        }
    }

    /**
     * Method created to register new users
     * @param array $data
     * @return JsonResponse
     */
    public function store(array $data): JsonResponse
    {
        try {
            $data['producer_id'] = $this->myUser(self::GET_USER_PRODUCER);
            $map = $this->repository->store($data);
            $map['sectors'] = $this->repository->storeSectors($map, $data['sectors']);
            if (!$map) {
                return $this->returnRequestWarning($data, 'Não foi possível realizar o registro. Tente novamente', HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
            }
            return $this->returnRequestSucess($map);
        } catch (\Exception $e) {
            return $this->returnRequestError((array)$e);
        }
    }

    /**
     * Method created to change the record with id passed as parameter.
     * @param int $id
     * @param array $data
     * @return JsonResponse
     */
    public function update(int $id, array $data): JsonResponse
    {
        try {
            $update = $this->repository->update($id, $data);

            if (!$update) {
                return response()->json($data, __('Unable to update record. Try again!'), HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
            }

            $map = $this->repository->show($id);

            $sectoresRegistreds = $map->sectors->pluck('id')->toArray();
            $sectoresUpdate = [];
            $dataNewSectores = [];

            foreach ($data['sectors'] as $sector) {

                if (isset($sector['id'])) {
                    if (in_array($sector['id'], $sectoresRegistreds)) {
                        $this->sectorService->update($sector['id'], $sector);
                        $sectoresUpdate[] = $sector['id'];
                    }
                } else {
                    $dataNewSectores[] = $sector;
                }
            }

            //apaga os setores que não estão no objeto recebido
            $sectoresDiff = array_diff($sectoresRegistreds, $sectoresUpdate);
            foreach ($sectoresDiff as $sector) {
                $this->sectorService->delete($sector);
            }

            //criar os novos setores;
            $newSectores = $this->repository->storeSectors($map, $dataNewSectores);

            $map = $this->repository->show($id);

            if (!$map) {
                return $this->returnRequestWarning($data, __('Unable to update record. Try again!'), HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
            }
            return $this->returnRequestSucess($data);
        } catch (\Exception $e) {
            return $this->returnRequestError((array)$e);
        }
    }

    /**
     * Method created to delete the record with id passed as parameter.
     * @param int $id
     * @return JsonResponse
     */
    public function delete(int $id): JsonResponse
    {
        try {

            $model = $this->repository->delete($id);

            if (!$model) {
                return response()->json(
                    [
                        'message' => __('Unable to delete record. Try again!'),
                    ], HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            return response()->json(
                [
                    'message' => __('Data delete successfully!'),
                ], HTTP_RESPONSE::HTTP_OK
            );

        } catch (\Exception $e) {
            return $this->returnRequestError((array)$e);
        }
    }

    public function checkSectors(Maps $map, $data): JsonResponse
    {
        try {

            if (!$model) {
                return response()->json(
                    [
                        'message' => __('Unable to delete record. Try again!'),
                    ], HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            return response()->json(
                [
                    'message' => __('Data delete successfully!'),
                ], HTTP_RESPONSE::HTTP_OK
            );

        } catch (\Exception $e) {
            return $this->returnRequestError((array)$e);
        }
    }

}
