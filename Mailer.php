<?php

namespace aface\mailgun;

use Mailgun\{
    Mailgun,
    HttpClientConfigurator
};
use yii\base\InvalidConfigException;
use yii\mail\BaseMailer;

/**
 * Mailer implements a mailer based on Mailgun.
 * To use Mailer, you should configure it in the application configuration like the following,
 * To send an email, you may use the following code:
 *
 * ~~~
 * Yii::$app->mailer->compose('contact/html', ['contactForm' => $form])
 *     ->setFrom('from@domain.com')
 *     ->setTo($form->email)
 *     ->setSubject($form->subject)
 *     ->send();
 * ~~~
 */
class Mailer extends BaseMailer
{
    /**
     * @var string message default class name.
     */
    public $messageClass = 'aface\mailgun\Message';
    /**
     * @var string Mailgun API credentials.
     * @see https://app.mailgun.com/app/account/security
     */
    public $key;
    /**
     * @var string Mailgun domain.
     */
    public $domain;
    /**
     * @var string Mailgun endpoint.
     */
    public $endpoint = 'api.mailgun.net';
    /**
     * @var Mailgun Mailgun instance.
     */
    private $_mailgun;

    /**
     * @return Mailgun
     * @throws InvalidConfigException
     */
    public function getMailgun()
    {
        if (! ($this->_mailgun instanceof Mailgun)) {
            if (! $this->key) {
                throw new InvalidConfigException('Mailer::key must be set.');
            }
            if (! $this->domain) {
                throw new InvalidConfigException('Mailer::domain must be set.');
            }

            $configurator = (new HttpClientConfigurator())
                ->setApiKey($this->key);
            $httpClient = $configurator->createConfiguredClient();
            $this->_mailgun = new Mailgun($configurator->getApiKey(), $httpClient, $this->endpoint);
        }

        return $this->_mailgun;
    }

    /**
     * @param \yii\mail\MessageInterface $message
     * @return bool
     * @throws InvalidConfigException
     * @throws \Mailgun\Messages\Exceptions\MissingRequiredMIMEParameters
     */
    protected function sendMessage($message)
    {
        $result = $this->getMailgun()->sendMessage(
            $this->domain,
            $message->getMessageBuilder()->getMessage(),
            $message->getMessageBuilder()->getFiles()
        );

        if ($result->http_response_code === 200) {
            return true;
        }

        return false;
    }

    /**
     * @param $email
     * @return bool
     */
    public function emailValidate($email)
    {
        $mailgun = Mailgun::create($this->key);
        $mailgun->setApiVersion('v4');
        $response = $mailgun->get('address/validate', [
            'address' => $email
        ]);

        if ($response->http_response_code === 200) {
            $responseBody = $response->http_response_body;
            return property_exists($responseBody, 'reason') ? empty($responseBody->reason) : false;
        }

        return false;
    }
}
