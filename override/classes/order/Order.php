<?php

class Order extends OrderCore
{
    /**
     * Retrieves the rows of order details for the current order.
     *
     * Executes a SQL query to fetch detailed information about each product in the order,
     * including product IDs, prices (with and without tax), quantities, references, and other identifiers.
     * Also calculates the product price with tax (`product_price_wt`) for each row.
     *
     * @return array Returns an array of associative arrays, each representing a row from the order details.
     */
    public function getWsOrderRows() {
        $query = '
            SELECT
            `id_order_detail` as `id`,
            `product_id`,
            `product_price`,
            `id_order`,
            `product_attribute_id`,
            `product_quantity`,
            `product_name`,
            `product_reference`,
            `product_ean13`,
            `product_isbn`,
            `product_upc`,
            `unit_price_tax_incl`,
            `unit_price_tax_excl`,
            (`product_price` * (
                CASE
                    WHEN `unit_price_tax_excl` > 0
                    THEN ROUND(((`unit_price_tax_incl` - `unit_price_tax_excl`) / `unit_price_tax_excl`) * 100, 2)
                    ELSE 0
                END / 100
            )+`product_price`) as `product_price_wt`
            FROM `'._DB_PREFIX_.'order_detail`
            WHERE id_order = '.(int)$this->id;
        $result = Db::getInstance()->executeS($query);
        return $result;
    }


    /**
     * Webservice parameters configuration for the Order class.
     *
     * This array defines how the Order object is exposed via the Prestashop Webservice API.
     * It specifies object methods, node names, fields, and associations for serialization and deserialization.
     *
     * Structure:
     * - objectMethods: Maps webservice actions to class methods (e.g., 'add' => 'addWs').
     * - objectNodeName: The XML node name for a single order object.
     * - objectsNodeName: The XML node name for a collection of order objects.
     * - fields: Defines the fields exposed in the API, their requirements, resource links, and custom getter/setter methods.
     *   - Each field can specify:
     *     - xlink_resource: Related resource for linking (e.g., 'addresses', 'carts').
     *     - required: Whether the field is required in the API.
     *     - getter/setter: Custom methods for getting or setting the field value.
     * - associations: Defines related entities (e.g., order_rows) and their fields, including requirements and custom setters.
     *
     * Notable customizations:
     * - 'transaction_id': Custom field exposed via the API, uses 'getApiOrderPayment' as getter, no setter, not required.
     * - 'order_rows': Virtual entity representing order line items, with detailed field configuration.
     */
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
            // Ajout du champ personnalisé
            'transaction_id' => array(
                'getter' => 'getApiOrderPayment',  // Appel de la méthode pour obtenir la valeur
                'setter' => false,                     // On ne veut pas de setter pour ce champ
                'required' => false,                   // Si c'est requis ou non dans l'API
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
                    'product_price_wt' => array('setter' => false),
                    'unit_price_tax_incl' => array('setter' => false),
                    'unit_price_tax_excl' => array('setter' => false),
                )
            ),
        ),
    );

    /**
     * Retrieves the UUID of the transaction associated with the current order.
     *
     * This method queries the database for a message related to the order that contains
     * a successful action completion notice and extracts the transaction UUID from it.
     *
     * @return string The transaction UUID if found, or an empty string otherwise.
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
