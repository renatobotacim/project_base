<?php

namespace App\Services;

use App\Helpers\Log;
use App\Repositories\AddressRepositoryInterface;
use App\Repositories\MapsRepositoryInterface;
use App\Repositories\CategoryRepositoryInterface;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\Response as HTTP_RESPONSE;

class CommonService extends Service
{

    private AddressRepositoryInterface $addressRepository;
    private MapsRepositoryInterface $mapsRepository;
    private CategoryRepositoryInterface $categoryRepository;

    public function __construct(
        AddressRepositoryInterface  $addressRepository,
        MapsRepositoryInterface     $mapsRepository,
        CategoryRepositoryInterface $categoryRepository,
    )
    {
        $this->addressRepository = $addressRepository;
        $this->mapsRepository = $mapsRepository;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @return object
     */
    public function showOptions(): object
    {

        try {

            $address = $this->addressRepository->index(1000, 1, '', $this->myUser(self::GET_USER_PRODUCER));
            $maps = $this->mapsRepository->index(1000, 1, '', $this->myUser(self::GET_USER_PRODUCER));
            $categories = $this->categoryRepository->index(1000, 1, '');

            return (object)[
                'address' => $address,
                'maps' => $maps,
                'categories' => $categories,
            ];

        } catch (\Exception $e) {
            return response()->json(
                [
                    'message' => __('OPSS! An internal error has occurred. Try again later.'),
                ], HTTP_RESPONSE::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @param string $size
     * @param string $data
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Foundation\Application|\Illuminate\Http\Response
     */
    public function generateQrCode(string $size, string $data)
    {
        $qrCodeData = QrCode::size($size)->format('svg')->generate($data);
        return response($qrCodeData)->header('Content-type', 'image/svg+xml');
    }

    /**
     * @param string $zipcode
     * @return object
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getAddressCep(string $zipcode): object
    {
        try {
            $cli = new Client();
            $result = $cli->request('GET', "http://viacep.com.br/ws/{$zipcode}/json", [
                'form_params' => null
            ]);
            return response()->json(json_decode($result->getBody()), HTTP_RESPONSE::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'message' => __('OPSS! An internal error has occurred. Try again later.'),
                ], HTTP_RESPONSE::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @return JsonResponse
     */
    public function versionApp(): JsonResponse
    {
        try {
            return $this->returnRequestSucess(['version' => '1.0.15']);
        } catch (\Exception $e) {
            return $this->returnRequestError((array)$e);
        }
    }

    public function genereateLogSendgrid( $data)
    {
        try {
            $log = new Log();
            $log->createLog(0, 5, "Email aberto - {$data['email']} : {$data['ip']} [{$data['sg_event_id']}] - {$data['useragent']}");
        } catch (\Exception $e) {
            return $this->returnRequestError((array)$e);
        }
    }

}
