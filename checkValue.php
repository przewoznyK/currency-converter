<?php
require('classesAndConnectToDatabase.php');
// Gets data from currency converter form.
$sourceAmount = $_POST['sourceAmount'];
$sourceCurrency = $_POST['sourceCurrency'];
$targetCurrency = $_POST['targetCurrency'];
// Saves last choose.
$sessionManager->set('sourceAmount', $sourceAmount);
$sessionManager->set('sourceCurrency', $sourceCurrency);
$sessionManager->set('targetCurrency', $targetCurrency);
// Creates CurrencyConverter object.
$currencyConverter = new CurrencyConverter($databaseManager, $sessionManager);
// Takes exchange rate value from the database.
$dataFromDatabase = $databaseManager->select('tabela_a', 'kurs_średni', 'nazwa_waluty', "$sourceCurrency", '', 'ASC', '', 'tabela_b');
// Sets exchange rate value to variable.
$source_exchange_rate = $dataFromDatabase[0]['kurs_średni'];
// Take exchange rate value from the database.
$dataFromDatabase = $databaseManager->select('tabela_a', 'kurs_średni', 'nazwa_waluty', "$targetCurrency", '', 'ASC', '', 'tabela_b');
// Sets exchange rate value to variable.
$target_exchange_rate = $dataFromDatabase[0]['kurs_średni'];
// Counts result.
$summaryExchange = $currencyConverter->CountSummaryExchanges($sourceAmount, $source_exchange_rate, $target_exchange_rate, 3);
// Prepares data to inserts in the database.
$insertData = [
    'wartość_początkowa' => $sourceAmount,
    'waluta_źródłowa' => $sourceCurrency,
    'waluta_docelowa' => $targetCurrency,
    'wynik' => $summaryExchange,
    'czas_wykonania' => date('Y-m-d H:i:s'),
];
// Inserts data in the database.
$currencyConverter->SaveSummaryInDatabase('zapisane_wyniki', $summaryExchange, $insertData);

header("Location: index.php");
