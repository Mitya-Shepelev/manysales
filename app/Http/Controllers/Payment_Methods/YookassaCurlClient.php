<?php

namespace App\Http\Controllers\Payment_Methods;

use YooKassa\Client\CurlClient;

class YookassaCurlClient extends CurlClient
{
    public function setAdvancedCurlOptions(): void
    {
        $this->setCurlOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->setCurlOption(CURLOPT_SSL_VERIFYHOST, false);
    }
}
