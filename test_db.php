<?php
try {
    $p = new PDO('mysql:host=pipaniadvertising.com;port=3306;dbname=pipaniadvertising_db', 'pipaniadvertising_db', 'BCLCEa8kZaTMjrqBbjuq');
    echo 'Success';
} catch (Exception $e) {
    echo $e->getMessage();
}
