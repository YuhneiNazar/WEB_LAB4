<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Display Tables</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<?php
require_once 'Database.php';

$database = new Database();

$tables = [
    'kliyenty' => 'Клієнти',
    'pratsivnyky' => 'Працівники',
    'posluhy' => 'Послуги',
    'cars' => 'Автомобілі',
    'zamovlennya' => 'Замовлення',
];
?>
<form action="" method="post">
    <label for="searchTable">Оберіть таблицю для пошуку:</label>
    <select name="searchTable" id="searchTable">
        <?php foreach ($tables as $table => $displayName) {
            echo "<option value=\"$table\">$displayName</option>";
        } ?>
    </select>
    <label for="searchColumn">Оберіть стовпець для пошуку:</label>
    <input type="text" name="searchColumn" id="searchColumn">
    <label for="searchValue">Введіть значення для пошуку:</label>
    <input type="text" name="searchValue" id="searchValue">
    <input type="submit" value="Шукати">
</form>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['searchTable'])) {
    $searchTable = $_POST['searchTable'];
    $searchColumn = $_POST['searchColumn'];
    $searchValue = $_POST['searchValue'];

    $searchResults = $database->getDataWithCriteria($searchTable, $searchColumn, $searchValue);

    echo "<h2>Результати пошуку для {$tables[$searchTable]}</h2>";
    echo '<table border="1">';
    echo '<tr>';
    foreach (array_keys($searchResults[0]) as $column) {
        echo "<th>$column</th>";
    }
    echo '</tr>';
    foreach ($searchResults as $row) {
        echo '<tr>';
        foreach ($row as $value) {
            echo "<td>$value</td>";
        }
        echo '</tr>';
    }
    echo '</table>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['insertTable'])) {
    $insertTable = $_POST['insertTable'];

    $columns = $database->getData("INFORMATION_SCHEMA.COLUMNS", [
        'TABLE_NAME' => $insertTable,
        'TABLE_SCHEMA' => 'sto'
    ]);

    $insertData = [];
    $placeholders = [];
    $values = [];

    foreach ($columns as $column) {
        $columnName = $column['COLUMN_NAME'];

        if ($column['EXTRA'] === 'auto_increment') {
            continue;
        }

        $userInput = $_POST[$columnName];
        $sanitizedInput = $database->sanitizeInput($userInput);

        $insertData[$columnName] = $sanitizedInput;
        $placeholders[] = '?';
        $values[] = $sanitizedInput;
    }

    $database->insertData($insertTable, $insertData);

    $tableData = $database->getData($insertTable, []);
    
    echo "<h2>{$tables[$insertTable]}</h2>";
    echo '<table border="1">';
    echo '<tr>';
    foreach (array_keys($tableData[0]) as $column) {
        echo "<th>$column</th>";
    }
    echo '</tr>';
    foreach ($tableData as $row) {
        echo '<tr>';
        foreach ($row as $value) {
            echo "<td>$value</td>";
        }
        echo '</tr>';
    }
    echo '</table>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateTable']) && isset($_POST['updateId'])) {
    $updateTable = $_POST['updateTable'];
    $updateId = $_POST['updateId'];

    $updateData = [];
    $columns = $database->getData("INFORMATION_SCHEMA.COLUMNS", [
        'TABLE_NAME' => $updateTable,
        'TABLE_SCHEMA' => 'sto' 
    ]);

    foreach ($columns as $column) {
        if ($column['EXTRA'] === 'auto_increment') {
            continue;
        }
        $userInput = $_POST[$column['COLUMN_NAME']];
        $sanitizedInput = $database->sanitizeInput($userInput);

        $updateData[$column['COLUMN_NAME']] = $sanitizedInput;
    }

    $database->updateData($updateTable, $updateData, ['id' => $updateId]);
    header("Location: ?table={$updateTable}&updated=true");
    exit();
}

if ($_GET['action'] === 'delete' && isset($_GET['table']) && isset($_GET['id'])) {
    $deleteTable = $_GET['table'];
    $deleteId = $_GET['id'];

    $database->deleteData($deleteTable, ['id' => $deleteId]);

    header("Location: ?table={$deleteTable}&deleted=true");
    exit();
}


