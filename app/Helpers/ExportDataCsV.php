<?php

namespace App\Helpers;

use App\Models\Event;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as HTTP_RESPONSE;

class ExportDataCsV
{

    private string $name;
    private array $header;
    private array $data;
    private string $file;

    /**
     * @param string $name
     * @param array $header
     * @param array $data
     * @return string
     */
    public function getExport(string $name, array $header, $data)
    {
        $this->name = $name;
        $this->header = $header;
        $this->data = $data;
        $this->export();
        return $this->file;
    }

    /**
     * @return void
     */
    private function export()
    {

        // Aceitar csv ou texto
        header('Content-Type: text/csv; charset=utf-8');
        // Nome arquivo
        header('Content-Disposition: attachment; filename=arquivo.csv');
        // Gravar no buffer
        $file = fopen("temp/{$this->name}.csv", 'w');

        // Criar o cabeçalho do Excel - Usar a função mb_convert_encoding para converter carateres especiais
        $header = [];
        foreach ($this->header as $x) {
            $aux = mb_convert_encoding($x, 'ISO-8859-1', 'UTF-8');
            array_push($header, $aux);
        }

        // Escrever o cabeçalho no arquivo
        fputcsv($file, $header, ';');

        // Ler os registros retornado do banco de dados
        foreach ($this->data as $x) {
            fputcsv($file, $x, ';');
        }

        fclose($file);

        $this->file = base64_encode(file_get_contents("temp/{$this->name}.csv"));

        //  unlink("temp/{$this->name}.csv");

    }
}
