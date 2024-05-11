<?php

namespace App\Jobs;

use App\Mail\Notification;
use App\Mail\RecoverPass;
use App\Mail\RegisterUser;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class sendLog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    private array $data;

    /**
     * Create a new job instance.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function handle(): void
    {
        $response = Http::post('https://logger.i9tasks.com.br/api/' . env('TOKEN_LOGS') . '/log/store', [

            'form_params' => $this->data,
            'headers' => [
                "Accept: Accept",
                "content-type: application/json"
            ]
        ]);
    }
}
