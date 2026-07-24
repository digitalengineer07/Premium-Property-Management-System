<?php
require_once "db.php";
$schema = ['tables' => []];
$res = mysqli_query($conn, 'SHOW TABLES');
while ($r = mysqli_fetch_array($res)) {
    $t = $r[0];
    $schema['tables'][$t] = [
        'columns' => [],
        'primary_key' => null,
        'create_statement' => mysqli_fetch_array(mysqli_query($conn, "SHOW CREATE TABLE `$t`"))[1]
    ];
    $cols = mysqli_query($conn, "SHOW COLUMNS FROM `$t`");
    while ($c = mysqli_fetch_assoc($cols)) {
        $def = $c['Type'];
        if ($c['Null'] === 'NO') $def .= ' NOT NULL';
        else $def .= ' DEFAULT NULL';
        if ($c['Default'] !== null) {
            if (strtoupper($c['Default']) === 'CURRENT_TIMESTAMP') $def .= ' DEFAULT CURRENT_TIMESTAMP';
            else $def .= " DEFAULT '" . mysqli_real_escape_string($conn, $c['Default']) . "'";
        }
        if ($c['Extra']) $def .= ' ' . strtoupper($c['Extra']);
        $schema['tables'][$t]['columns'][$c['Field']] = $def;
        if ($c['Key'] === 'PRI') $schema['tables'][$t]['primary_key'] = $c['Field'];
    }
}
file_put_contents('admin/schema.json', json_encode($schema, JSON_PRETTY_PRINT));
echo 'Done';
