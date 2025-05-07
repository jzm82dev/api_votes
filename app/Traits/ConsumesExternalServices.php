<?php

namespace App\Traits;

use GuzzleHttp\Client;

trait ConsumesExternalServices{

    public function makeRequest($method, $requestUrl, $queryParamas = [], $formParams = [], $headers = [], $isJasonRquest = false){

        $client = new Client([
            'base_uri' => $this->baseUri
        ]);

        if(method_exists($this, 'resolveAuthorization')){
            $this->resolveAuthorization($queryParamas, $formParams, $headers);
        }

        $response = $client->request($method, $requestUrl, [
            $isJasonRquest ? 'json' : 'form_params' => $formParams,
            'headers' => $headers,
            'query' => $queryParamas
        ]);

        $response = $response->getBody()->getContents();

        if(method_exists($this, 'decodeResponse')){
            $response = $this->decodeResponse($response);
        }

        return $response;

    }


}
