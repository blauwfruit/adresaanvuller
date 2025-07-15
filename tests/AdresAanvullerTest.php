<?php
/**
*   AdresAanvullerTest
*
*   Do not copy, modify or distribute this document in any form.
*
*   @author     Matthijs <matthijs@blauwfruit.nl>
*   @copyright  Copyright (c) 2013-2020 blauwfruit (https://blauwfruit.nl)
*   @license    Proprietary Software
*
*/

use PHPUnit\Framework\TestCase;

final class AdresAanvullerTest extends TestCase
{
    public $adresAanvuller;

    public function __construct()
    {
        parent::__construct();
        $this->adresAanvuller = Module::getInstanceByName('adresaanvuller');
    }

    public function testFilterNumberReturnsNull()
    {
        $this->assertEquals($this->adresAanvuller->filterNumber('DD'), null);
    }

    public function testFilterNumberReturnsNumber()
    {
        $this->assertEquals($this->adresAanvuller->filterNumber('3D'), '3');
    }

    public function testIfStreetnameFromNumerDoesNotReturnEmptyValue()
    {
        $this->assertNotEmpty($this->adresAanvuller->filterStreetnameFromNumber('Coolsingel', 'Coolsingel'));
    }

    public function testIfStreetnameFromNumerDoesNotReturnEmptyString()
    {
        $this->assertEquals(
            'Coolsingel',
            $this->adresAanvuller->filterStreetnameFromNumber('Coolsingel', 'Coolsingel')
        );
    }

    /**
     * @todo other functions related to streetname filtering bundle with this function below
     */
    public function testIfStreetnameFromNumerReturnsOnlyHouseNumber()
    {
        $this->assertEquals(
            '136-B',
            $this->adresAanvuller->filterStreetnameFromNumber('Goudsesingel', 'Goudsesingel 136-B')
        );

        $this->assertEquals(
            '3',
            $this->adresAanvuller->filterStreetnameFromNumber('Goudsesingel-Zuid', 'Goudsesingel-Zuid 3')
        );

        $this->assertEquals(
            'D-3',
            $this->adresAanvuller->filterStreetnameFromNumber('Goudsesingel-Zuid', 'Goudsesingel-Zuid D-3')
        );
    }

    public function testFilterNumberReturnsFalseWhenEmpty()
    {
        $this->assertFalse($this->adresAanvuller->filterNumber('Schijndel'));
    }

    /*
        public function testEmailFunctionality()
        {
            $this->assertInternalType(
                'int',
                $this->adresAanvuller->sendWarning(
                    new Order(97),
                    new Address(672),
                    new Address(671),
                    array($this->adresAanvuller->l('Postcode and house number are incorrect')))
            );
        }
    */
    // public function testSameAddress()
    // {
    //     $row = Db::getInstance()->getRow('
    //         SELECT o.id_order, a.address1, a.address2
    //         FROM '._DB_PREFIX_.'orders o
    //         JOIN '._DB_PREFIX_.'address a ON o.id_address_delivery=a.id_address
    //         WHERE a.address1=a.address2
    //         ORDER BY RAND()');
    //     $this->assertEquals(trim($row['address1']), trim($row['address2']));
    //     $this->assertNull($this->adresAanvuller->hookActionPaymentConfirmation(array('id_order' => $row['id_order'])));
    // }
}
