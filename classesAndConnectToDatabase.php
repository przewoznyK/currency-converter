<?php

use DatabaseManager as GlobalDatabaseManager;
use SessionManager as GlobalSessionManager;

// Manages session operations
class SessionManager
{
    // Starts new session
    public function start()
    {
        session_start();
    }
    /**
     * Sets the value for the specified session variable.
     * @param string $name - name session variable
     * @param  string|float|bool $value - value for variable
     */
    public function set(string $name, string|float|bool $value)
    {
        $_SESSION[$name] = $value;
    }
    /**
     * Retrieves the value of specific session variable. If this variable doesn't exist use $default value.
     * @param string $name - name session variable
     * @param string|float|bool $default - default value returned if session variable does not exist
     */
    public function get(string $name, string|float|bool $default = false)
    {
        return isset($_SESSION[$name]) ? $_SESSION[$name] : $default;
    }
    // Deletes session variable.
    public function remove(string $name)
    {
        unset($_SESSION[$name]);
    }
}

//Manages API operations, including fetching, decoding, and extracting data.
class ApiManager
{
    private $sessionManager;
    private $databaseManager;
    public function __construct(GlobalSessionManager $sessionManager, GlobalDatabaseManager $databaseManager)
    {
        $this->sessionManager = $sessionManager;
        $this->databaseManager = $databaseManager;
    }

    // Fetches JSON data from the specified API URL. If it fails saves error in the session.
    public function getJsonData($apiUrl)
    {
        $result = @file_get_contents($apiUrl);
        if (empty($result)) {
            $this->sessionManager->set('getTableApiError', true);
        } else {
            return $result;
        }
    }
    // Decodes the provided JSON data.
    public function decodeJsonData($jsonData)
    {
        return json_decode($jsonData);
    }
    /**
     * Extracts specific data from the decoded data.
     * @param string|int $key - the key for the desired data to extract.
     */
    public function extractData($decodedData, string|int $key)
    {
        return $decodedData[0]->$key;
    }
    /**
     * Retrives updated data from the API, clears the corresponding tablie in the database and returns the extracted data.
     *  @param string $tableName - The name of the table in the database
     */

    public function getUpdatedDataFromApi(string $apiNbpUrl, string $tableName, string|int $key)
    {
        // Take data from Api.
        $jsonData = $this->getJsonData($apiNbpUrl);
        // If data was successfully taken.
        if (!$this->sessionManager->get('getTableApiError')) {
            // Clears old data from table.
            $this->databaseManager->clearTable($tableName);
            // If clear table was successfully.
            if (!$this->sessionManager->get('clearTableError')) {
                // Decodes JSON data.
                $decodedData = $this->decodeJsonData($jsonData);
                // Extracts data.
                $dataApiNbpToUse = $this->extractData($decodedData, $key);
                return $dataApiNbpToUse;
            } else {
                // Saves error in session.
                $this->sessionManager->remove('clearTableError');
                return false;
            }
        } else {
            // Saves error in session.
            $this->sessionManager->remove('getTableApiError');
            return false;
        }
    }
}
// Manages database connetctions and operations.
class DatabaseManager
{
    private $host;
    private $db_user;
    private $db_password;
    private $db_name;
    private $conn;
    private $sessionManager;
    public function __construct(string $host, string $db_user, string $db_password, string $db_name, GlobalSessionManager $sessionManager)
    {
        $this->host = $host;
        $this->db_user = $db_user;
        $this->db_password = $db_password;
        $this->db_name = $db_name;
        $this->sessionManager = $sessionManager;
    }
    // Connects to the database.
    public function connect()
    {
        try {
            $this->conn = new mysqli($this->host, $this->db_user, $this->db_password, $this->db_name);
        } catch (Exception $e) {
            die("Exception: " . $e->getMessage());
        }
    }
    // Returns a database connection object.
    public function getConn()
    {
        return $this->conn;
    }
    // Clears table in the database.
    public function clearTable($tableName)
    {
        try {
            $query = "TRUNCATE TABLE $tableName";
            mysqli_query($this->conn, $query);
            $this->sessionManager->remove('clearTableError');
        } catch (Exception $e) {
            echo "Exception: " . $e->getMessage();
            $this->sessionManager->set('clearTableError', true);
        }
    }

