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

class AdresAanvullertestModuleFrontController extends ModuleFrontController
{
    /**
     *  Prepare response for front-end postcode lookup
     */
    public function init()
    {
        $this->module->test = true;
        if ($id_order = Tools::getValue('id_order')) {
            $params = array('id_order' => $id_order);
        } else {
            $sql = "SELECT id_order FROM orders ORDER BY RAND() LIMIT 1";
            $results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
            $params = $results[0];
        }
        echo "<pre>";
        var_dump($this->module->hookActionPaymentConfirmation($params));

        var_dump($this->module->filterStreetnameFromNumber('Goudsesingel', 'Goudsesingel 136-B'));
        exit;
    }
}
