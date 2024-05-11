<?php

namespace App\Services;

use App\Mail\RegisterUser;
use App\Repositories\OwnerRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response as HTTP_RESPONSE;

class OwnerService extends Service
{

    private OwnerRepositoryInterface $repository;
    private AddressService $addressService;

    public function __construct(
        OwnerRepositoryInterface $repository,
        AddressService           $addressService
    )
    {
        $this->repository = $repository;
        $this->addressService = $addressService;
    }

    /**
     * show list records for model
     * @param int $paginate
     * @param int $page
     * @param string $search
     * @return object
     */
    public function index(int $paginate, int $page, string $search): object
    {
        try {
            return $this->repository->index($paginate, $page, $search);
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
     * @return object|null
     */
    public function show(int $id): object|null
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
     * Method created to register new users
     * @param array $data
     * @return JsonResponse
     */
    public function store(array $data): JsonResponse
    {
        try {

            $addressData = $data['address'];
            unset($data['address']);

            DB::beginTransaction();

            $address = $this->addressService->store($addressData);

            if ($address->status() == 200) {

                $data['address_id'] = json_decode($address->content())->data->id;

                $model = $this->repository->store($data);
                if (!$model) {
                    DB::rollBack();
                    return response()->json(
                        [
                            'status' => false,
                            'message' => 'Não foi possível realizar o registro devido a inconsistências na requisição. Verifique os dados e tente novamente',
                        ], HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY
                    );
                }
            } else {
                DB::rollBack();
                return response()->json(
                    [
                        'status' => false,
                        'message' => 'Não foi possível realizar o registro devido a inconsistências nos dados do endereço. Verifique os dados e tente novamente',
                    ], HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            DB::commit();

            return response()->json(
                [
                    'message' => 'Dados registrados com sucesso!',
                    'data' => $model
                ], HTTP_RESPONSE::HTTP_OK
            );

        } catch (\Exception $e) {
            return response()->json(
                [
                    'message' => __('OPSS! An internal error has occurred. Try again later.'),
                ], HTTP_RESPONSE::HTTP_INTERNAL_SERVER_ERROR
            );
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

            //get data the old owner
            $ownerOld = $this->show($id);

            if (!$ownerOld) {
                return response()->json(
                    [
                        'status' => false,
                        'message' => __('O proprietário informado não foi localizado. Verifique os dados e tente novamente!'),
                    ], HTTP_RESPONSE::HTTP_NOT_FOUND
                );
            }

            DB::beginTransaction();

            if (isset($data['address'])) {
                if ((!isset($data['address_id']) || !isset($data['address']['id']))) {
                    $addressId = $ownerOld['address_id'];
                } else {
                    $addressId = $data['address_id'];
                }
                $addressNew = $this->addressService->update($addressId, $data['address']);

                if (!$addressNew) {
                    DB::rollBack();
                    return response()->json(
                        [
                            'status' => false,
                            'message' => __('Unable to update record. Try again!'),
                        ], HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY
                    );
                }
            }

            $model = $this->repository->update($id, $data);

            if (!$model) {
                DB::rollBack();
                return response()->json(
                    [
                        'status' => false,
                        'message' => __('Unable to update record. Try again!'),
                        'data' => $data
                    ], HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            DB::commit();
            return response()->json(
                [
                    'message' => __('Data updated successfully!'),
                    'data' => $data
                ], HTTP_RESPONSE::HTTP_OK
            );

        } catch (\Exception $e) {
            return response()->json(
                [
                    'message' => __('OPSS! An internal error has occurred. Try again later.'),
                ], HTTP_RESPONSE::HTTP_INTERNAL_SERVER_ERROR
            );
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
            return response()->json(
                [
                    'message' => __('OPSS! An internal error has occurred. Try again later.'),
                ], HTTP_RESPONSE::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

}
