<?php

require dirname(__FILE__) . '/../../config/config.inc.php';
require dirname(__FILE__) . '/../../init.php';

header('Content-Type: application/json; charset=utf-8');

$secretToken = 'dev7_test_123';
$requestToken = trim((string) Tools::getValue('token'));
$limit = (int) Tools::getValue('limit', 20);

if ($requestToken === '' || $requestToken !== $secretToken) {
    http_response_code(403);
    die(json_encode([
        'ok' => false,
        'message' => 'Unauthorized',
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

if ($limit <= 0) {
    $limit = 20;
}

if ($limit > 100) {
    $limit = 100;
}

$groups = Db::getInstance()->executeS('
    SELECT 
        a.id_order,
        a.id_product
    FROM `' . _DB_PREFIX_ . 'bestlab_event_ticket` a
    WHERE a.mail_sent_at IS NULL
    GROUP BY a.id_order, a.id_product
    ORDER BY MIN(a.date_order) ASC, a.id_order ASC, a.id_product ASC
    LIMIT ' . (int) $limit
);

if (!$groups) {
    die(json_encode([
        'ok' => true,
        'message' => 'Brak grup do wysłania.',
        'processed_groups' => 0,
        'sent' => 0,
        'failed' => 0,
        'remaining' => 0,
        'details' => [],
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

$module = Module::getInstanceByName('besteventticket');

if (!$module || !Validate::isLoadedObject($module)) {
    http_response_code(500);
    die(json_encode([
        'ok' => false,
        'message' => 'Nie udało się załadować modułu besteventticket.',
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

$processed = 0;
$sent = 0;
$failed = 0;
$details = [];

foreach ($groups as $group) {
    $idOrder = (int) $group['id_order'];
    $idProduct = (int) $group['id_product'];
    $processed++;

    try {
        $result = $module->sendConfirmationMail($idOrder, $idProduct);

        if (!empty($result['success'])) {
            $updated = Db::getInstance()->update(
                'bestlab_event_ticket',
                [
                    'mail_sent_at' => date('Y-m-d H:i:s'),
                    'date_upd' => date('Y-m-d H:i:s'),
                ],
                'id_order = ' . (int) $idOrder . ' AND id_product = ' . (int) $idProduct
            );

            if ($updated) {
                $sent++;
                $details[] = [
                    'id_order' => $idOrder,
                    'id_product' => $idProduct,
                    'status' => 'sent',
                    'message' => $result['message'] ?? 'OK',
                ];
            } else {
                $failed++;
                $details[] = [
                    'id_order' => $idOrder,
                    'id_product' => $idProduct,
                    'status' => 'failed',
                    'message' => 'Mail wysłany, ale nie udało się oznaczyć grupy jako wysłanej w bazie.',
                ];
            }
        } else {
            $failed++;
            $details[] = [
                'id_order' => $idOrder,
                'id_product' => $idProduct,
                'status' => 'failed',
                'message' => $result['message'] ?? 'Mail::Send zwrócił błąd.',
            ];
        }
    } catch (Throwable $e) {
        $failed++;
        $details[] = [
            'id_order' => $idOrder,
            'id_product' => $idProduct,
            'status' => 'failed',
            'message' => $e->getMessage(),
        ];
    }
}

$remaining = (int) Db::getInstance()->getValue('
    SELECT COUNT(*) FROM (
        SELECT a.id_order, a.id_product
        FROM `' . _DB_PREFIX_ . 'bestlab_event_ticket` a
        WHERE a.mail_sent_at IS NULL
        GROUP BY a.id_order, a.id_product
    ) x
');

die(json_encode([
    'ok' => true,
    'message' => 'Cron wykonany.',
    'processed_groups' => $processed,
    'sent' => $sent,
    'failed' => $failed,
    'remaining' => $remaining,
    'details' => $details,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));