<?php
class DB {
    public static function fetch(string $request, array $data, bool $all = false) {
        # WITH DOCKER
        # $pdo = new PDO('mysql:host=mysql;dbname=webchat', "root", "root");
        
        # WITH LOCAL MYSQL SERVER
        $pdo = new PDO('mysql:host=;dbname=', "", "" , [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        $stmt = $pdo->prepare($request);
        $success = $stmt->execute($data);
        
        if (strpos(strtoupper($request), 'SELECT') === 0) {
            return $all ? $stmt->fetchAll(PDO::FETCH_ASSOC) : $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            return $success;
        }
    }
}