    // Performs a SELECT query in the database.
    public function select(string $tableName, string $columnsNames = '*', string $where = '', string $whereWhat = '', string $orderBy = '', string $orderByHow = 'ASC', string $limit = '', string $unionAll = '')
    {
        try {
            $query = "SELECT $columnsNames FROM $tableName";
            if ($where != '') {
                $query .= "  WHERE $where = '$whereWhat'";
            }
            if ($orderBy != '') {
                $query .= " ORDER BY $orderBy $orderByHow";
            }
            if ($limit != '') {
                $query .= " LIMIT $limit";
            }
            if ($unionAll != '') {
                $query .= " UNION ALL SELECT $columnsNames FROM $unionAll";
                if ($where != '') {
                    $query .= "  WHERE $where = '$whereWhat'";
                }
            }
            $result = mysqli_query($this->conn, $query);
            if (!$result) {
                die("Query failed: " . mysqli_error($this->conn));
            }
            $data = array();
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
            return $data;
        } catch (Exception $e) {
            echo "Exception: " . $e->getMessage();
        }
    }
    // Performs query INSERT in the database.
    public function insert(string $tableName, array $dataArray)
    {
        $columns = implode(', ', array_keys($dataArray));
        $values = "'" . implode("', '", array_values($dataArray)) . "'";

        $query = "INSERT INTO $tableName ($columns) VALUES ($values)";
        $result = mysqli_query($this->conn, $query);
        if (!$result) {
            die("Query failed: " . mysqli_error($this->conn));
        }
        return mysqli_insert_id($this->conn);
    }
}
// Manages generate table and create select field.
class DisplayDataManager
{
    private $sessionManager;
    public function __construct(GlobalSessionManager $sessionManager)
    {
        $this->sessionManager = $sessionManager;
    }
    // Generates table HTML based on array.
    public function GenerateTableFromDataArray($array)
    {
        if (!empty($array)) {
            echo '<table>';
            $uniqueKeys = array_unique(array_keys($array[0]));
            echo '<tr>';
            foreach ($uniqueKeys as $key) {
                echo "<th>" . str_replace('_', " ", $key) . "</th>";
            }
            echo '</tr>';
            foreach ($array as $row) {
                echo '<tr>';
                foreach ($uniqueKeys as $value) {
                    echo "<td> $row[$value] </td>";
                }
                echo '</tr>';
            }
            echo '</table>';
        } else echo '<h2>Wygląda na to, że w bazie danych nie ma zapisanych wyników.</h2>';
    }
    /**
     * Creates select field.
     * @param string $name - name select field
     * @param array $array_values - array of values ​​for the select field
     */
    public function CreateSelectField(string $name, array $array_values)
    {
        echo '<select name="' . $name . '">';
        if ($name == 'sourceCurrency' || $name == 'targetCurrency') {
            $lastValue = $name == 'sourceCurrency' ? $this->sessionManager->get('sourceCurrency') : $this->sessionManager->get('targetCurrency');
        } else {
            $lastValue =  $this->sessionManager->get('limitDisplayResult') == '999999' ? 'wszystko' : $this->sessionManager->get('limitDisplayResult', 5);
        }
        $this->SetOptionValueToSelectFieldFromArray($array_values, $lastValue);
        echo '</select>';
    }
    /**
     * Sets option value for select field on base array values.
     * @param array $array_values - array of values ​​for the select field
     * @param string|float $lastValue used for check and select last choose
     */
    public function SetOptionValueToSelectFieldFromArray(array $array_values, string|float $lastValue)
    {
        foreach ($array_values as $value) {
            foreach (array_keys($value) as $key) {
                $selected = $value[$key] == $lastValue ? 'selected' : '';
                echo '<option value="' . $value[$key] . '"' . $selected . '> ' . $value[$key] . ' </option>';
            }
        }
    }
}
// Manages conversion of currency and saves results in the database.
class CurrencyConverter
{
    private $databaseManager;
    private $sessionManager;
    public function __construct(GlobalDatabaseManager $databaseManager, GlobalSessionManager $sessionManager)
    {
        $this->databaseManager = $databaseManager;
        $this->sessionManager = $sessionManager;
    }
    /**
     * Calculates the total value of currency exchange.
     *
     * @param float $sourceAmount The input amount.
     * @param float $source_exchange_rate The exchange rate for the source currency.
     * @param float $target_exchange_rate The exchange rate for the target currency.
     * @param int $decimal The number of decimal places for the result.
     */
    public function CountSummaryExchanges(float $sourceAmount, float $source_exchange_rate, float $target_exchange_rate, int $decimal)
    {
        $summary = $sourceAmount * $source_exchange_rate / $target_exchange_rate;
        $summary = number_format($summary, $decimal);
        return $summary;
    }
    /**
     * Saves the total value of currency exchange to the database.
     *
     * @param string $tableName The name of the table in the database
     * @param float $summaryExchange The total value of currency exchange
     * @param array $insertData The data to insert into the database
     */
    public function SaveSummaryInDatabase(string $tableName, float $summaryExchange, array $insertData)
    {
        $this->sessionManager->set('summary', $summaryExchange);
        $this->databaseManager->insert($tableName, $insertData);
    }
}
$sessionManager = new SessionManager();
$sessionManager->start();
$databaseManager = new DatabaseManager('localhost', 'root', '', 'nbp_database', $sessionManager);
$databaseManager->connect();
$displayDataManager = new DisplayDataManager($sessionManager);
