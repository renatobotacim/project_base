<?php

namespace App\Services;

use App\Models\Role;
use App\Models\RoleHasUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\CustomValidationException;
use App\Repositories\ComarcaRepositoryInterface;

use Symfony\Component\HttpFoundation\Response as HTTP_RESPONSE;

class Service
{

    //LEVEL USER
    const USER_LEVEL_ADMIN = 3;
    const USER_LEVEL_PRODUCER = 2;
    const USER_LEVEL_USER = 1;

    //ROLES
    const ROLES_ACESSAR_DADOS_GERAIS_DOS_EVENTOS = 1;
    const ROLES_CRIAR_NOVOS_EVENTOS = 2;
    const ROLES_EDITAR_EVENTOS_CADASTRADOS = 3;
    const ROLES_ACESSAR_PERMISSOES = 4;
    const ROLES_ACESSAR_CARTEIRA_DIGITAL = 5;
    const ROLES_EMISSAO_DE_CORTESIAS = 6;
    const ROLES_VALIDACAO_DE_INGRESSOS = 13;

    //GET USER
    const GET_USER_ID = 1;
    const GET_USER_OBJECT = 2;
    const GET_USER_LEVEL = 3;
    const GET_USER_PRODUCER = 4;

    const RATE_TICKET = 0.135;
    const RATE_VALUE_MIN = 5;

    const LOG_INFO = 0;
    const LOG_WARNING = 1;
    const LOG_ERROR = 2;
    const LOG_SUCCESS = 3;

    /**
     * function for clear strings
     * @param $tipe
     * @param $string
     * @return array|string|string[]|null
     */
    public function cleanString($tipe, $string): array|string|null
    {
        $caracteres_sem_acento = array(
            'Š' => 'S', 'š' => 's', 'Ð' => 'Dj', 'Â' => 'Z', 'Â' => 'z', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A',
            'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I',
            'Ï' => 'I', 'Ñ' => 'N', 'Å' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U',
            'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
            'å' => 'a', 'æ' => 'a', 'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i',
            'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'Å' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'ù' => 'u',
            'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ý' => 'y', 'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y', 'ƒ' => 'f',
            'Ä' => 'a', 'î' => 'i', 'â' => 'a', 'È' => 's', 'È' => 't', 'Ä' => 'A', 'Î' => 'I', 'Â' => 'A', 'È' => 'S', 'È' => 'T',
        );
        return preg_replace("/[^0-9]/", "", strtr($string, $caracteres_sem_acento));
    }

    /**
     * @param string $type
     * @param int $lent
     * @return string
     */
    public function generateHash(string $type, int $lent): string
    {
        //create eventHash
        $letras = array('A', 'Q', 'B', 'W', 'C', 'E', 'D', 'R', 'X', 'T', 'F', 'Y', 'G', 'U', 'O', 'P', 'I', 'Z', 'V', 'M');
        $resultado = '';
        while ($lent > 0) {
            if (rand(0, 9) % 2 == 0) {
                $resultado .= rand(0, 9); // sorteia valores entre 0-9
            } else {
                $resultado .= $letras[rand(0, 19)]; // retorna do array a letra pela chave
            }
            $lent--;
        }

        return $type . $resultado;
    }

    /**
     * @param $text
     * @param string $divider
     * @return string
     */
    public function slugify($text, string $divider = '-'): string
    {
        // replace non letter or digits by divider
        $text = preg_replace('~[^\pL\d]+~u', $divider, $text);
        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);
        // trim
        $text = trim($text, $divider);
        // remove duplicate divider
        $text = preg_replace('~-+~', $divider, $text);
        // lowercase
        $text = strtolower($text);
        if (empty($text)) {
            return 'n-a';
        }
        return $text;
    }

    /**
     * @param $start
     * @param $end
     * @return float
     */
    public function countDays($start, $end): float
    {
        return floor((strtotime($end) - strtotime($start)) / (60 * 60 * 24));
    }


    /**
     * @param int $permision
     * @param int $level
     * @return bool
     */
    public function checkPermission(int $permision, int $level): bool
    {
        $permissions = [];
        //check level
        if ($this->myUser(self::GET_USER_LEVEL) >= self::USER_LEVEL_PRODUCER) {

            //check permissions
            $roles = DB::select("select role_id from roles_has_users where user_id = " . $this->myUser(self::GET_USER_ID));

            foreach ($roles as $item) {
                $permissions[] = $item->role_id;
            }
            return in_array($permision, $permissions);
        }
        return false;
    }

    /**
     * @param $param
     * @return Authenticatable|int|string|void|null
     */
    public function myUser($param)
    {
        /*
         * 1: ID
         * 2: OBJECT
         * 3: LEVEL
         * 4: PRODUCER ID
         */
        switch ($param) {
            case 1:
                return Auth::id();
                break;
            case 2:
                return Auth::user();
                break;
            case 3:
                return Auth::user()->level;
                break;
            case 4:
                return Auth::user()->producer_id;
                break;
        }
    }

    /**
     * @param object|array|null $data
     * @param string|null $message
     * @return JsonResponse
     */
    public function returnRequestSucess(object|array|null $data, string $message = null): JsonResponse
    {
        return response()->json(
            [
                'message' => $message ?? 'Solicitação concluída com sucesso!',
                'data' => $data
            ], HTTP_RESPONSE::HTTP_OK
        );
    }


    /**
     * @param array|object $data
     * @param string|null $message
     * @param int|null $code
     * @return JsonResponse
     */
    public function returnRequestWarning(array|object $data, string $message = null, int $code = null): JsonResponse
    {
        return response()->json(
            [
                'message' => $message ? __($message) : __('Unable to update record. Try again!'),
                'data' => $data
            ], $code ?? 401
        );
    }

    /**
     * @param array|object $erro
     * @param string|null $message
     * @return JsonResponse
     */
    public function returnRequestError(array|object $erro, string $message = null): JsonResponse
    {

        //registar log

        //enviar notificação no zap

        return response()->json(
            [
                'message' => $message ?? __('OPSS! An internal error has occurred. Try again later.'),
                'error' => (array) $erro
            ], HTTP_RESPONSE::HTTP_INTERNAL_SERVER_ERROR
        );
    }

}
