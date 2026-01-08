<?php
function ConnexionBDD(){
try{
    $host = 'mysql:host=localhost;dbname=edl';
    $login = 'root';
    $password = '';
    $pdo = new PDO($host, $login, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    return $pdo;
} catch (Exception $e){
    echo "il y a eu une erreur de connexion avec la basede de données";
    die($e->getMessage());
}
}