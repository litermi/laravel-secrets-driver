<?php

namespace Litermi\SecretsDriver\Managers\Contracts;

interface ManagesSecrets
{
    /**
     * Public method to retrieve a secret
     * @param   string                                              Full secret key name
     * @return  mixed                                               Secret value
     * @throws  SecretsManagerException|SecretsRetrievalException
     */
    public function getSecret(string $secret);
}