<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

require_once '../db.php';

function getUsersData($page = 1, $limit = 20)
{
    try {
        $offset = ($page - 1) * $limit;

        $limit = intval($limit);
        $offset = intval($offset);

        $totalResult = DB::fetch('SELECT COUNT(*) as total FROM users', []);
        $total = $totalResult['total'];

        $query = "SELECT id, username, mail, uuid, admin FROM users ORDER BY id DESC LIMIT $limit OFFSET $offset";
        $result = DB::fetch($query, [], true);

        if ($result) {
            $totalPages = ceil($total / $limit);
            return [
                'status' => 'success',
                'data' => $result,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_users' => $total,
                    'users_per_page' => $limit
                ]
            ];
        } else {
            return ['status' => 'error', 'message' => 'Aucun utilisateur trouvé'];
        }
    } catch (PDOException $e) {
        return ['status' => 'error', 'message' => 'Erreur de base de données : ' . $e->getMessage()];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = 20;

    echo json_encode(getUsersData($page, $limit));
} else {
    echo json_encode(['status' => 'error', 'message' => 'Méthode non autorisée']);
}
