<?php

namespace Litermi\SecretsDriver\Managers;

use \Exception;
use Litermi\SecretsDriver\Exceptions\SecretsRetrievalException;
use Litermi\SecretsDriver\Managers\Interfaces\ManagesSecrets;

class AwsSecretsManager implements ManagesSecrets
{
    /** @var Aws\SecretsManager\SecretsManagerClient */
    protected $secretsManager = null;

    /**
     * Protected method to get an AWS secret manager instance, if not already created
     * 
     * @return Aws\SecretsManager\SecretsManagerClient
     * @throws SecretsManagerException
     */
    protected function getSecretsManager()
    {
        if (is_null($this->secretsManager)) {
            try {
                $this->secretsManager = app("aws")
                                    ->createClient("SecretsManager");                
            } catch (Exception $e) {
                throw new SecretsManagerException(
                    $e->getMessage()
                );
            }    
        }
        
        return $this->secretsManager;
    }

    /**
     * Public method to retrieve a secret
     * @param   string                                              Full secret key name
     * @return  mixed                                               Secret value
     * @throws  SecretsManagerException|SecretsRetrievalException
     */
    public function getSecret(string $secret)
    {
        try {
            /** @var string JSON with secret configuration */
            $rawSecretData = $this->getSecretsManager()
                                    ->getSecretValue([
                                        'SecretId' => $secret,
                                    ])["SecretString"];

            /** @var mixed[] Parsed database configuration */
            $secretData = json_decode($rawSecretData, true);
        } catch (AwsException $ae) {
            /** @var SecretsRetrievalException Exception to throw */
            $secretEx = new SecretsRetrievalException(
                $e->getMessage()
            );

            /* Specify the secret */
            $secretEx->setSecret($secret);

            throw $secretEx;
        }

        return $secretData;
    }
}