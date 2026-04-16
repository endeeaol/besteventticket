<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function bestEventTicketInstallSql(): bool
{
    $sql = [];

    $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'bestlab_event_ticket` (
        `id_bestlab_event_ticket` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `source_ticket_id` INT UNSIGNED DEFAULT NULL,
        `source_cart_id` INT UNSIGNED DEFAULT NULL,
        `ticket_ref` VARCHAR(64) NOT NULL,
        `id_order` INT UNSIGNED DEFAULT NULL,
        `id_customer` INT UNSIGNED DEFAULT NULL,
        `customer_email` VARCHAR(255) DEFAULT NULL,
        `customer_phone` VARCHAR(64) DEFAULT NULL,
        `customer_firstname` VARCHAR(255) DEFAULT NULL,
        `customer_lastname` VARCHAR(255) DEFAULT NULL,
        `id_product` INT UNSIGNED NOT NULL,
        `event_key` VARCHAR(32) NOT NULL,
        `event_name` VARCHAR(255) NOT NULL,
        `ticket_position` TINYINT UNSIGNED NOT NULL,
        `qty_in_order` TINYINT UNSIGNED NOT NULL,
        `guest_name` VARCHAR(255) DEFAULT NULL,
        `confirmation` TINYINT(1) DEFAULT NULL,
        `confirmation_token` VARCHAR(128) DEFAULT NULL,
        `mail_sent_at` DATETIME DEFAULT NULL,
        `confirmed_at` DATETIME DEFAULT NULL,
        `date_order` DATETIME DEFAULT NULL,
        `date_add` DATETIME NOT NULL,
        `date_upd` DATETIME NOT NULL,
        PRIMARY KEY (`id_bestlab_event_ticket`),
        UNIQUE KEY `uniq_ticket_ref` (`ticket_ref`),
        KEY `idx_source_cart_id` (`source_cart_id`),
        KEY `idx_id_order` (`id_order`),
        KEY `idx_customer_email` (`customer_email`),
        KEY `idx_id_product` (`id_product`),
        KEY `idx_event_key` (`event_key`),
        KEY `idx_confirmation` (`confirmation`),
        KEY `idx_confirmation_token` (`confirmation_token`)
    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';

    foreach ($sql as $query) {
        if (!Db::getInstance()->execute($query)) {
            return false;
        }
    }

    return true;
}