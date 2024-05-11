<?php

namespace App\Helpers;

use App\Jobs\sendLog;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;

class Log
{
    private $user;

    public function __construct()
    {
    }

    /**
     * @param int $type
     * @param int $system
     * @param string $msg
     * @return void
     */
    public function createLog(int $type, int $system, string $msg, int $identify = null)
    {

        $pathName = storage_path("trace/");

        $systemOptons = ['ticketk', 'webhook_cobranca', 'mensageria', 'payment', "moviment", 'webhook_email'];

        $fileName = $systemOptons[$system] . ".log";

        if (!is_dir($pathName)) {
            mkdir($pathName, 0775, true);
        }
        if (is_file($pathName . $fileName) && (filesize($pathName . $fileName) > 10000000)) {
            rename($pathName . $fileName, $pathName . $systemOptons[$system ?? 0] . "_" . date("Ymd_his") . ".log");
        }
        $typeOptons = ['INFO', 'WARNING', 'ERROR', 'SUCCESS'];
        $moment = date('Y-m-d H:i:s');
        error_log($moment . " [" . $typeOptons[$type] . "] [$identify] - {$msg}\n", 3, $pathName . $fileName);

        $data = [
            "app" => $systemOptons[$system ?? 00],
            "moment" => $moment,
            "type" => $typeOptons[$type],
            "identifier" => $identify,
            "log" => $msg
        ];

        sendLog::dispatch($data);

    }

}
