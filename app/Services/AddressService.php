<?php

namespace App\Services;

use App\Mail\RegisterUser;
use App\Repositories\AddressRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response as HTTP_RESPONSE;

class AddressService extends Service
{

    private AddressRepositoryInterface $repository;

    public function __construct(
        AddressRepositoryInterface $repository,
    )
    {
        $this->repository = $repository;
    }

    /**
     * show list records for model
     * @param int $paginate
     * @param int $page
     * @param string $search
     * @return object
     */

    /**
     * @param int $paginate
     * @param int $page
     * @param string $search
     * @return object
     */
    public function index(int $paginate, int $page, string $search): object
    {
        try {
            return $this->repository->index($paginate, $page, $search, $this->myUser(self::GET_USER_PRODUCER));
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
     * @OA\Get(
     *     tags={"Address"},
     *     path="/show/{id}",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *           description="Task id",
     *           in="path",
     *           name="ID",
     *           required=true,
     *           @OA\Schema(
     *               type="integer",
     *           )
     *      ),
     *     @OA\Response(response="200", description="An example endpoint")
     * )
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
     * Method created to register new users
     * @param array $data
     * @return JsonResponse
     * @OA\Post(
     *     tags={"Address"},
     *     path="/create",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="title", type="string"),
     *              @OA\Property(property="description", type="string"),
     *              @OA\Property(property="expired_at", type="string"),
     *              @OA\Property(property="user_id", type="string"),
     *              ),
     *      ),
     * @OA\Response(response="200", description="An example endpoint")
     * )
     */


    public function store(array $data): JsonResponse
    {
        try {

            $user = $this->myUser(self::GET_USER_PRODUCER);

            if ($user) {
                $data['producer_id'] = $user;
            }

            $model = $this->repository->store($data);
            $model->city;

            if (!$model) {
                return response()->json(
                    [
                        'status' => false,
                        'message' => 'Não foi possível realizar o registro. Tente novamente',
                        'data' => $model
                    ], HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY
                );
            }

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

            $model = $this->repository->update($id, $data);

            if (!$model) {
                return response()->json(
                    [
                        'status' => false,
                        'message' => __('Unable to update record. Try again!'),
                        'data' => $data
                    ], HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            $address = $this->repository->show($id);

            return response()->json(
                [
                    'message' => __('Data updated successfully!'),
                    'data' => $address
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
