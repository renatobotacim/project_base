<?php

namespace App\Services;

use App\Mail\RegisterUser;
use App\Repositories\CouponRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response as HTTP_RESPONSE;

class CouponService extends Service
{

    private CouponRepositoryInterface $repository;

    public function __construct(
        CouponRepositoryInterface $repository,
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
     * @param int|null $id
     * @param string|null $coupon
     * @return JsonResponse
     */
    public function show(int $id = null, string $coupon = null, int $eventId = null): JsonResponse
    {
        try {

            if (isset($id) && empty($id)) {
                $data = $this->repository->show($id);
            } else {
                $data = $this->repository->show(null, $coupon, $eventId);
            }

            return $this->returnRequestSucess($data);
        } catch (\Exception $e) {
            return $this->returnRequestError($e);
        }
    }

    /**
     * @param string $coupon
     * @param int $eventId
     * @return JsonResponse
     */
    public function apply(string $coupon, int $eventId): JsonResponse
    {

        try {

            $couponData = $this->repository->show(null, $coupon, $eventId);

            if (!$couponData) {
                return response()->json(
                    [
                        'message' => 'Esse cupom não existe!',
                        'data' => []
                    ], HTTP_RESPONSE::HTTP_OK
                );
            }

            if ($couponData->expiration < now() || (($couponData->amount_use + $couponData->amount_reserved) >= $couponData->amount)) {
                return response()->json(
                    [
                        'message' => 'Esse cupom não está mais disponível',
                        'data' => []
                    ], HTTP_RESPONSE::HTTP_OK
                );
            }

            $couponData->increment('amount_reserved', 1);

            return response()->json(
                [
                    'message' => 'Dados registrados com sucesso!',
                    'data' => $couponData->dedution
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
     * @param string $coupon
     * @param int $eventId
     * @return JsonResponse
     */
    public function execute(string $coupon, int $eventId): JsonResponse
    {

        try {

            $couponData = $this->repository->show(null, $coupon, $eventId);
            if (!$couponData) {
                return $this->returnRequestWarning([], 'Esse cupom não existe!', HTTP_RESPONSE::HTTP_BAD_REQUEST);
            }

            if ($couponData->expiration < now() || (($couponData->amount_use + $couponData->amount_reserved - 1) >= $couponData->amount) || $couponData->amount_reserved < 1 || $couponData->status == 'inactive') {
                return $this->returnRequestWarning([], 'Esse cupom não está mais disponível', HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
            }

            $couponData->increment('amount_use', 1);
            $couponData->decrement('amount_reserved', 1);

            return $this->returnRequestSucess($couponData);

        } catch (\Exception $e) {
            return $this->returnRequestError($e);
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

            $model = $this->repository->store($data);

            if (!$model) {
                return response()->json(
                    [
                        'status' => false,
                        'message' => 'Não foi possível realizar o registro. Tente novamente',
                        'data' => $data
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
