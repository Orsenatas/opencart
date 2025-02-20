<?php
namespace Opencart\Catalog\Controller\Account;
class Subscription extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$this->load->language('account/subscription');

		if (!$this->customer->isLogged() || (!isset($this->request->get['customer_token']) || !isset($this->session->data['customer_token']) || ($this->request->get['customer_token'] != $this->session->data['customer_token']))) {
			$this->session->data['redirect'] = $this->url->link('account/subscription', 'language=' . $this->config->get('config_language'));

			$this->response->redirect($this->url->link('account/login', 'language=' . $this->config->get('config_language')));
		}

		$this->document->setTitle($this->language->get('heading_title'));

		$url = '';

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'language=' . $this->config->get('config_language'))
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_account'),
			'href' => $this->url->link('account/account', 'language=' . $this->config->get('config_language') . '&customer_token=' . $this->session->data['customer_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('account/subscription', 'language=' . $this->config->get('config_language') . '&customer_token=' . $this->session->data['customer_token'] . $url)
		];

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		$limit = 10;

		$data['subscriptions'] = [];

		$this->load->model('account/subscription');
		$this->load->model('account/order');
		$this->load->model('catalog/product');
		$this->load->model('localisation/currency');
		$this->load->model('localisation/subscription_status');

		$subscription_total = $this->model_account_subscription->getTotalSubscriptions();

		$results = $this->model_account_subscription->getSubscriptions(($page - 1) * $limit, $limit);

		foreach ($results as $result) {
			$product_info = $this->model_catalog_product->getProduct($result['product_id']);

			if ($product_info) {
				$currency_info = $this->model_localisation_currency->getCurrency($result['currency_id']);

				if ($currency_info) {
					$currency = $currency_info['code'];
				} else {
					$currency = $this->config->get('config_currency');
				}

				$description = '';

				if ($result['trial_status']) {
					$trial_price = $this->currency->format($this->tax->calculate($result['trial_price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $currency);
					$trial_cycle = $result['trial_cycle'];
					$trial_frequency = $this->language->get('text_' . $result['trial_frequency']);
					$trial_duration = $result['trial_duration'];

					$description .= sprintf($this->language->get('text_subscription_trial'), $trial_price, $trial_cycle, $trial_frequency, $trial_duration);
				}

				$price = $this->currency->format($this->tax->calculate($result['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $currency);
				$cycle = $result['cycle'];
				$frequency = $this->language->get('text_' . $result['frequency']);
				$duration = $result['duration'];

				if ($duration) {
					$description .= sprintf($this->language->get('text_subscription_duration'), $price, $cycle, $frequency, $duration);
				} else {
					$description .= sprintf($this->language->get('text_subscription_cancel'), $price, $cycle, $frequency);
				}

				$subscription_status_info = $this->model_localisation_subscription_status->getSubscriptionStatus($result['subscription_status_id']);

				if ($subscription_status_info) {
					$subscription_status = $subscription_status_info['name'];
				} else {
					$subscription_status = '';
				}

				$data['subscriptions'][] = [
					'subscription_id' => $result['subscription_id'],
					'product_id'      => $result['product_id'],
					'product_name'    => $product_info['name'],
					'subscription'    => $description,
					'product'         => $this->url->link('product/product', 'language=' . $this->config->get('config_language') . '&product_id=' . $result['product_id']),
					'status'          => $subscription_status,
					'date_added'      => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
					'view'            => $this->url->link('account/subscription.info', 'language=' . $this->config->get('config_language') . '&customer_token=' . $this->session->data['customer_token'] . '&subscription_id=' . $result['subscription_id'])
				];
			}
		}

		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $subscription_total,
			'page'  => $page,
			'limit' => $limit,
			'url'   => $this->url->link('account/subscription', 'language=' . $this->config->get('config_language') . '&customer_token=' . $this->session->data['customer_token'] . '&page={page}')
		]);

		$data['results'] = sprintf($this->language->get('text_pagination'), ($subscription_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($subscription_total - $limit)) ? $subscription_total : ((($page - 1) * $limit) + $limit), $subscription_total, ceil($subscription_total / $limit));

		$data['continue'] = $this->url->link('account/account', 'language=' . $this->config->get('config_language') . '&customer_token=' . $this->session->data['customer_token']);

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('account/subscription_list', $data));
	}

	public function info(): void {
		$this->load->language('account/subscription');

		if (!$this->customer->isLogged() || (!isset($this->request->get['customer_token']) || !isset($this->session->data['customer_token']) || ($this->request->get['customer_token'] != $this->session->data['customer_token']))) {
			$this->session->data['redirect'] = $this->url->link('account/subscription', 'language=' . $this->config->get('config_language'));

			$this->response->redirect($this->url->link('account/login', 'language=' . $this->config->get('config_language')));
		}

		if (isset($this->request->get['subscription_id'])) {
			$subscription_id = (int)$this->request->get['subscription_id'];
		} else {
			$subscription_id = 0;
		}

		$this->load->model('account/subscription');

		$subscription_info = $this->model_account_subscription->getSubscription($subscription_id);

		if ($subscription_info) {
			$this->document->setTitle($this->language->get('text_subscription'));

			$url = '';

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$data['breadcrumbs'] = [];

			$data['breadcrumbs'][] = [
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/home', 'language=' . $this->config->get('config_language'))
			];

			$data['breadcrumbs'][] = [
				'text' => $this->language->get('text_account'),
				'href' => $this->url->link('account/account', 'language=' . $this->config->get('config_language') . '&customer_token=' . $this->session->data['customer_token'])
			];

			$data['breadcrumbs'][] = [
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('account/subscription', 'language=' . $this->config->get('config_language') . '&customer_token=' . $this->session->data['customer_token'] . $url)
			];

			$data['breadcrumbs'][] = [
				'text' => $this->language->get('text_subscription'),
				'href' => $this->url->link('account/subscription.info', 'language=' . $this->config->get('config_language') . '&customer_token=' . $this->session->data['customer_token'] . '&subscription_id=' . $this->request->get['subscription_id'] . $url)
			];

			$data['subscription_id'] = $subscription_info['subscription_id'];
			$data['order_id'] = $subscription_info['order_id'];
			$data['date_added'] = date($this->language->get('date_format_short'), strtotime($subscription_info['date_added']));

			$this->load->model('catalog/product');

			$product_info = $this->model_catalog_product->getProduct($subscription_info['product_id']);

			$currency_info = $this->model_localisation_currency->getCurrency($subscription_info['currency_id']);

			if ($currency_info) {
				$currency = $currency_info['code'];
			} else {
				$currency = $this->config->get('config_currency');
			}

			$this->load->model('localisation/subscription_status');

			$subscription_status_info = $this->model_localisation_subscription_status->getSubscriptionStatus($subscription_info['subscription_status_id']);

			if ($subscription_status_info) {
				$data['subscription_status'] = $subscription_status_info['name'];
			} else {
				$data['subscription_status'] = '';
			}

			$data['payment_method'] = $subscription_info['payment_method']['name'];

			$data['name'] = $subscription_info['name'];
			$data['quantity'] = $subscription_info['quantity'];

			$this->load->model('catalog/subscription_plan');

			$subscription_plan_info = $this->model_catalog_subscription_plan->getSubscriptionPlan($subscription_info['subscription_plan_id']);

			if ($subscription_plan_info) {
				$data['subscription_plan'] = $subscription_plan_info['name'];
			} else {
				$data['subscription_plan'] = '';
			}

			$data['description'] = '';

			if ($subscription_info['trial_status']) {
				$trial_price = $this->currency->format($this->tax->calculate($subscription_info['trial_price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $currency);
				$trial_cycle = $subscription_info['trial_cycle'];
				$trial_frequency = $this->language->get('text_' . $subscription_info['trial_frequency']);
				$trial_duration = $subscription_info['trial_duration'];

				$data['description'] .= sprintf($this->language->get('text_subscription_trial'), $trial_price, $trial_cycle, $trial_frequency, $trial_duration);
			}

			$price = $this->currency->format($this->tax->calculate($subscription_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $currency);
			$cycle = $subscription_info['cycle'];
			$frequency = $this->language->get('text_' . $subscription_info['frequency']);
			$duration = $subscription_info['duration'];

			if ($duration) {
				$data['description'] .= sprintf($this->language->get('text_subscription_duration'), $price, $cycle, $frequency, $duration);
			} else {
				$data['description'] .= sprintf($this->language->get('text_subscription_cancel'), $price, $cycle, $frequency);
			}

			// Orders
			$data['history'] = $this->getHistory();
			$data['orders'] = $this->getOrder();

			$data['order'] = $this->url->link('account/order.info', 'language=' . $this->config->get('config_language') . '&customer_token=' . $this->session->data['customer_token'] . '&order_id=' . $subscription_info['order_id']);
			$data['product'] = $this->url->link('product/product', 'language=' . $this->config->get('config_language') . '&customer_token=' . $this->session->data['customer_token'] . '&product_id=' . $subscription_info['product_id']);

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('account/subscription_info', $data));
		} else {
			$this->document->setTitle($this->language->get('text_subscription'));

			$data['breadcrumbs'] = [];

			$data['breadcrumbs'][] = [
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/home', 'language=' . $this->config->get('config_language'))
			];

			$data['breadcrumbs'][] = [
				'text' => $this->language->get('text_account'),
				'href' => $this->url->link('account/account', 'language=' . $this->config->get('config_language') . '&customer_token=' . $this->session->data['customer_token'])
			];

			$data['breadcrumbs'][] = [
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('account/subscription', 'language=' . $this->config->get('config_language') . '&customer_token=' . $this->session->data['customer_token'])
			];

			$data['breadcrumbs'][] = [
				'text' => $this->language->get('text_subscription'),
				'href' => $this->url->link('account/subscription.info', 'language=' . $this->config->get('config_language') . '&customer_token=' . $this->session->data['customer_token'] . '&subscription_id=' . $subscription_id)
			];

			$data['continue'] = $this->url->link('account/subscription', 'language=' . $this->config->get('config_language') . '&customer_token=' . $this->session->data['customer_token']);

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('error/not_found', $data));
		}
	}

	public function order(): void {
		$this->load->language('account/subscription');

		$this->response->setOutput($this->getOrder());
	}

	public function getOrder(): string {
		if (isset($this->request->get['subscription_id'])) {
			$subscription_id = (int)$this->request->get['subscription_id'];
		} else {
			$subscription_id = 0;
		}

		if (isset($this->request->get['page']) && $this->request->get['route'] == 'account/subscription.order') {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		$limit = 10;

		$data['orders'] = [];

		$this->load->model('account/order');

		$results = $this->model_account_order->getOrdersBySubscriptionId($subscription_id, ($page - 1) * $limit, $limit);

		foreach ($results as $result) {
			$data['orders'][] = [
				'order_id'   => $result['order_id'],
				'status'     => $result['status'],
				'total'      => $this->currency->format($result['total'], $result['currency_code'], $result['currency_value']),
				'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
				'view'       => $this->url->link('sale/subscription.order', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $result['order_id'] . '&page={page}')
			];
		}

		$order_total = $this->model_account_order->getTotalOrdersBySubscriptionId($subscription_id);

		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $order_total,
			'page'  => $page,
			'limit' => $limit,
			'url'   => $this->url->link('sale/subscription.order', 'user_token=' . $this->session->data['user_token'] . '&subscription_id=' . $subscription_id . '&page={page}')
		]);

		$data['results'] = sprintf($this->language->get('text_pagination'), ($order_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($order_total - $limit)) ? $order_total : ((($page - 1) * $limit) + $limit), $order_total, ceil($order_total / $limit));

		return $this->load->view('account/subscription_order', $data);
	}

	public function history(): void {
		$this->load->language('account/subscription');

		$this->response->setOutput($this->getHistory());
	}

	public function getHistory(): string {
		if (isset($this->request->get['subscription_id'])) {
			$subscription_id = (int)$this->request->get['subscription_id'];
		} else {
			$subscription_id = 0;
		}

		if (isset($this->request->get['page']) && $this->request->get['route'] == 'account/subscription.history') {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		$limit = 10;

		$data['histories'] = [];

		$this->load->model('account/subscription');

		$results = $this->model_account_subscription->getHistories($subscription_id, ($page - 1) * $limit, $limit);

		foreach ($results as $result) {
			$data['histories'][] = [
				'status'     => $result['status'],
				'comment'    => nl2br($result['comment']),
				'notify'     => $result['notify'] ? $this->language->get('text_yes') : $this->language->get('text_no'),
				'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added']))
			];
		}

		$subscription_total = $this->model_account_subscription->getTotalHistories($subscription_id);

		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $subscription_total,
			'page'  => $page,
			'limit' => $limit,
			'url'   => $this->url->link('account/subscription.history', 'user_token=' . $this->session->data['user_token'] . '&subscription_id=' . $subscription_id . '&page={page}')
		]);

		$data['results'] = sprintf($this->language->get('text_pagination'), ($subscription_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($subscription_total - $limit)) ? $subscription_total : ((($page - 1) * $limit) + $limit), $subscription_total, ceil($subscription_total / $limit));

		return $this->load->view('account/subscription_history', $data);
	}
}
