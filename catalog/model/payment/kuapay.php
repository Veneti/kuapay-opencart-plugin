<?php
class ModelPaymentKuapay extends Model {
    public function getTotals($order_id) {
        $order_total_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_total` WHERE order_id = '" . (int)$order_id . "'");
        return $order_total_query;
    }

    public function getMethod($address, $total) {
        $this->load->language('payment/kuapay');

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('kuapay_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

        if ($this->config->get('kuapay_total') > $total) {
            $status = false;
        } elseif (!$this->config->get('kuapay_geo_zone_id')) {
            $status = true;
        } elseif ($query->num_rows) {
            $status = true;
        } else {
            $status = false;
        }

        $this->load->model('checkout/order');

        $currencyCode = $this->session->data['currency'];
        $posCurrency = $this->config->get('kuapay_currency');

        if ($currencyCode != $posCurrency) {
            $status = false;
        }

        $method_data = array();

        if ($status) {
            $method_data = array(
                'code'       => 'kuapay',
                'title'      => $this->language->get('text_title'),
                'sort_order' => $this->config->get('kuapay_sort_order')
            );
        }

        return $method_data;
    }
}
?>