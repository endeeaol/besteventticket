<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function bestEventTicketUninstallSql(): bool
{
    // Celowo nie usuwamy tabeli przy odinstalowaniu modułu,
    // żeby nie stracić danych biletów i potwierdzeń.
    return true;
}