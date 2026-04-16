<?php

class AdminBestEventTicketController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'bestlab_event_ticket';
        $this->className = '';
        $this->identifier = 'id_bestlab_event_ticket';
        $this->lang = false;
        $this->list_no_link = true;

        parent::__construct();

        $this->_defaultOrderBy = 'date_order';
        $this->_defaultOrderWay = 'DESC';

        $this->fields_list = [
            'id_bestlab_event_ticket' => [
                'title' => 'ID',
                'class' => 'fixed-width-xs',
            ],
            'event_name' => [
                'title' => 'Wydarzenie',
                'filter_key' => 'a!event_name',
            ],
            'id_product' => [
                'title' => 'ID produktu',
                'class' => 'fixed-width-sm',
            ],
            'id_order' => [
                'title' => 'Nr zamówienia',
                'class' => 'fixed-width-sm',
            ],
            'ticket_ref' => [
                'title' => 'Nr biletu',
                'filter_key' => 'a!ticket_ref',
            ],
            'customer_email' => [
                'title' => 'E-mail',
                'filter_key' => 'a!customer_email',
            ],
            'customer_phone' => [
                'title' => 'Telefon',
                'filter_key' => 'a!customer_phone',
            ],
            'guest_name' => [
                'title' => 'Gość',
                'filter_key' => 'a!guest_name',
            ],
            'confirmation_label' => [
                'title' => 'Potwierdzenie',
                'havingFilter' => true,
            ],
            'ticket_position' => [
                'title' => 'Poz.',
                'class' => 'fixed-width-xs',
            ],
            'qty_in_order' => [
                'title' => 'Ilość w zam.',
                'class' => 'fixed-width-xs',
            ],
            'date_order' => [
                'title' => 'Data zamówienia',
                'type' => 'datetime',
            ],
            'confirmed_at' => [
                'title' => 'Data potwierdzenia',
                'type' => 'datetime',
            ],
        ];

        $this->_select = '
            CASE
                WHEN a.confirmation IS NULL THEN "Brak odpowiedzi"
                WHEN a.confirmation = 0 THEN "Niepotwierdzone"
                WHEN a.confirmation = 1 THEN "Potwierdzone"
                ELSE "-"
            END AS confirmation_label
        ';
    }

    protected function getEventStats()
    {
        $rows = Db::getInstance()->executeS('
            SELECT
                id_product,
                COUNT(*) AS total,
                SUM(CASE WHEN confirmation = 1 THEN 1 ELSE 0 END) AS confirmed
            FROM `' . _DB_PREFIX_ . 'bestlab_event_ticket`
            WHERE id_product IN (95, 96, 97)
            GROUP BY id_product
        ');

        $map = [
            95 => ['name' => 'Szczecin'],
            96 => ['name' => 'Poznań'],
            97 => ['name' => 'Warszawa'],
        ];

        $stats = [];

        foreach ($map as $idProduct => $meta) {
            $stats[$idProduct] = [
                'name' => $meta['name'],
                'confirmed' => 0,
                'total' => 0,
            ];
        }

        if (!empty($rows)) {
            foreach ($rows as $row) {
                $id = (int) $row['id_product'];

                if (isset($stats[$id])) {
                    $stats[$id]['confirmed'] = (int) $row['confirmed'];
                    $stats[$id]['total'] = (int) $row['total'];
                }
            }
        }

        return $stats;
    }

    public function renderList()
    {
        $this->toolbar_title = 'Raport biletów wydarzeń';

        $stats = $this->getEventStats();

        $this->context->smarty->assign([
            'bet_event_stats' => $stats,
        ]);

        $statsHtml = $this->renderStatsBlock();
        $listHtml = parent::renderList();

        return $statsHtml . $listHtml;
    }

    protected function renderStatsBlock()
    {
        $template = _PS_MODULE_DIR_ . 'besteventticket/views/templates/admin/stats.tpl';

        if (!file_exists($template)) {
            return '';
        }

        return $this->context->smarty->fetch($template);
    }

    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();

        $this->page_header_toolbar_btn['export_csv'] = [
            'href' => $this->context->link->getAdminLink('AdminBestEventTicket') . '&exportbestlab_event_ticket=1',
            'desc' => 'Eksport CSV',
            'icon' => 'process-icon-export',
        ];
    }

    public function postProcess()
    {
        if (Tools::getValue('exportbestlab_event_ticket')) {
            $this->exportCsv();
        }

        parent::postProcess();
    }

    protected function exportCsv()
    {
        $rows = Db::getInstance()->executeS('
            SELECT
                a.event_name,
                a.id_product,
                a.id_order,
                a.ticket_ref,
                a.customer_email,
                a.customer_phone,
                a.customer_firstname,
                a.customer_lastname,
                a.guest_name,
                a.confirmation,
                a.ticket_position,
                a.qty_in_order,
                a.date_order,
                a.confirmed_at
            FROM `' . _DB_PREFIX_ . 'bestlab_event_ticket` a
            ORDER BY a.event_name ASC, a.id_order ASC, a.ticket_position ASC
        ');

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=besteventticket-report.csv');

        $output = fopen('php://output', 'w');

        fputcsv($output, [
            'event_name',
            'id_product',
            'id_order',
            'ticket_ref',
            'customer_email',
            'customer_phone',
            'customer_firstname',
            'customer_lastname',
            'guest_name',
            'confirmation',
            'ticket_position',
            'qty_in_order',
            'date_order',
            'confirmed_at',
        ], ';');

        foreach ($rows as $row) {
            fputcsv($output, [
                $row['event_name'],
                $row['id_product'],
                $row['id_order'],
                $row['ticket_ref'],
                $row['customer_email'],
                $row['customer_phone'],
                $row['customer_firstname'],
                $row['customer_lastname'],
                $row['guest_name'],
                $row['confirmation'],
                $row['ticket_position'],
                $row['qty_in_order'],
                $row['date_order'],
                $row['confirmed_at'],
            ], ';');
        }

        fclose($output);
        exit;
    }
}