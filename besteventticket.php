<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class BestEventTicket extends Module
{
    public function __construct()
    {
        $this->name = 'besteventticket';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'BESTLAB';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('BESTLAB Event Ticket');
        $this->description = $this->l('Obsługa potwierdzeń uczestników wydarzeń na podstawie biletów.');
        $this->ps_versions_compliancy = [
            'min' => '8.0.0',
            'max' => _PS_VERSION_,
        ];
    }

    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        require_once __DIR__ . '/sql/install.php';

        if (!bestEventTicketInstallSql()) {
            return false;
        }

        if (!$this->installAdminTab()) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        require_once __DIR__ . '/sql/uninstall.php';

        if (!$this->uninstallAdminTab()) {
            return false;
        }

        if (!bestEventTicketUninstallSql()) {
            return false;
        }

        return parent::uninstall();
    }

    public function getContent()
	{
		$output = '';

		if (Tools::isSubmit('submitBestEventTicketTestToken')) {
			$idOrder = (int) Tools::getValue('BET_TEST_ID_ORDER');
			$idProduct = (int) Tools::getValue('BET_TEST_ID_PRODUCT');

			if ($idOrder > 0 && $idProduct > 0) {
				$token = $this->buildConfirmationToken($idOrder, $idProduct);
				$url = $this->context->link->getModuleLink(
					$this->name,
					'confirm',
					['token' => $token]
				);

				$updated = Db::getInstance()->update(
					'bestlab_event_ticket',
					[
						'confirmation_token' => pSQL($token),
						'date_upd' => date('Y-m-d H:i:s'),
					],
					'id_order = ' . (int) $idOrder . ' AND id_product = ' . (int) $idProduct
				);

				if ($updated) {
					$output .= $this->displayConfirmation(
						$this->l('Wygenerowano i przypisano token. Link testowy:') .
						'<br><a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" target="_blank">' .
						htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '</a>'
					);
				} else {
					$output .= $this->displayError($this->l('Nie udało się przypisać tokenu do rekordów.'));
				}
			} else {
				$output .= $this->displayError($this->l('Podaj poprawne ID zamówienia i ID produktu.'));
			}
		}

		if (Tools::isSubmit('submitBestEventTicketSendTestMail')) {
			$idOrder = (int) Tools::getValue('BET_MAIL_ID_ORDER');
			$idProduct = (int) Tools::getValue('BET_MAIL_ID_PRODUCT');
			$overrideEmail = trim((string) Tools::getValue('BET_MAIL_OVERRIDE_EMAIL'));

			$result = $this->sendConfirmationMail(
				$idOrder,
				$idProduct,
				$overrideEmail !== '' ? $overrideEmail : null
			);

			if (!empty($result['success'])) {
				$message = $result['message'];

				if (!empty($result['confirm_url'])) {
					$message .= '<br>URL potwierdzenia:<br><a href="' .
						htmlspecialchars($result['confirm_url'], ENT_QUOTES, 'UTF-8') .
						'" target="_blank">' .
						htmlspecialchars($result['confirm_url'], ENT_QUOTES, 'UTF-8') .
						'</a>';
				}

				$output .= $this->displayConfirmation($message);
			} else {
				$output .= $this->displayError(
					!empty($result['message']) ? $result['message'] : $this->l('Nie udało się wysłać maila testowego.')
				);
			}
		}

		$reportUrl = $this->context->link->getAdminLink('AdminBestEventTicket');

		$output .= '<div class="panel">';
		$output .= '<h3>Raport biletów</h3>';
		$output .= '<p><a class="btn btn-default" href="' . htmlspecialchars($reportUrl, ENT_QUOTES, 'UTF-8') . '" target="_blank">';
		$output .= '<i class="process-icon-preview"></i> Otwórz raport biletów';
		$output .= '</a></p>';
		$output .= '</div>';

		$helperToken = new HelperForm();
		$helperToken->module = $this;
		$helperToken->name_controller = $this->name;
		$helperToken->token = Tools::getAdminTokenLite('AdminModules');
		$helperToken->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
		$helperToken->submit_action = 'submitBestEventTicketTestToken';
		$helperToken->fields_value = [
			'BET_TEST_ID_ORDER' => (string) Tools::getValue('BET_TEST_ID_ORDER', ''),
			'BET_TEST_ID_PRODUCT' => (string) Tools::getValue('BET_TEST_ID_PRODUCT', '95'),
		];

		$fieldsFormToken = [
			'form' => [
				'legend' => [
					'title' => $this->l('Test generatora linku'),
					'icon' => 'icon-ticket',
				],
				'input' => [
					[
						'type' => 'text',
						'label' => $this->l('ID zamówienia'),
						'name' => 'BET_TEST_ID_ORDER',
						'required' => true,
					],
					[
						'type' => 'text',
						'label' => $this->l('ID produktu'),
						'name' => 'BET_TEST_ID_PRODUCT',
						'required' => true,
						'desc' => $this->l('Np. 95 / 96 / 97'),
					],
				],
				'submit' => [
					'title' => $this->l('Generuj link testowy'),
					'name' => 'submitBestEventTicketTestToken',
				],
			],
		];

		$helperMail = new HelperForm();
		$helperMail->module = $this;
		$helperMail->name_controller = $this->name;
		$helperMail->token = Tools::getAdminTokenLite('AdminModules');
		$helperMail->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
		$helperMail->submit_action = 'submitBestEventTicketSendTestMail';
		$helperMail->fields_value = [
			'BET_MAIL_ID_ORDER' => (string) Tools::getValue('BET_MAIL_ID_ORDER', ''),
			'BET_MAIL_ID_PRODUCT' => (string) Tools::getValue('BET_MAIL_ID_PRODUCT', '95'),
			'BET_MAIL_OVERRIDE_EMAIL' => (string) Tools::getValue('BET_MAIL_OVERRIDE_EMAIL', ''),
		];

		$fieldsFormMail = [
			'form' => [
				'legend' => [
					'title' => $this->l('Test wysyłki maila'),
					'icon' => 'icon-envelope',
				],
				'input' => [
					[
						'type' => 'text',
						'label' => $this->l('ID zamówienia'),
						'name' => 'BET_MAIL_ID_ORDER',
						'required' => true,
					],
					[
						'type' => 'text',
						'label' => $this->l('ID produktu'),
						'name' => 'BET_MAIL_ID_PRODUCT',
						'required' => true,
						'desc' => $this->l('Np. 95 / 96 / 97'),
					],
					[
						'type' => 'text',
						'label' => $this->l('Override e-mail'),
						'name' => 'BET_MAIL_OVERRIDE_EMAIL',
						'required' => false,
						'desc' => $this->l('Opcjonalnie. Jeśli podasz adres, mail testowy poleci na ten adres zamiast na e-mail klienta.'),
					],
				],
				'submit' => [
					'title' => $this->l('Wyślij testowy mail'),
					'name' => 'submitBestEventTicketSendTestMail',
				],
			],
		];

		return $output
			. $helperToken->generateForm([$fieldsFormToken])
			. $helperMail->generateForm([$fieldsFormMail]);
	}

	

    public function buildConfirmationToken(int $idOrder, int $idProduct): string
    {
        return hash('sha256', $idOrder . '|' . $idProduct . '|' . _COOKIE_KEY_);
    }

	
	protected function getEventData($idProduct)
	{
		$map = [
			95 => [
				'name' => 'Tour de Bestlab Szczecin',
				'date' => '12 maja 2026',
				'address' => "Courtyard by Marriott Szczecin City\nPl. Brama Portowa 2\n70-225 Szczecin",
			],
			96 => [
				'name' => 'Tour de Bestlab Poznań',
				'date' => '13 maja 2026',
				'address' => "Hotel Mercure Poznań Centrum\nFranklina Roosevelta 20\n60-829 Poznań",
			],
			97 => [
				'name' => 'Tour de Bestlab Warszawa',
				'date' => '14 maja 2026',
				'address' => "Centrum Prasowe Foksal\nul. Foksal 3/5\n00-366 Warszawa",
			],
		];

		return $map[(int) $idProduct] ?? null;
	}

	public function sendConfirmationMail($idOrder, $idProduct, $overrideEmail = null)
	{
		$idOrder = (int) $idOrder;
		$idProduct = (int) $idProduct;

		if ($idOrder <= 0 || $idProduct <= 0) {
			return [
				'success' => false,
				'message' => 'Nieprawidłowe id_order lub id_product.',
			];
		}

		$rows = Db::getInstance()->executeS('
			SELECT *
			FROM `' . _DB_PREFIX_ . 'bestlab_event_ticket`
			WHERE `id_order` = ' . $idOrder . '
			  AND `id_product` = ' . $idProduct . '
			ORDER BY `ticket_position` ASC
		');

		if (empty($rows)) {
			return [
				'success' => false,
				'message' => 'Nie znaleziono rekordów dla wskazanej grupy id_order + id_product.',
			];
		}

		$row = $rows[0];

		$token = trim((string) $row['confirmation_token']);

		if ($token === '') {
			$token = $this->buildConfirmationToken($idOrder, $idProduct);

			$updated = Db::getInstance()->update(
				'bestlab_event_ticket',
				[
					'confirmation_token' => pSQL($token),
					'date_upd' => date('Y-m-d H:i:s'),
				],
				'id_order = ' . (int) $idOrder . ' AND id_product = ' . (int) $idProduct
			);

			if (!$updated) {
				return [
					'success' => false,
					'message' => 'Nie udało się wygenerować i zapisać confirmation_token dla tej grupy.',
				];
			}

			$row['confirmation_token'] = $token;
		}

		$event = $this->getEventData($idProduct);

		if (!$event) {
			return [
				'success' => false,
				'message' => 'Brak konfiguracji wydarzenia dla id_product = ' . $idProduct,
			];
		}

		$context = Context::getContext();
		$idLang = (int) $context->language->id;

		$confirmUrl = $context->link->getModuleLink(
			$this->name,
			'confirm',
			['token' => $row['confirmation_token']],
			true
		);

		$recipientEmail = $overrideEmail ? trim((string) $overrideEmail) : trim((string) $row['customer_email']);
		$recipientName = trim((string) ($row['customer_firstname'] . ' ' . $row['customer_lastname']));

		if ($recipientEmail === '') {
			return [
				'success' => false,
				'message' => 'Brak adresu e-mail odbiorcy.',
			];
		}

		$eventAddressText = $event['address'];
		$eventAddressHtml = nl2br($event['address']);

		$templateVars = [
			'{firstname}' => (string) $row['customer_firstname'],
			'{lastname}' => (string) $row['customer_lastname'],
			'{event_name}' => (string) $event['name'],
			'{event_date}' => (string) $event['date'],
			'{event_address}' => (string) $eventAddressText,
			'{event_address_html}' => (string) $eventAddressHtml,
			'{ticket_qty}' => (string) count($rows),
			'{order_id}' => (string) $idOrder,
			'{confirm_url}' => (string) $confirmUrl,
		];

		$subject = 'Tour de Bestlab – potwierdzenie obecności';

		$sent = Mail::Send(
			$idLang,
			'confirm_presence',
			$subject,
			$templateVars,
			$recipientEmail,
			$recipientName,
			null,
			null,
			null,
			null,
			_PS_MODULE_DIR_ . $this->name . '/mails/'
		);

		if (!$sent) {
			return [
				'success' => false,
				'message' => 'Mail::Send() zwrócił false.',
			];
		}

		return [
			'success' => true,
			'message' => 'Mail został wysłany poprawnie na adres: ' . $recipientEmail,
			'to' => $recipientEmail,
			'confirm_url' => $confirmUrl,
		];
	}

	protected function installAdminTab(): bool
	{
		$existingId = (int) Tab::getIdFromClassName('AdminBestEventTicket');
		if ($existingId > 0) {
			return true;
		}

		$tab = new Tab();
		$tab->active = 1;
		$tab->class_name = 'AdminBestEventTicket';
		$tab->module = $this->name;
		$tab->id_parent = 492;

		$tab->name = [];
		foreach (Language::getLanguages(true) as $lang) {
			$tab->name[(int) $lang['id_lang']] = 'Tour de Bestlab';
		}

		return (bool) $tab->add();
	}

	protected function uninstallAdminTab(): bool
	{
		$idTab = (int) Tab::getIdFromClassName('AdminBestEventTicket');

		if ($idTab <= 0) {
			return true;
		}

		$tab = new Tab($idTab);

		return (bool) $tab->delete();
	}
}