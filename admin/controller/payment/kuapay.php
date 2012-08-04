<?php
class ControllerPaymentKuaPay extends Controller {
    const DEFAULT_API_URI = "https://www.kuapay.com/api/1.0/";

    private $error = array();

    public function index() {
        $this->load->language('payment/kuapay');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('kuapay', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->redirect($this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'));
        }

        $this->data['heading_title'] = $this->language->get('heading_title');

        $this->data['text_enabled'] = $this->language->get('text_enabled');
        $this->data['text_disabled'] = $this->language->get('text_disabled');
        $this->data['text_all_zones'] = $this->language->get('text_all_zones');
        $this->data['text_yes'] = $this->language->get('text_yes');
        $this->data['text_no'] = $this->language->get('text_no');

        $this->data['entry_email'] = $this->language->get('entry_email');
        $this->data['entry_password'] = $this->language->get('entry_password');
        $this->data['entry_pos_serial'] = $this->language->get('entry_pos_serial');
        $this->data['entry_api_uri'] = $this->language->get('entry_api_uri');
        $this->data['entry_debug'] = $this->language->get('entry_debug');
        $this->data['entry_total'] = $this->language->get('entry_total');
        $this->data['entry_currency'] = $this->language->get('entry_currency');

        $this->data['entry_started_status'] = $this->language->get('entry_started_status');
        $this->data['entry_sending_bill_status'] = $this->language->get('entry_sending_bill_status');
        $this->data['entry_authorizing_status'] = $this->language->get('entry_authorizing_status');
        $this->data['entry_sending_confirmation_status'] = $this->language->get('entry_sending_confirmation_status');
        $this->data['entry_completed_status'] = $this->language->get('entry_completed_status');
        $this->data['entry_error_with_identificator_code_status'] = $this->language->get('entry_error_with_identificator_code_status');
        $this->data['entry_error_with_login_credentials_status'] = $this->language->get('entry_error_with_login_credentials_status');
        $this->data['entry_error_with_authorization_status'] = $this->language->get('entry_error_with_authorization_status');
        $this->data['entry_error_invalid_card'] = $this->language->get('entry_error_invalid_card');
        $this->data['entry_error_declined'] = $this->language->get('entry_error_declined');
        $this->data['entry_error_unknown'] = $this->language->get('entry_error_unknown');
        $this->data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
        $this->data['entry_status'] = $this->language->get('entry_status');
        $this->data['entry_sort_order'] = $this->language->get('entry_sort_order');

        $this->data['button_save'] = $this->language->get('button_save');
        $this->data['button_cancel'] = $this->language->get('button_cancel');

         if (isset($this->error['warning'])) {
            $this->data['error_warning'] = $this->error['warning'];
        } else {
            $this->data['error_warning'] = '';
        }

        if (isset($this->error['email'])) {
            $this->data['error_email'] = $this->error['email'];
        } else {
            $this->data['error_email'] = '';
        }

        if (isset($this->error['password'])) {
            $this->data['error_password'] = $this->error['password'];
        } else {
            $this->data['error_password'] = '';
        }

        if (isset($this->error['pos_serial'])) {
            $this->data['error_pos_serial'] = $this->error['pos_serial'];
        } else {
            $this->data['error_pos_serial'] = '';
        }

        if (isset($this->error['api_uri'])) {
            $this->data['error_api_uri'] = $this->error['api_uri'];
        } else {
            $this->data['error_api_uri'] = '';
        }

        $this->data['breadcrumbs'] = array();

           $this->data['breadcrumbs'][] = array(
               'text'      => $this->language->get('text_home'),
            'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
              'separator' => false
           );

           $this->data['breadcrumbs'][] = array(
               'text'      => $this->language->get('text_payment'),
            'href'      => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'),
              'separator' => ' :: '
           );

           $this->data['breadcrumbs'][] = array(
               'text'      => $this->language->get('heading_title'),
            'href'      => $this->url->link('payment/kuapay', 'token=' . $this->session->data['token'], 'SSL'),
              'separator' => ' :: '
           );

        $this->data['action'] = $this->url->link('payment/kuapay', 'token=' . $this->session->data['token'], 'SSL');

        $this->data['cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL');

        if (isset($this->request->post['kuapay_email'])) {
            $this->data['kuapay_email'] = $this->request->post['kuapay_email'];
        } else {
            $this->data['kuapay_email'] = $this->config->get('kuapay_email');
        }

        if (isset($this->request->post['pp_standard_email'])) {
            $this->data['kuapay_password'] = $this->request->post['kuapay_password'];
        } else {
            $this->data['kuapay_password'] = $this->config->get('kuapay_password');
        }

        if (isset($this->request->post['kuapay_pos_serial'])) {
            $this->data['kuapay_pos_serial'] = $this->request->post['kuapay_pos_serial'];
        } else {
            $this->data['kuapay_pos_serial'] = $this->config->get('kuapay_pos_serial');
        }

        if (isset($this->request->post['kuapay_api_uri'])) {
            $this->data['kuapay_api_uri'] = $this->request->post['kuapay_api_uri'];
        } else {
            $this->data['kuapay_api_uri'] = $this->config->get('kuapay_api_uri');
        }

        $this->load->model('localisation/currency');

        $currency_data = $this->model_localisation_currency->getCurrencies();

        $this->data['currencies'] = $this->model_localisation_currency->getCurrencies();

        if (isset($this->request->post['kuapay_currency'])) {
            $this->data['kuapay_currency'] = $this->request->post['kuapay_currency'];
        } else {
            $this->data['kuapay_currency'] = $this->config->get('kuapay_currency');
        }

        if (isset($this->request->post['kuapay_debug'])) {
            $this->data['kuapay_debug'] = $this->request->post['kuapay_debug'];
        } else {
            $this->data['kuapay_debug'] = $this->config->get('kuapay_debug');
        }

        if (isset($this->request->post['kuapay_total'])) {
            $this->data['kuapay_total'] = $this->request->post['kuapay_total'];
        } else {
            $this->data['kuapay_total'] = $this->config->get('kuapay_total');
        }

        if (isset($this->request->post['kuapay_started_status_id'])) {
            $this->data['kuapay_started_status_id'] = $this->request->post['kuapay_started_status_id'];
        } else {
            $this->data['kuapay_started_status_id'] = $this->config->get('kuapay_started_status_id');
        }

        if (isset($this->request->post['kuapay_sending_bill_status_id'])) {
            $this->data['kuapay_sending_bill_status_id'] = $this->request->post['kuapay_sending_bill_status_id'];
        } else {
            $this->data['kuapay_sending_bill_status_id'] = $this->config->get('kuapay_sending_bill_status_id');
        }

        if (isset($this->request->post['kuapay_authorizing_status_id'])) {
            $this->data['kuapay_authorizing_status_id'] = $this->request->post['kuapay_authorizing_status_id'];
        } else {
            $this->data['kuapay_authorizing_status_id'] = $this->config->get('kuapay_authorizing_status_id');
        }

        if (isset($this->request->post['kuapay_sending_confirmation_status_id'])) {
            $this->data['kuapay_sending_confirmation_status_id'] = $this->request->post['kuapay_sending_confirmation_status_id'];
        } else {
            $this->data['kuapay_sending_confirmation_status_id'] = $this->config->get('kuapay_sending_confirmation_status_id');
        }

        if (isset($this->request->post['kuapay_completed_status_id'])) {
            $this->data['kuapay_completed_status_id'] = $this->request->post['kuapay_completed_status_id'];
        } else {
            $this->data['kuapay_completed_status_id'] = $this->config->get('kuapay_completed_status_id');
        }

        if (isset($this->request->post['kuapay_error_with_identificator_code_status_id'])) {
            $this->data['kuapay_error_with_identificator_code_status_id'] = $this->request->post['kuapay_error_with_identificator_code_status_id'];
        } else {
            $this->data['kuapay_error_with_identificator_code_status_id'] = $this->config->get('kuapay_error_with_identificator_code_status_id');
        }

        if (isset($this->request->post['kuapay_error_with_login_credentials_status_id'])) {
            $this->data['kuapay_error_with_login_credentials_status_id'] = $this->request->post['kuapay_error_with_login_credentials_status_id'];
        } else {
            $this->data['kuapay_error_with_login_credentials_status_id'] = $this->config->get('kuapay_error_with_login_credentials_status_id');
        }

        if (isset($this->request->post['kuapay_error_with_authorization_status_id'])) {
            $this->data['kuapay_error_with_authorization_status_id'] = $this->request->post['kuapay_error_with_authorization_status_id'];
        } else {
            $this->data['kuapay_error_with_authorization_status_id'] = $this->config->get('kuapay_error_with_authorization_status_id');
        }

        if (isset($this->request->post['kuapay_error_invalid_card'])) {
            $this->data['kuapay_error_invalid_card'] = $this->request->post['kuapay_error_invalid_card'];
        } else {
            $this->data['kuapay_error_invalid_card'] = $this->config->get('kuapay_error_invalid_card');
        }

        if (isset($this->request->post['kuapay_error_declined'])) {
            $this->data['kuapay_error_declined'] = $this->request->post['kuapay_error_declined'];
        } else {
            $this->data['kuapay_error_declined'] = $this->config->get('kuapay_error_declined');
        }

        if (isset($this->request->post['kuapay_error_unknown'])) {
            $this->data['kuapay_error_unknown'] = $this->request->post['kuapay_error_unknown'];
        } else {
            $this->data['kuapay_error_unknown'] = $this->config->get('kuapay_error_unknown');
        }

        $this->load->model('localisation/order_status');

        $this->data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        if (isset($this->request->post['kuapay_geo_zone_id'])) {
            $this->data['kuapay_geo_zone_id'] = $this->request->post['kuapay_geo_zone_id'];
        } else {
            $this->data['kuapay_geo_zone_id'] = $this->config->get('kuapay_geo_zone_id');
        }

        $this->load->model('localisation/geo_zone');

        $this->data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        if (isset($this->request->post['kuapay_status'])) {
            $this->data['kuapay_status'] = $this->request->post['kuapay_status'];
        } else {
            $this->data['kuapay_status'] = $this->config->get('kuapay_status');
        }

        if (isset($this->request->post['kuapay_sort_order'])) {
            $this->data['kuapay_sort_order'] = $this->request->post['kuapay_sort_order'];
        } else {
            $this->data['kuapay_sort_order'] = $this->config->get('kuapay_sort_order');
        }

        $this->template = 'payment/kuapay.tpl';
        $this->children = array(
            'common/header',
            'common/footer'
        );

        $this->response->setOutput($this->render());
    }

    private function validate() {
        if (!$this->user->hasPermission('modify', 'payment/kuapay')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->request->post['kuapay_email']) {
            $this->error['email'] = $this->language->get('error_email');
        }

        if (!$this->request->post['kuapay_password']) {
            $this->error['password'] = $this->language->get('error_password');
        }

        if (!$this->request->post['kuapay_pos_serial']) {
            $this->error['pos_serial'] = $this->language->get('error_pos_serial');
        }

        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }
}
?>