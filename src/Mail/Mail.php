<?php

namespace App\Facades\Mail;

use App\Facades\Config\Config;
use App\Facades\Http\View;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

class Mail
{
	private Transport\Smtp\EsmtpTransport $transport;
	private Mailer $mailer;
	
	public function __construct()
	{
		$this->transport = Transport::fromDsn(Config::get('app.mail'));
		$this->mailer    = new Mailer($this->transport);
	}
	
	public function send(Email $email): void
	{
		$this->mailer->send($email);
	}
	
	public function getTemplate(string $template, array $data): ?string
	{
		return View::mail($template, $data);
	}
}
