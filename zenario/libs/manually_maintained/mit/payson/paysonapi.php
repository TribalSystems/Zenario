<?php
namespace PaysonEmbedded {
    require_once "paysonapiexception.php";
    require_once "paysonapierror.php";
    require_once "paydata.php";
    require_once "paysonmerchant.php";
    require_once "customer.php";
    require_once "gui.php";
    require_once "paysoncheckout.php";
    require_once "account.php";
    
}
namespace PaysonEmbedded {
    class PaysonApi {
        private $merchantId;
        private $apiKey;
        private $protocol = "https://%s";
        const PAYSON_HOST = "api.payson.se/2.0/";
        const ACTION_CHECKOUTS = "Checkouts/";
        const ACTION_ACCOUNTS = "Accounts/";
        private $paysonMerchant = null;
        private $payData = null;
        private $customer = null;
        private $allOrderData = array();
        private $gui = null;
        private $useTestEnvironment = null;
        private $checkoutId = null;
        private $paysonResponse = null;
        public $paysonResponseErrors = array();
        public function __construct($merchantId, $apiKey, $useTestEnvironment = false) {
            $this->useTestEnvironment = $useTestEnvironment;
            $this->merchantId = $merchantId;
            $this->apiKey = $apiKey;
            
            if (!function_exists('curl_exec')) {
                throw new PaysonApiException('Curl not installed. Is required for PaysonApi.');
            }
        }
        public function getMerchantId() {
            return $this->merchantId;
        }
        public function getApiKey() {
            return $this->apiKey;
        }
        public function CreateCheckout(Checkout $checkout) {
            $result = $this->doCurlRequest('POST', $this->getUrl(self::ACTION_CHECKOUTS), $checkout->toArray());
            $checkoutId = $this->extractCheckoutId($result);
            if(!$checkoutId) {
                throw new PaysonApiException("Checkout Id not received of unclear reason");
            }
            return $checkoutId;
        }
        
        public function CreateGetCheckout(Checkout $checkout) {
            $result = $this->doCurlRequest('POST', $this->getUrl(self::ACTION_CHECKOUTS), $checkout->toArray(), true);
            $newCheckout = Checkout::create(json_decode($result));
            if(!$newCheckout->id) {
                throw new PaysonApiException("Checkout ID not received of unclear reason");
            }
            return $newCheckout;
        }
        
        public function UpdateCheckout($checkout) {
            if(!$checkout->id) {
                throw new PaysonApiException("Checkout object which should be updated must have id property set");
            }
            $result = $this->doCurlRequest('PUT', $this->getUrl(self::ACTION_CHECKOUTS).$checkout->id, $checkout->toArray());
            return $checkout;
        }
        
        public function GetCheckout($checkoutId) {
            $result = $this->doCurlRequest('GET', $this->getUrl(self::ACTION_CHECKOUTS).$checkoutId, null);
            return Checkout::create(json_decode($result));
        }
        
        public function ShipCheckout(Checkout $checkout) {
            $checkout->status = 'shipped';
            return $this->UpdateCheckout($checkout);
        }
        
        public function CancelCheckout(Checkout $checkout) {
            $checkout->status = 'canceled';
            return $this->UpdateCheckout($checkout);
        }
        
        public function Validate() {
            $result = $this->doCurlRequest('GET', $this->getUrl(self::ACTION_ACCOUNTS), null);
            return Account::create(json_decode($result));
        }
        private function doCurlRequest($method, $url, $postfields, $returnBody = false) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->authorizationHeader());
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields?json_encode($postfields):null);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, true);
            $result = curl_exec($ch);
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $body = substr($result, $header_size);
            $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            /* This class of status codes indicates the action requested by the client was received, understood, accepted and processed successfully
             * 200 OK
             * 201 Created
             * 202 Accepted
             * 203 Non-Authoritative Information (since HTTP/1.1)
             */
            if ($response_code == 200) {
                return $body;
            } elseif ($response_code == 201) {
                if ($returnBody == true) {
                    return $body;
                }
                return $result;
            } elseif ($result == false) {
                throw new PaysonApiException('Curl error: '.curl_error($ch));
            } else {
                $errors = array();
                
                $data = json_decode($body,true);
                $errors[] = new PaysonApiError('HTTP status code: ' . $response_code.', '.$data['message'], null);
                
                if(isset($data['errors']) && count($data['errors'])) {
                    $errors = array_merge($errors, $this->parseErrors($data['errors'], $response_code));    
                }
                
                throw new PaysonApiException("Api errors", $errors);
            }
            
        }
        
        private function authorizationHeader() {
            $header = array();
            $header[] = 'Content-Type: application/json';
            $header[] = 'Authorization: Basic ' . base64_encode($this->merchantId . ':' . $this->apiKey);
            return $header;
        }
        private function extractCheckoutId($result) {
            $checkoutId = null;
            if (preg_match('#Location: (.*)#', $result, $res)) {
                $checkoutId = trim($res[1]);
            }
            $checkoutId = explode('/', $checkoutId);
            $checkoutId = $checkoutId[count($checkoutId) - 1];
            return $checkoutId;
        }
        private function parseErrors($responseErrors, $response_code) {
            $errors = array();
            foreach ($responseErrors as $error) {
                $errors[] = new PaysonApiError($error['message'], (isset($error['property'])?$error['property']:null));
            }
            return $errors;
        }
        public function setStatus($status) {
            $this->allOrderData['status'] = $status;
        }
        
        private function getUrl($action) {
            return (sprintf($this->protocol, ($this->useTestEnvironment ? 'test-' : '')) . self::PAYSON_HOST.$action);
        }
    }
}
