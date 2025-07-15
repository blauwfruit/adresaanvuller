<?php
/**
*   AdresAanvuller
*
*   Do not copy, modify or distribute this document in any form.
*
*   @author     Matthijs <matthijs@blauwfruit.nl>
*   @copyright  Copyright (c) 2013-2020 blauwfruit (http://blauwfruit.nl)
*   @license    Proprietary Software
*   @category   checkout
*
*/

class AdresAanvullerRetrieveModuleFrontController extends ModuleFrontController
{
    /**
     *  Prepare response for front-end postcode lookup
     */
    public function init()
    {
        $json_response = array();
        if (Tools::getValue('token')!==Tools::getToken(false)) {
            $json_response['message'] = false;
            $json_response['message'] = $this->module->l('Token is incorrect', 'retrieve');
        } else {
            $combiMessage = $this->module->l('The combination postalcode/home number is incorrect.', 'retrieve');
            if (Configuration::get("ADRESAANVULLER_OBLIGATORY")) {
                $combiMessage .= ' '
                .$this->module->l('You cannot continue without giving correct address information.', 'retrieve');
            } else {
                $combiMessage .= ' '
                .$this->module->l('Continue with these details if you\'re absolutely sure.', 'retrieve');
            }
            $postcode = $this->module->filterPostcode(Tools::getValue('postcode'));
            $number = $this->module->filterNumber(Tools::getValue('number'));
            $json_response = array();
            if ($postcode !== false && $number !== false) {
                $response = $this->module->request($postcode, $number);
                if (isset($response->error)) {
                    if (preg_match('/limit/', $response->error)) {
                        $json_response['success'] = false;
                        $json_response['limit_exceeded'] = true;
                        $json_response['message'] = $this->module->l('Rate limit exceeded', 'retrieve');
                    } else {
                        $json_response['success'] = false;
                        $json_response['message'] = $this->module->l('An error has occured.', 'retrieve');
                    }
                } elseif ($response == null) {
                    $json_response['success'] = false;
                    $json_response['message'] = $this->module->l('An error has occured.', 'retrieve');
                } elseif (isset($response->_embedded->addresses) && count($response->_embedded->addresses)) {
                    $json_response['success'] = true;
                    $json_response['message'] = $this->module->l('Address retrieved', 'retrieve');
                    $json_response['address'] = array(
                        'city' => $response->_embedded->addresses[0]->city->label,
                        'street' => $response->_embedded->addresses[0]->street,
                        'province' => $response->_embedded->addresses[0]->province->label,
                    );
                } else {
                    $json_response['success'] = false;
                    $json_response['message'] = $combiMessage;
                }
            } else {
                $json_response['success'] = false;
                $json_response['message'] = $this->module->l('Postalcode is not correct', 'retrieve');
            }
        }
        
        exit(json_encode($json_response));
    }
}
