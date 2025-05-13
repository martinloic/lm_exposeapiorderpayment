<?php

class Order extends OrderCore
{
    protected $webserviceParameters = array(
        'objectMethods' => array('add' => 'addWs'),
        'objectNodeName' => 'order',
        'objectsNodeName' => 'orders',
        'fields' => array(
            'id_address_delivery' => array('xlink_resource'=> 'addresses'),
            'id_address_invoice' => array('xlink_resource'=> 'addresses'),
            'id_cart' => array('xlink_resource'=> 'carts'),
            'id_currency' => array('xlink_resource'=> 'currencies'),
            'id_lang' => array('xlink_resource'=> 'languages'),
            'id_customer' => array('xlink_resource'=> 'customers'),
            'id_carrier' => array('xlink_resource'=> 'carriers'),
            'current_state' => array(
                'xlink_resource'=> 'order_states',
                'setter' => 'setWsCurrentState'
            ),
            'module' => array('required' => true),
            'invoice_number' => array(),
            'invoice_date' => array(),
            'delivery_number' => array(),
            'delivery_date' => array(),
            'valid' => array(),
            'date_add' => array(),
            'date_upd' => array(),
            'shipping_number' => array(
                'getter' => 'getWsShippingNumber',
                'setter' => 'setWsShippingNumber'
            ),
            // Custom field
            'transaction_id' => array(
                'getter' => 'getApiOrderPayment',  // Call the method to get the value
                'setter' => false,                // We don't want a setter for this field
                'required' => false,              // Whether it is required or not in the API
            ),
        ),
        'associations' => array(
            'order_rows' => array('resource' => 'order_row', 'setter' => false, 'virtual_entity' => true,
                'fields' => array(
                    'id' =>  array(),
                    'product_id' => array('required' => true),
                    'product_attribute_id' => array('required' => true),
                    'product_quantity' => array('required' => true),
                    'product_name' => array('setter' => false),
                    'product_reference' => array('setter' => false),
                    'product_ean13' => array('setter' => false),
                    'product_isbn' => array('setter' => false),
                    'product_upc' => array('setter' => false),
                    'product_price' => array('setter' => false),
                    'unit_price_tax_incl' => array('setter' => false),
                    'unit_price_tax_excl' => array('setter' => false),
                )
            ),
        ),
    );

    /**
     * Method to retrieve the custom payment method
     * Fetches the value from your custom table
     */
    public function getApiOrderPayment() {
        $order = Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'orders WHERE id_order ="'.(int)$this->id.'"');

        // $payment = Db::getInstance()->getRow('SELECT transaction_id FROM '._DB_PREFIX_.'order_payment WHERE order_reference="'.$order['reference'].'"');
        
        $payment = Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'message WHERE id_order="'.(int)$this->id.'" AND message LIKE "%Action successfully completed (00)%"');

        if (preg_match('/UUID de transaction: ([0-9a-f]+)/', $payment['message'], $matches)) {
            $uuid = $matches[1];
        }

        // if (preg_match('/\d+$/', $payment['transaction_id'], $matches)) {
        //     $payment_id = $matches[0];
        // }
        if($uuid == '') {
            return '';
        } else {
            return $uuid;
        }
    }
}
