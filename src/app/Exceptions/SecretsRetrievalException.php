<?php

namespace Litermi\SecretsDriver\Exceptions;

use \Exception;

class SecretsRetrievalException extends Exception
{
    /** @var string Full secret key name */
    protected string $secret = "";

    /** 
     * Public method to set the full secret key name
     * @return void
     */
    public function setSecret(string $secret)
    {
        $this->secret = $secret;
    }

    /** 
     * Public method to retrieve the full secret key name
     * @return string
     */
    public function getSecret()
    {
        return $this->secret;
    }
}