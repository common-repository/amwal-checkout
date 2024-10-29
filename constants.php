<?php
const AMWALWC_ENV                     = 'prod';
if(AMWALWC_ENV === 'localhost'){
    define("AMWALWC_SERVER_URI", 'http://localhost:8000');
    define("AMWALWC_MERCHANT_DASHBOARD", 'http://localhost:3001');
    define("AMWALWC_PAY_URI", 'http://localhost:3000');
}
elseif (AMWALWC_ENV === 'docker'){
    define("AMWALWC_SERVER_URI", 'http://host.docker.internal:8000');
    define("AMWALWC_MERCHANT_DASHBOARD", 'http://host.docker.internal:3001');
    define("AMWALWC_PAY_URI", 'http://host.docker.internal:3000');
}
elseif (AMWALWC_ENV === 'prod'){
    define("AMWALWC_SERVER_URI", 'https://backend.sa.amwal.tech');
    define("AMWALWC_MERCHANT_DASHBOARD", 'https://merchant.sa.amwal.tech');
	define("AMWALWC_PAY_URI", 'https://pay.sa.amwal.tech');
}
else{
    define("AMWALWC_SERVER_URI", 'https://'.AMWALWC_ENV.'-backend.sa.amwal.tech');
    define("AMWALWC_MERCHANT_DASHBOARD", 'https://'.AMWALWC_ENV.'-merchant.sa.amwal.tech');
	define("AMWALWC_PAY_URI", 'https://'.AMWALWC_ENV.'-pay.sa.amwal.tech');
}

const AMWALWC_TRANSACTION_DETAILS       = AMWALWC_SERVER_URI . '/transactions/';
const AMWALWC_REFUND_HOST               = AMWALWC_TRANSACTION_DETAILS . 'refund/';
const AMWALWC_INSTALLMENT_URL		    = AMWALWC_PAY_URI . '/installment-setup';