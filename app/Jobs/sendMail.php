<?php

namespace App\Jobs;

use App\Mail\Notification;
use App\Mail\RecoverPass;
use App\Mail\RegisterUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class sendMail implements ShouldQueue
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
     */
    public function handle(): void
    {
        if(isset($this->data['type']) && $this->data['type'] == "recoverPass"){


            $email = new \SendGrid\Mail\Mail();
            $email->setFrom("sistema@ticketk.com.br", "TicketK");
            $email->setSubject("Sending with SendGrid is Fun");
            $email->addTo("rbotacim@gmail.com", "Renato Botacim");
            //$email->addContent("text/plain", "and easy to do anywhere, even with PHP");
            $email->addContent(
                "text/html",  new Content(
                view: 'mail.recoverPassword',
                with: [
                    'data' => $this->data1,
                ],
            )
            );
            $sendgrid = new \SendGrid(getenv('SENDGRID_API_KEY'));
            try {
                $response = $sendgrid->send($email);
                print $response->statusCode() . "\n";
                print_r($response->headers());
                print $response->body() . "\n";
            } catch (Exception $e) {
                echo 'Caught exception: '. $e->getMessage() ."\n";
            }




        }

        if(isset($this->data['type']) && $this->data['type'] == "registerUser"){
            Mail::to($this->data['userEmail'], $this->data['userName'])->send(new RegisterUser($this->data));
        }

        if(isset($this->data['type']) && $this->data['type'] == "notification"){
            Mail::to($this->data['userEmail'], $this->data['userName'])->send(new Notification($this->data));
        }

        //Mail::to($this->data['userEmail'], $this->data['userName'])->send(new RegisterUser($this->data));
    }
}
