<?php

namespace App\Helpers;

use App\Models\Event;
use App\Repositories\CouponRepositoryInterface;
use GuzzleHttp\Client;
use http\Env\Response;
use Illuminate\Support\Facades\DB;

class SendMail
{

    private array $data;
    private string $view;
    private string $subject;
    private $response;

    private Log $log;

    public function __construct($data)
    {
        $this->data = $data;
        $this->log = new Log();
    }

    public function sendRecoverPass()
    {
        $this->log->createLog(0, 2, "------------INICIANDO ENVIO DE EMAIL - Recuperação de senha");
        $this->view = 'mail.recoverPassword';
        $this->subject = 'Recuperação de Senha';
        $this->sendMail();


        return $this->response;
    }

    public function sendNotification()
    {
        $this->log->createLog(0, 2, "------------INICIANDO ENVIO DE EMAIL - Notifiicação");
        $this->view = 'mail.notification';
        $this->subject = $this->data['title'];
        $this->sendMail();
        return $this->response;
    }

    public function sendCode()
    {
        $this->log->createLog(0, 2, "------------INICIANDO ENVIO DE EMAIL - Envio de Código");
        $this->view = 'mail.sendCode';
        $this->subject = 'Código de verificação para retirada';
        $this->sendMail();
        return $this->response;
    }

    public function sendRegisterUser()
    {
        $this->log->createLog(0, 2, "------------INICIANDO ENVIO DE EMAIL - Cadastro Usuário");
        $this->view = 'mail.registerUser';
        $this->subject = "Confirmação de cadastro na plataforma Ticketk";
        $this->sendMail();
        return $this->response;
    }

    public function sendRegisteProducer()
    {
        $this->log->createLog(0, 2, "------------INICIANDO ENVIO DE EMAIL - Cadastro Produtor");
        $this->view = 'mail.registerProducer';
        $this->subject = "Confirmação de cadastro de produtor na plataforma Ticketk";
        $this->sendMail();
        return $this->response;
    }

    public function sendUpdateProducerOwner()
    {
        $this->log->createLog(0, 2, "------------INICIANDO ENVIO DE EMAIL - Alteração do Socio/representante");
        $this->view = 'mail.updateProducerOwner';
        $this->subject = "Alteração de Sócio / Representante Produtor na plataforma Ticketk";
        $this->sendMail();
        return $this->response;
    }


    private function sendMail()
    {
        $this->log->createLog(0, 2, "Disparando Email");
        $email = new \SendGrid\Mail\Mail();
        $email->setFrom("sistema@ticketk.com.br", "TicketK");
        $email->setSubject($this->subject);
        $email->addTo($this->data['userEmail'], $this->data['userName'] ?? "Usuário");
        $email->addContent("text/plain", $this->subject);
        $email->addContent(
            "text/html", view($this->view, ['data' => $this->data])
        );
        $sendgrid = new \SendGrid();

        try {
            $this->response = $sendgrid->send($email);
            $id = explode(': ', $this->response->headers()[5]);
            $this->log->createLog(0, 2, "Email enviado com sucesso! [{$id[1]}] {$this->data['userEmail']} ");
        } catch (\Exception $e) {
            $this->log->createLog(2, 2, "Erro ao enviar o email -> " . 'Caught exception: ' . $e->getMessage());
            $this->response = 'Caught exception: ' . $e->getMessage();
        }
    }

}
