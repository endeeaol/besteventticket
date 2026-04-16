<?php

class BestEventTicketConfirmModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $token = trim((string) Tools::getValue('token'));

        if ($token === '') {
            $this->context->smarty->assign([
                'bet_error' => 'Brak tokenu potwierdzenia.',
            ]);
            $this->setTemplate('module:besteventticket/views/templates/front/confirm_form.tpl');
            return;
        }

        $tickets = $this->getTicketsByToken($token);

        if (empty($tickets)) {
            $this->context->smarty->assign([
                'bet_error' => 'Nie znaleziono biletów dla podanego linku.',
            ]);
            $this->setTemplate('module:besteventticket/views/templates/front/confirm_form.tpl');
            return;
        }

        if (Tools::isSubmit('submitBestEventTicketConfirmation')) {
            $result = $this->handleSubmit($tickets);

            if ($result['ok']) {
                $this->context->smarty->assign([
                    'event_name' => $tickets[0]['event_name'],
                    'id_order' => $tickets[0]['id_order'],
                    'confirmed_count' => $result['confirmed_count'],
                    'total_count' => count($tickets),
                ]);
                $this->setTemplate('module:besteventticket/views/templates/front/confirm_success.tpl');
                return;
            }

            $this->context->smarty->assign([
                'bet_error' => $result['message'],
            ]);
        }

        $this->context->smarty->assign($this->buildTemplateVars($tickets, $token));
        $this->setTemplate('module:besteventticket/views/templates/front/confirm_form.tpl');
    }

    protected function getTicketsByToken(string $token): array
    {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('bestlab_event_ticket');
        $sql->where('confirmation_token = "' . pSQL($token) . '"');
        $sql->orderBy('ticket_position ASC');

        return Db::getInstance()->executeS($sql) ?: [];
    }

    protected function buildTemplateVars(array $tickets, string $token): array
    {
        $first = $tickets[0];
        $qty = (int) $first['qty_in_order'];

        $defaultSingleGuestName = trim(
            (string) $first['customer_firstname'] . ' ' . (string) $first['customer_lastname']
        );

        return [
            'bet_error' => null,
            'bet_token' => $token,
            'bet_tickets' => $tickets,
            'bet_qty' => $qty,
            'bet_id_order' => $first['id_order'],
            'bet_event_name' => $first['event_name'],
            'bet_default_single_guest_name' => $defaultSingleGuestName,
            'bet_action' => $this->context->link->getModuleLink(
                $this->module->name,
                'confirm',
                ['token' => $token]
            ),
        ];
    }

    protected function handleSubmit(array $tickets): array
    {
        $db = Db::getInstance();
        $now = date('Y-m-d H:i:s');

        if (count($tickets) === 1) {
            $ticket = $tickets[0];

            $guestName = trim(
                (string) $ticket['customer_firstname'] . ' ' . (string) $ticket['customer_lastname']
            );

            if ($guestName === '') {
                $guestName = trim((string) $ticket['customer_email']);
            }

            $ok = $db->update(
                'bestlab_event_ticket',
                [
                    'guest_name' => pSQL($guestName),
                    'confirmation' => 1,
                    'confirmed_at' => $now,
                    'date_upd' => $now,
                ],
                'id_bestlab_event_ticket = ' . (int) $ticket['id_bestlab_event_ticket']
            );

            if (!$ok) {
                return [
                    'ok' => false,
                    'message' => 'Nie udało się zapisać potwierdzenia.',
                ];
            }

            return [
                'ok' => true,
                'confirmed_count' => 1,
            ];
        }

        $confirmedCount = 0;

        foreach ($tickets as $ticket) {
            $position = (int) $ticket['ticket_position'];
            $fieldName = 'guest_name_' . $position;
            $guestName = trim((string) Tools::getValue($fieldName));

            $data = [
                'guest_name' => $guestName !== '' ? pSQL($guestName) : null,
                'confirmation' => $guestName !== '' ? 1 : 0,
                'confirmed_at' => $now,
                'date_upd' => $now,
            ];

            $ok = $db->update(
                'bestlab_event_ticket',
                $data,
                'id_bestlab_event_ticket = ' . (int) $ticket['id_bestlab_event_ticket']
            );

            if (!$ok) {
                return [
                    'ok' => false,
                    'message' => 'Nie udało się zapisać wszystkich danych uczestników.',
                ];
            }

            if ($guestName !== '') {
                $confirmedCount++;
            }
        }

        return [
            'ok' => true,
            'confirmed_count' => $confirmedCount,
        ];
    }
}