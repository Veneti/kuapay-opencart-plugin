<?php
require_once dirname(__FILE__) . '/../../../externals/Kuapay/library/php/Kuapay/loadall.php';

class ControllerPaymentKuapay extends Controller {
    const PURCHASE_RESOURCE = "purchase/";
    const NEW_ACTION        = "new";

    protected function index() {
        $lang = $this->language->get('code');

        $this->data['locale_code'] = $lang;

        $this->language->load('payment/kuapay');

        $this->data['button_confirm'] = $this->language->get('button_confirm');

        $this->data['error_could_not_initialize_kuapay'] = $this->language->get('error_could_not_initialize_kuapay');

        $this->load->model('checkout/order');

        $orderInfo = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        $errorMessage = null;

        if (!$orderInfo) {
            $errorMessage = $this->language->get('error_getting_order');
        } else {
            $currency = $this->config->get('kuapay_currency');

            if ($orderInfo['currency_code'] == $currency) {
                $this->data['business']  = $this->config->get('kuapay_email');
                $this->data['item_name'] = html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8');

                $this->data['products'] = array();

                foreach ($this->cart->getProducts() as $product) {
                    $option_data = array();

                    foreach ($product['option'] as $option) {
                        $option_data[] = array(
                            'name'  => $option['name'],
                            'value' => $option['option_value']
                        );
                    }

                    $this->data['products'][] = array(
                        'name'     => $product['name'],
                        'model'    => $product['model'],
                        'price'    => $this->currency->format($product['price'], $currency, false, false),
                        'quantity' => $product['quantity'],
                        'option'   => $option_data,
                        'weight'   => $product['weight']
                    );
                }

                $this->data['discount_amount_cart'] = 0;

                $total = $this->currency->format($orderInfo['total'] - $this->cart->getSubTotal(), $currency, false, false);

                if ($total > 0) {
                    $this->data['products'][] = array(
                        'name'     => $this->language->get('text_total'),
                        'model'    => '',
                        'price'    => $total,
                        'quantity' => 1,
                        'option'   => array(),
                        'weight'   => 0
                    );
                } else {
                    $this->data['discount_amount_cart'] -= $this->currency->format($total, $currency, false, false);
                }

                $this->data['currency_code'] = $currency;
                $this->data['first_name'] = html_entity_decode($orderInfo['payment_firstname'], ENT_QUOTES, 'UTF-8');
                $this->data['last_name'] = html_entity_decode($orderInfo['payment_lastname'], ENT_QUOTES, 'UTF-8');
                $this->data['address1'] = html_entity_decode($orderInfo['payment_address_1'], ENT_QUOTES, 'UTF-8');
                $this->data['address2'] = html_entity_decode($orderInfo['payment_address_2'], ENT_QUOTES, 'UTF-8');
                $this->data['city'] = html_entity_decode($orderInfo['payment_city'], ENT_QUOTES, 'UTF-8');
                $this->data['zip'] = html_entity_decode($orderInfo['payment_postcode'], ENT_QUOTES, 'UTF-8');
                $this->data['country'] = $orderInfo['payment_iso_code_2'];
                $this->data['email'] = $orderInfo['email'];
                $this->data['invoice'] = $this->session->data['order_id'] . ' - ' . html_entity_decode($orderInfo['payment_firstname'], ENT_QUOTES, 'UTF-8') . ' ' . html_entity_decode($orderInfo['payment_lastname'], ENT_QUOTES, 'UTF-8');
                $this->data['lc'] = $this->session->data['language'];
                $this->data['return'] = $this->url->link('checkout/success');
                $this->data['cancel_return'] = $this->url->link('checkout/checkout', '', 'SSL');

                $this->load->library('encryption');

                $encryption = new Encryption($this->config->get('config_encryption'));

                $this->data['custom'] = $encryption->encrypt($this->session->data['order_id']);

                if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/kuapay.tpl')) {
                    $this->template = $this->config->get('config_template') . '/template/payment/kuapay.tpl';
                } else {
                    $this->template = 'default/template/payment/kuapay.tpl';
                }

                $this->render();
            }
        }
    }

    public function bill() {
        $this->language->load('payment/kuapay');

        $debug = $this->config->get('kuapay_debug');

        $this->load->library('encryption');

        $encryption = new Encryption($this->config->get('config_encryption'));

        $this->load->model('checkout/order');

        $orderInfo = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        $this->load->model('payment/kuapay');

        $totals = $this->model_payment_kuapay->getTotals($this->session->data['order_id']);

        $total = 0;
        $subtotal = 0;
        $tax = 0;
        $costProducts = array();

        foreach ($totals->rows as $totalRow) {
            switch ($totalRow['code']) {
                case 'total':
                    $total += $totalRow['value'];
                    break;
                case 'tax':
                    $tax += $totalRow['value'];
                    break;
                case 'sub_total':
                    $subtotal += $totalRow['value'];
                    break;
                default:
                    $costProducts[] = array(
                        'product_id' => '0000',
                        'name' => $totalRow['title'],
                        'quantity' => 1,
                        'price' => $totalRow['value']
                    );
                    $subtotal += $totalRow['value'];
            }
        }

        $json = array();

        if (!$orderInfo) {
            $json['error'] = $this->language->get('error_getting_order');
        } else {
            $bill = new Kuapay_Bill();
            $bill->setSubtotal($subtotal);
            $bill->setTotal($total);
            $bill->setTax($tax);

            $billDetails = new Kuapay_BillDetails();

            $cartProducts = $this->cart->getProducts();

            $products = array_merge($cartProducts, $costProducts);

            foreach ($products as $product) {
                $billDetails->append(new Kuapay_BillDetail(array(
                    "id" => $product['product_id'],
                    "name" => htmlspecialchars($product['name']),
                    "quantity" => $product['quantity'],
                    "price" => $product['price']
                )));

            }

            $bill->setDetails($billDetails);

            $purchase = new Kuapay_Purchase();
            $purchase->setBill($bill);

            $qrcode = preg_replace('~[^0-9]~', '', $this->request->post['qrcode']);

            $purchase->setQRCode($qrcode);
            $purchase->setSerial($this->config->get('kuapay_pos_serial'));
            $purchase->setEmail($this->config->get('kuapay_email'));
            $purchase->setPassword($this->config->get('kuapay_password'));

            $client = new Kuapay_Client();
            $kuapayApiUrl = $this->config->get('kuapay_api_uri');
            if (!empty($kuapayApiUrl)) {
                $client->getAdapter()->setApiUrl($kuapayApiUrl);
            }

            try {
                $purchaseId = $client->purchase($purchase);
                $json['pid'] = $purchaseId;

                $orderStatusId = $this->config->get('kuapay_started_status_id');

                $orderId = $this->session->data['order_id'];
                $this->model_checkout_order->confirm($orderId, $orderStatusId);
            } catch (Kuapay_Exception $ke) {
                if ($debug) {
                    $this->log->write('KUAPY: bill exception: ' . $ke->getMessage() . ' ' . $ke->getFile() . ':' . $ke->getLine());
                }
                $json['error'] = $this->language->get('error_connection');
            }
        }

        $this->response->setOutput(json_encode($json));

    }

    public function status() {
        $this->language->load('payment/kuapay');

        $debug = $this->config->get('kuapay_debug');

        $this->load->library('encryption');

        $encryption = new Encryption($this->config->get('config_encryption'));

        $this->load->model('checkout/order');

        $orderInfo = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        $purchaseId = $this->request->post['pid'];
        $json = array();

        $client = new Kuapay_Client();
        $kuapayApiUrl = $this->config->get('kuapay_api_uri');
        if (!empty($kuapayApiUrl)) {
            $client->getAdapter()->setApiUrl($kuapayApiUrl);
        }
        if ($debug) {
            $client->getAdapter()->setDebug(true);
            $client->getAdapter()->setLogger(new Kuapay_Logger_OpenCart(array('logger' => $this->log)));
        }

        $result = null;

        try {
            $result = $client->status($purchaseId);
        } catch (Kuapay_Exception $ke) {
            $json['error'] = $this->language->get('error_connection');
        }

        if (is_object($result)) {
            $json['status_code'] = $result->value->status_code;

            switch ($result->value->status_code) {
                case -5:
                    $orderStatusId = $this->config->get('kuapay_error_invalid_card');
                break;
                case -4:
                    $orderStatusId = $this->config->get('kuapay_error_declined');
                break;
                case -3:
                    $orderStatusId = $this->config->get('kuapay_error_authorization_id');
                break;
                case -2:
                    $orderStatusId = $this->config->get('kuapay_error_with_login_credentials_status_id');
                break;
                case -1:
                    $orderStatusId = $this->config->get('kuapay_error_with_identificator_code_status_id');
                break;
                case 0:
                    $orderStatusId = $this->config->get('kuapay_started_status_id');
                break;
                case 1:
                    $orderStatusId = $this->config->get('kuapay_sending_bill_status_id');
                break;
                case 2:
                    $orderStatusId = $this->config->get('kuapay_authorizing_status_id');
                break;
                case 3:
                    $orderStatusId = $this->config->get('kuapay_sending_confirmation_status_id');
                break;
                case 4:
                    $orderStatusId = $this->config->get('kuapay_completed_status_id');
                break;
                default:
                    $orderStatusId = $this->config->get('kuapay_error_unknown');
                break;

            }

            $orderId = $this->session->data['order_id'];
            $this->model_checkout_order->update($orderId, $orderStatusId);
        }

        $this->response->setOutput(json_encode($json));
    }
}
?>