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

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdresAanvuller extends Module
{
    /* $test */
    public $test = false;
    public $customAddressFormat = "firstname lastname\ncompany\nvat_number\nCountry:name\npostcode address2\naddress1 city\nphone";
    public $defaultAddressFormat = "firstname lastname\ncompany\nvat_number\naddress1 address2\npostcode city\nCountry:name\nphone\nphone_mobile\n";

    const ADDRESS_FORMAT_TYPE_JQUERY = 1;
    const ADDRESS_FORMAT_TYPE_DATABASE = 2;
    const ADDRESS_FORMAT_TYPE_NONE = 3;

    public function __construct()
    {
        $this->name = 'adresaanvuller';
        $this->tab = 'front_office_features';
        $this->version = '4.1.2';
        $this->author = 'blauwfruit';
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => '1.7');
        $this->need_instance = 1;
        $this->bootstrap = true;
        $this->displayName = $this->l('Adresaanvuller');
        $this->module_key = '27e8a536ca53dbcdbec1b35c525959c8';
        $this->description = $this->l('Postcode Autocomplete for Dutch addresses.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall? Settings will be lost... ');
        parent::__construct();
    }

    /**
     *  Set the right configurations
     *  - set jQuery selectors
     *  - set proper address_format for Netherlands (iso_code NL)
     *  - install hookHeader
     **/
    public function install()
    {
        $fields = array(
            'postcode' => 'input[name="postcode"]',
            'address2' => 'input[name="address2"]',
            'address1' => 'input[name="address1"]',
            'city' => 'input[name="city"]',
            'id_country' => 'select[name="id_country"] option:selected',
            'OBLIGATORY' => 0,
            'ADRESAANVULLER_ADDRESS_FORMAT_TYPE' => self::ADDRESS_FORMAT_TYPE_JQUERY,
        );

        foreach ($fields as $key => $value) {
            if (!Configuration::get('ADRESAANVULLER_'.$key)) {
                Configuration::updateValue('ADRESAANVULLER_'.$key, $value);
            }
        }

        if (!parent::install() ||
            !$this->registerHook('header') ||
            !$this->registerHook('actionPaymentConfirmation')
        ) {
            return false;
        } else {
            return true;
        }
    }

    public function uninstall()
    {
        if (!parent::uninstall() ||
            !$this->unregisterHook('header') ||
            !$this->unregisterHook('actionPaymentConfirmation')) {
            return false;
        } else {
            return true;
        }
    }

    /**
     *  Most logic for handling the configurations in the backoffice
     */
    public function getContent()
    {
        $confirm = array();
        $warning = array();
        $html = '';
        if (Tools::isSubmit('submit'.$this->name)) {
            foreach (array('netherlands','postcode','address2',
                'address1','city','id_country','PostcodeAPIKey',
                'OBLIGATORY', 'CHECKLATER',
                'WARNING_MAILS','WARNING_EMAILADDRESSES') as $field) {
                if (Configuration::updateValue("ADRESAANVULLER_".$field, Tools::getValue("ADRESAANVULLER_".$field))) {
                    $confirm[] = $field;
                } else {
                    $warning[] = $field;
                }
            }

            $addressChangeResult = true;
            if ((int) Configuration::get('ADRESAANVULLER_ADDRESS_FORMAT_TYPE') !== (int) Tools::getValue('ADRESAANVULLER_ADDRESS_FORMAT_TYPE')) {
                if ((int) Tools::getValue('ADRESAANVULLER_ADDRESS_FORMAT_TYPE') == self::ADDRESS_FORMAT_TYPE_DATABASE) {
                    $addressChangeResult = $this->changeAddressFormat();
                } elseif ((int) Tools::getValue('ADRESAANVULLER_ADDRESS_FORMAT_TYPE') == self::ADDRESS_FORMAT_TYPE_JQUERY) {
                    $addressChangeResult = $this->changeAddressFormat(true);
                }
            }

            if ($addressChangeResult) {
                Configuration::updateValue(
                    'ADRESAANVULLER_ADDRESS_FORMAT_TYPE',
                    Tools::getValue('ADRESAANVULLER_ADDRESS_FORMAT_TYPE')
                );
                $confirm[] = 'ADRESAANVULLER_ADDRESS_FORMAT_TYPE';
            } else {
                $warning[] = 'ADRESAANVULLER_ADDRESS_FORMAT_TYPE';
            }

            if ($confirm) {
                $html .= $this->displayConfirmation("Configuration " . implode(', ', $confirm) . " are saved");
            }
            if ($warning) {
                $html .= $this->displayWarning("Configuration " . implode(', ', $warning) . " are not saved");
            }
        }

        $this->context->smarty->assign(array(
            'base_uri' => __PS_BASE_URI__,
            'shop_name' => $this->context->shop->name,
            'shop_domain' => $this->context->shop->domain
        ));
        $html .= $this->renderForm();
        $html .= $this->display(__FILE__, 'views/templates/admin/configuration.tpl');
        return $html;
    }

    public function renderForm()
    {
        $apikey_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('API Key'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type'     => 'text',
                        'placeholder' => 'API Key',
                        'label'    => $this->l('API Key'),
                        'name'     => 'ADRESAANVULLER_PostcodeAPIKey',
                        'size'     => 50,
                        'required' => true
                   ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right'),
           ),
        );

        $x = 0;
        $real_options[$x]['id_option'] = '';
        $real_options[$x]['name'] = $this->l('Country');
        
        foreach (Country::getCountries($this->context->language->id) as $country) {
            $x++;
            $real_options[$x]['id_option'] = $country['id_country'];
            $real_options[$x]['name'] = $country['name'];
        }



        $address_fields = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Address fields'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type'     => 'select',
                        'placeholder' => sprintf($this->l('Netherlands')),
                        'label'    => sprintf($this->l('Netherlands')),
                        'name'     => 'ADRESAANVULLER_netherlands',
                        'value'     => Configuration::get('ADRESAANVULLER_netherlands'),
                        'size'     => 1,
                        'required' => true,
                        'options' => array(
                            'query' => $real_options,
                            'id' => 'id_option',
                            'name' => 'name'
                        )
                    ),
                    array(
                      'type'     => 'text',
                      'placeholder' => sprintf($this->l('Selector for %s'), '`postcode`'),
                      'label'    => sprintf($this->l('Selector for %s'), '`postcode`'),
                      'name'     => 'ADRESAANVULLER_postcode',
                      'value'     => Configuration::get('ADRESAANVULLER_postcode'),
                      'size'     => 50,
                      'required' => true
                   ),
                    array(
                      'type'     => 'text',
                      'placeholder' => sprintf($this->l('Selector for %s'), '`address2`'),
                      'label'    => sprintf($this->l('Selector for %s'), '`address2`'),
                      'name'     => 'ADRESAANVULLER_address2',
                      'value'     => Configuration::get('ADRESAANVULLER_address2'),
                      'size'     => 50,
                      'required' => true
                   ),
                    array(
                      'type'     => 'text',
                      'placeholder' => sprintf($this->l('Selector for %s'), '`address1`'),
                      'label'    => sprintf($this->l('Selector for %s'), '`address1`'),
                      'name'     => 'ADRESAANVULLER_address1',
                      'value'     => Configuration::get('ADRESAANVULLER_address1'),
                      'size'     => 50,
                      'required' => true
                   ),
                    array(
                      'type'     => 'text',
                      'placeholder' => sprintf($this->l('Selector for %s'), '`city`'),
                      'label'    => sprintf($this->l('Selector for %s'), '`city`'),
                      'name'     => 'ADRESAANVULLER_city',
                      'value'     => Configuration::get('ADRESAANVULLER_city'),
                      'size'     => 50,
                      'required' => true
                   ),
                    array(
                      'type'     => 'text',
                      'placeholder' => sprintf($this->l('Selector for %s'), '`id_country`'),
                      'label'    => sprintf($this->l('Selector for %s'), '`id_country`'),
                      'name'     => 'ADRESAANVULLER_id_country',
                      'value'     => Configuration::get('ADRESAANVULLER_id_country'),
                      'size'     => 50,
                      'required' => true
                   ),
               ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'),
           ),
        );


        $emails = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Check postcode and house number later'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type'=> 'switch',
                        'label'=> $this->l('Checks if delivery address is correct after the order is places'),
                        'name'=> 'ADRESAANVULLER_CHECKLATER',
                        'desc' => $this->l('Beware that this option will double your API calls, a paid subscription is likely needed'),
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                            )
                            ),
                    ),
                    array(
                        'type'=> 'switch',
                        'label'=> $this->l('Notifications for errors'),
                        'name'=> 'ADRESAANVULLER_WARNING_MAILS',
                        'desc' => $this->l('Alert email when no address is found'),
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                            )
                            ),
                    ),
                    array(
                        'type'     => 'textarea',
                        'placeholder' => $this->l('Emailaddresses for errors'),
                        'label'    => $this->l('Emailaddresses for errors'),
                        'name'     => 'ADRESAANVULLER_WARNING_EMAILADDRESSES',
                        'size'     => 50,
                        'required' => true
                    ),
                ),
                'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'),
           ),
        );

        $restrictions = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Restrictions'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type'=> 'switch',
                        'label'=> $this->l('Make autocomplete obligatory'),
                        'name'=> 'ADRESAANVULLER_OBLIGATORY',
                        'desc' => $this->l('If checked Yes, the customer is only able to complete the form with help of the autocompleter'),
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                            )
                            ),
                    ),
                ),
                'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'),
           ),
        );

        $addressFormat = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Address format'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type'     => 'select',
                        'placeholder' => $this->l('Netherlands'),
                        'label'=> $this->l('Address format type'),
                        'name'=> 'ADRESAANVULLER_ADDRESS_FORMAT_TYPE',
                        'desc' => $this->l('If Database is selected, the AddressFormat will be applied in the database 
                            (and also in the invoice), if jQuery is selected, the format will be changed in the front-end with 
                            jQuery'),
                        'value'     => Configuration::get('ADRESAANVULLER_netherlands'),
                        'size'     => 1,
                        'required' => true,
                        'options' => array(
                            'query' => array(
                                array('id_option' => self::ADDRESS_FORMAT_TYPE_JQUERY, 'name' => $this->l('jQuery')),
                                array('id_option' => self::ADDRESS_FORMAT_TYPE_DATABASE, 'name' => $this->l('Database')),
                                array('id_option' => self::ADDRESS_FORMAT_TYPE_NONE, 'name' => $this->l('None - I manage this myself')),
                            ),
                            'id' => 'id_option',
                            'name' => 'name'
                        )
                    ),
                ),
                'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'),
           ),
        );

        $helper = new HelperForm();
        $helper->submit_action = 'submit'.$this->name;
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->title = $this->displayName;
        /* API key */
        $helper->fields_value['ADRESAANVULLER_PostcodeAPIKey'] = Configuration::get('ADRESAANVULLER_PostcodeAPIKey');
        /* Obligatory */
        $helper->fields_value['ADRESAANVULLER_OBLIGATORY'] = Configuration::get('ADRESAANVULLER_OBLIGATORY');
        /* Address Fields */
        $helper->fields_value['ADRESAANVULLER_netherlands'] = Configuration::get('ADRESAANVULLER_netherlands');
        $helper->fields_value['ADRESAANVULLER_postcode'] = Configuration::get('ADRESAANVULLER_postcode');
        $helper->fields_value['ADRESAANVULLER_address2'] = Configuration::get('ADRESAANVULLER_address2');
        $helper->fields_value['ADRESAANVULLER_address1'] = Configuration::get('ADRESAANVULLER_address1');
        $helper->fields_value['ADRESAANVULLER_city'] = Configuration::get('ADRESAANVULLER_city');
        $helper->fields_value['ADRESAANVULLER_id_country'] = Configuration::get('ADRESAANVULLER_id_country');
        $helper->fields_value['ADRESAANVULLER_CHECKLATER'] = Configuration::get('ADRESAANVULLER_CHECKLATER');
        $helper->fields_value['ADRESAANVULLER_WARNING_MAILS'] = Configuration::get('ADRESAANVULLER_WARNING_MAILS');
        $helper->fields_value['ADRESAANVULLER_WARNING_EMAILADDRESSES'] = Configuration::get('ADRESAANVULLER_WARNING_EMAILADDRESSES');
        $helper->fields_value['ADRESAANVULLER_ADDRESS_FORMAT_TYPE'] = Configuration::get('ADRESAANVULLER_ADDRESS_FORMAT_TYPE');

        return $helper->generateForm(array($apikey_form, $address_fields, $emails, $restrictions, $addressFormat));
    }

    /**
     *  Export all variables to JavaScript in the front-end
     */
    public function hookHeader($params = false)
    {
        $this->context->controller->addJS($this->_path . 'views/js/adresaanvuller.js');
        Media::addJsDef(array(
            'retrieve' => $this->context->link->getModuleLink(
                'adresaanvuller',
                'retrieve',
                array('token' => Tools::getToken(false)),
                true
            ),
            'netherlands' => (int)Configuration::get("ADRESAANVULLER_netherlands"),
            'postcode_selector' => Configuration::get("ADRESAANVULLER_postcode"),
            'address2_selector' => Configuration::get("ADRESAANVULLER_address2"),
            'address1_selector' => Configuration::get("ADRESAANVULLER_address1"),
            'city_selector' => Configuration::get("ADRESAANVULLER_city"),
            'id_country_selector' => Configuration::get("ADRESAANVULLER_id_country"),
            'obligatory' => Configuration::get("ADRESAANVULLER_OBLIGATORY"),
            'address_format_type' => (int)Configuration::get("ADRESAANVULLER_ADDRESS_FORMAT_TYPE"),
        ));
    }

    /**
     * Check the delivery address for correctness, if not a mail is sent to notify the merchant.
     */
    public function hookActionPaymentConfirmation($params)
    {
        $start = time();
        $errors = array();
        $address_old = null;

        if (!Configuration::get('ADRESAANVULLER_CHECKLATER')) {
            return;
        }

        $order = new Order((int)$params['id_order']);
        $address = new Address((int)$order->id_address_delivery);

        if (!$this->isNetherlands($address)) {
            return;
        }

        // Process and clean the postcode and street info
        $postcode = $this->filterPostcode($address->postcode);
        $streetName = $this->filterStreetnameFromNumber($address->address1, $address->address2);
        $address->address2 = $streetName;
        $address->save();

        $number = $this->filterNumber($streetName ? $streetName : $address->address2);

        // Check if postcode and house number are valid
        if ($postcode !== false && $number !== false) {
            // Log old address
            $address_old = new Address($address->id);

            // Process API response and compare the addresses
            $this->processApiResponse($postcode, $number, $address, $address_old, $order, $errors);
        } else {
            // Log error for invalid postcode or number
            $errors[] = $this->l('Postcode and house number are incorrect');
        }

        $this->validateAddresses($address, $errors);

        if (Configuration::get('ADRESAANVULLER_WARNING_MAILS') && count($errors)) {
            Logger::addLog(Tools::jsonEncode($errors), 1);
            $this->sendWarning($order, $address, $address_old, $errors);
        }

        $executionTime = time() - $start;
        Logger::addLog("[{$this->name}] Execution time " . $executionTime, 1);

        if ($executionTime > Configuration::get('PS_EXECUTION_TIME_THRESHOLD')) {
            Logger::addLog("[{$this->name}] Long execution time " . $executionTime, 2);
        }
    }

    /**
     * Check if the address is in the Netherlands.
     */
    protected function isNetherlands(Address $address)
    {
        return (int)$address->id_country === (int)Configuration::get("ADRESAANVULLER_netherlands");
    }

    protected function processApiResponse($postcode, $number, Address $address, Address $address_old, Order $order, &$errors)
    {
        $response = $this->request($postcode, $number);

        // If API provides address, compare and update if necessary
        if (isset($response->_embedded->addresses) && count($response->_embedded->addresses)) {
            $apiAddress = $response->_embedded->addresses[0];

            if ($apiAddress->city->label !== $address->city || $apiAddress->street !== $address->address1) {
                $this->updateAddressWithApiData($address, $apiAddress, $address_old, $order, $errors);
            }
        } else {
            $errors[] = $this->l('Address not found');
        }
    }

    protected function updateAddressWithApiData(Address $address, $apiAddress, Address $address_old, Order $order, &$errors)
    {
        // Update address details with API data
        $address->city = $apiAddress->city->label;
        $address->address1 = $apiAddress->street;
        $address->update();

        Logger::addLog(sprintf(
            "[%s][#%s] address changed from `%s` to `%s`",
            $this->name,
            $order->reference,
            $this->getAddressString($address_old, false),
            $this->getAddressString($address, false)
        ));
    }

    protected function validateAddresses(Address $address, &$errors)
    {
        if (trim($address->address1) === trim($address->address2)) {
            $errors[] = $this->l('Address 1 and Address 2 are the same');
        }
    }



    public function request($postcode, $number)
    {
        $APIkey = Configuration::get('ADRESAANVULLER_PostcodeAPIKey');
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.postcodeapi.nu/v2/addresses/?postcode=$postcode&number=$number",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "accept: application/hal+json",
                "x-api-key: $APIkey"
            ),
        ));
        return Tools::jsonDecode(curl_exec($curl));
    }

    public function filterNumber($number)
    {
        preg_match('/^\d+/', trim($number), $numbers);
        return isset($numbers[0]) ? $numbers[0] : false;
    }

    public function filterPostcode($postcode)
    {
        $postcode = str_replace(' ', '', $postcode);
        return preg_match('/^(\d{4,4}[a-zA-Z]{2,2})$/', $postcode) ? $postcode : false;
    }

    /**
     *  When Google Chrome completes forms, it's not always correct. Sometimes a house nummer contains a
     *  full streetname + house numnber. This function will check if the streetname is in the house number.
     *
     *  @param  string  $address1 streetname
     *  @param  string  $address2 house number
     *
     *  @return string  clean house number
     */
    public function filterStreetnameFromNumber($address1, $address2)
    {
        preg_match_all('/^([a-zA-Z\.\- ]{5,})/', $address1, $matches);
        if (!isset($matches[0][0])) {
            return $address2;
        }
        $filtered = trim(str_replace($matches[0][0], '', $address2));
        return $filtered ? $filtered : $address2;
    }

    /**
     *  Send mail for alert of notification
     *
     *  @param  string  either warning|notification
     *  @param  object  Order       $order
     *  @param  object  Address     $address
     *  @param  object  Address     $address_old
     *  @param  object  Customer    $customer
     */
    public function sendWarning(
        Order $order,
        Address $address,
        Address $address_old = null,
        $errors = array()
    ) {
        $customer = new Customer((int) $order->id_customer);

        $reference = $order->reference ? $order->reference : $order->id_cart;
        $tpl_vars = array();
        $tpl_vars['{firstname}'] = sprintf($this->l('%s-team'), (new Shop($order->id_shop))->name);
        $tpl_vars['{id_order}'] = $order->id;
        $tpl_vars['{reference}'] = $reference;
        $tpl_vars['{address}'] = $this->getAddressString($address);
        $tpl_vars['{email}'] = $customer->email;
        $tpl_vars['{phone}'] = $address->phone;
        if ($address_old) {
            $tpl_vars['{address_old}'] = $this->getAddressString($address_old);
        }

        $tpl_vars['{errors}'] = implode(', ', $errors);

        return Mail::Send(
            $this->context->language->id,
            'address_not_found',
            sprintf($this->l('Wrong address: #%d - %s'), $order->id, $reference),
            $tpl_vars,
            $this->getEmailAddresses(),
            null,
            null,
            null,
            null,
            null,
            dirname(__FILE__).'/mails/',
            false,
            $order->id_shop
        );
    }

    public function getEmailAddresses()
    {
        $emailaddresses = explode("\n", Configuration::get('ADRESAANVULLER_WARNING_EMAILADDRESSES'));
        $emailaddresses = array_map('trim', $emailaddresses);
        return $emailaddresses;
    }

    /**
     *  Return address as a string
     *  @param  object  Address  $address
     *  @param  string  formatted by line feeds: \n
     */
    public function getAddressString(Address $address, $formatted = true)
    {
        $format = $formatted ? "%s %s\n%s %s" : "%s %s, %s %s";
        return sprintf($format, $address->address1, $address->address2, $address->postcode, $address->city);
    }

    /**
     *  Change AddressFormat in the database
     *
     *  @param  bool    $default    change to default format or, false, change to custom format
     *  @return bool
     */
    public function changeAddressFormat($default = false)
    {
        $country = new Country(Country::getByIso('NL'));
        $address_format = new AddressFormat($country->id);
        $address_format->format = $default ? $this->defaultAddressFormat : $this->customAddressFormat;
        return $address_format->update();
    }
}