echo '<form action="" method="post">';
echo '<label for="table">Оберіть таблицю:</label>';
echo '<select name="table" id="table">';
foreach ($tables as $table => $displayName) {
    echo "<option value=\"$table\">$displayName</option>";
}
echo '</select>';
echo '<input type="submit" value="Відобразити таблицю">';
echo '</form>';

if (isset($_POST['table'])) {
    $selectedTable = $_POST['table'];

    echo "<h2>{$tables[$selectedTable]}</h2>";

    $tableData = $database->getData($selectedTable, []);

    echo '<table border="1">';
    echo '<tr>';
    foreach (array_keys($tableData[0]) as $column) {
        echo "<th>$column</th>";
    }
    echo '<th>Action</th>'; 
    echo '</tr>';
    foreach ($tableData as $row) {
        echo '<tr>';
        foreach ($row as $key => $value) {
            echo "<td>$value</td>";
        }
        echo '<td><a href="?action=update&table=' . $selectedTable . '&id=' . $row['id'] . '">Update</a></td>';
        echo '<td><a href="?action=delete&table=' . $selectedTable . '&id=' . $row['id'] . '">Delete</a></td>';
        echo '</tr>';
    }
    echo '</table>';

    echo "<h2>Вставити дані в {$tables[$selectedTable]}</h2>";
    echo '<form action="" method="post">';
    echo '<input type="hidden" name="insertTable" value="' . $selectedTable . '">';

    $columns = $database->getData("INFORMATION_SCHEMA.COLUMNS", [
        'TABLE_NAME' => $selectedTable,
        'TABLE_SCHEMA' => 'sto' 
    ]);

    foreach ($columns as $column) {
        if ($column['EXTRA'] === 'auto_increment') {
            continue;
        }

        echo '<label for="' . $column['COLUMN_NAME'] . '">' . $column['COLUMN_NAME'] . ':</label>';
        echo '<input type="text" name="' . $column['COLUMN_NAME'] . '" id="' . $column['COLUMN_NAME'] . '" required>';
        echo '<br>';
    }

    echo '<input type="submit" value="Вставити дані">';
    echo '</form>';
}

if ($_GET['action'] === 'update' && isset($_GET['table']) && isset($_GET['id'])) {
    $updateTable = $_GET['table'];
    $updateId = $_GET['id'];

    echo "<h2>Оновити дані в {$tables[$updateTable]}</h2>";
    echo '<form action="" method="post">';
    echo '<input type="hidden" name="updateTable" value="' . $updateTable . '">';
    echo '<input type="hidden" name="updateId" value="' . $updateId . '">';

    $columns = $database->getData("INFORMATION_SCHEMA.COLUMNS", [
        'TABLE_NAME' => $updateTable,
        'TABLE_SCHEMA' => 'sto'
    ]);

    $existingData = $database->getData($updateTable, ['id' => $updateId]);
    $existingData = $existingData[0];

    foreach ($columns as $column) {
        if ($column['EXTRA'] === 'auto_increment') {
            continue;
        }

        echo '<label for="' . $column['COLUMN_NAME'] . '">' . $column['COLUMN_NAME'] . ':</label>';
        echo '<input type="text" name="' . $column['COLUMN_NAME'] . '" id="' . $column['COLUMN_NAME'] . '" value="' . $existingData[$column['COLUMN_NAME']] . '" required>';
        echo '<br>';
    }

    echo '<input type="submit" value="Оновити дані">';
    echo '</form>';
}

if (isset($_POST['table']) && $_POST['table'] === 'zamovlennya') {
    $serviceCostPerCar = $database->calculateServiceCostPerCar();

    echo "<h2>Загальна вартість обслуговування за автомобіль</h2>";
    echo '<table border="1">';
    echo '<tr><th>Car ID</th><th>Car Model</th><th>Total Service Cost</th></tr>';

    foreach ($serviceCostPerCar as $row) {
        echo '<tr>';
        echo "<td>{$row['car_id']}</td>";
        echo "<td>{$row['car_model']}</td>";
        echo "<td>{$row['total_service_cost']}</td>";
        echo '</tr>';
    }

    echo '</table>';
}
?>
</body>
</html>