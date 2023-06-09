<?php
require('classesAndConnectToDatabase.php');
// Creates API Manager object
$apiManager = new ApiManager($sessionManager, $databaseManager);
// Sets API Url
$apiNbpUrl = "http://api.nbp.pl/api/exchangerates/tables/A/today/";
// Checks if updated data from API exists. If exists clears old data and inserts new data.
$dataApiNbpToUse = $apiManager->getUpdatedDataFromApi($apiNbpUrl, 'tabela_a', 'rates');
if ($dataApiNbpToUse) {
    foreach ($dataApiNbpToUse as $element) {
        // Prepares data to insert in database
        $insertData = array(
            'nazwa_waluty' => mysqli_real_escape_string($databaseManager->getConn(), $element->currency),
            'kod_waluty' => mysqli_real_escape_string($databaseManager->getConn(), $element->code),
            'kurs_średni' => mysqli_real_escape_string($databaseManager->getConn(), $element->mid),
        );
        // Inserts data one the database
        $databaseManager->insert('tabela_a', $insertData);
    }
}
// Sets API Url
$apiNbpUrl = "http://api.nbp.pl/api/exchangerates/tables/B/today/";
// Checks if updated data from API exists. If exists clears old data and inserts new data.
$dataApiNbpToUse = $apiManager->getUpdatedDataFromApi($apiNbpUrl, 'tabela_b', 'rates');
if ($dataApiNbpToUse) {
    foreach ($dataApiNbpToUse as $element) {
        // Prepares data to inserts to the database 
        $insertData = array(
            'nazwa_waluty' => mysqli_real_escape_string($databaseManager->getConn(), $element->currency),
            'kod_waluty' => mysqli_real_escape_string($databaseManager->getConn(), $element->code),
            'kurs_średni' => mysqli_real_escape_string($databaseManager->getConn(), $element->mid),
        );
        // Inserts data one the database
        $databaseManager->insert('tabela_b', $insertData);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Przelicznik walut</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <aside>
        <?php
        // Takes all data from tables tabela_a and tabela_b in the database
        $dataFromDatabase = $databaseManager->select('tabela_a', 'nazwa_waluty, kod_waluty, kurs_średni', '', '', '', 'ASC', '', 'tabela_b');
        // Displays this data in table
        $displayDataManager->GenerateTableFromDataArray($dataFromDatabase);
        ?>
    </aside>
    <main>
        <!-- Creates form currency converter -->
        <form action='checkValue.php' method='POST'>
            <h3>Wartość</h3>
            <?php
            // Creates input field source amount and fills it last used value. If last used value doesn't exists use default value
            $lastAmountValue = $sessionManager->get('sourceAmount', 1);
            echo '<input type="number" name="sourceAmount" value="' . $lastAmountValue . '" min="1" step="0.01" required >';
            ?>
            <h3>Waluta źródłowa</h3>
            <?php
            // Takes all currency names from database
            $dataFromDatabase = $databaseManager->select('tabela_a', 'nazwa_waluty', '', '', '', 'ASC', '', 'tabela_b');
            // Creates section field and uses currency names in option value
            $displayDataManager->CreateSelectField('sourceCurrency', $dataFromDatabase);
            ?>
            <h3>Waluta docelowa</h3>
            <?php
            // Creates section field with data from table
            $displayDataManager->CreateSelectField('targetCurrency', $dataFromDatabase);
            ?>
            <!-- Displays summary if exists. If not displays 'Wpisz liczbe'  -->
            <h3>Wynik</h3>
            <?php echo $sessionManager->get('summary', 'Wpisz liczbe') ?>
            <p><input type='submit' value='Sprawdź'></p>
        </form>
        Ostatnio sprawdzane:
        <!-- Form that changes the number of displayed saved results from the database -->
        <form method="POST" action="changeSavedResultLimitDisplay.php">
            <?php
            // Prepares option value for select field
            $arrayWithOptionToSelectField = [
                array('1' => '1'),
                array('2' => '5'),
                array('3' => '10'),
                array('4' => 'wszystko'),
            ];
            $displayDataManager->CreateSelectField('limitDisplayResult', $arrayWithOptionToSelectField);
            ?>
            <input type="submit" value="Zmień">
        </form>
        <?php
        // Sets last selected limit, if limit doesn't exists set limit as 5 
        $limit = $sessionManager->get('limitDisplayResult', 5);
        // Takes saved results data from table
        $dataFromDatabase = $databaseManager->select('zapisane_wyniki', '*', '', '', 'czas_wykonania', 'DESC', $limit);
        // Displays this data to table
        $displayDataManager->GenerateTableFromDataArray($dataFromDatabase);
        ?>
    </main>
</body>

</html>