<?php

error_reporting(0); 
ini_set('display_errors', 0);

ob_start();

session_start();
$rootPath = dirname(__DIR__); 
chdir($rootPath);


header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

include "includes/header.php"; 

ob_end_clean();

$user_id = $_SESSION['user_id'];
$category_id = isset($_POST['category_id']) ? $_POST['category_id'] : '';
$search_term = isset($_POST['search_term']) ? $_POST['search_term'] : '';

$join_type = (!empty($category_id)) ? "JOIN" : "LEFT JOIN";

$sql = "SELECT u.user_id, u.name, u.profile_picture, u.joined_date,
        (SELECT AVG(rating) FROM tr_userreviews WHERE reviewee_id = u.user_id) as avg_rating,
        (
            SELECT COUNT(*) FROM ms_userskills m 
            WHERE m.user_id = u.user_id 
            AND (
                (m.skill_type = 'teach' AND m.skill_name IN (SELECT skill_name FROM ms_userskills WHERE user_id = ? AND skill_type = 'learn'))
                OR 
                (m.skill_type = 'learn' AND m.skill_name IN (SELECT skill_name FROM ms_userskills WHERE user_id = ? AND skill_type = 'teach'))
            )
        ) as match_score
        FROM ms_users u
        $join_type ms_userskills us ON u.user_id = us.user_id
        WHERE u.user_id != ? 
        AND u.role != 'admin'";

$params = [$user_id, $user_id, $user_id];
$types = "iii";

if (!empty($category_id)) {
    $sql .= " AND us.category_id = ?";
    $params[] = intval($category_id);
    $types .= "i";
}

if (!empty($search_term)) {
    $term = "%" . $search_term . "%";
    $sql .= " AND (u.name LIKE ? OR us.skill_name LIKE ?)";
    $params[] = $term;
    $params[] = $term;
    $types .= "ss";
}

$sql .= " GROUP BY u.user_id ORDER BY match_score DESC, avg_rating DESC, u.joined_date DESC LIMIT 8";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$partners = [];

while ($row = $result->fetch_assoc()) {
    $p_id = $row['user_id'];

    $row['avg_rating'] = number_format((float)($row['avg_rating'] ?? 0), 1);
    
    $row['match_score'] = (int)$row['match_score']; 

    $skill_sql = "SELECT us.skill_name, us.skill_type, sc.category_name 
                  FROM ms_userskills us 
                  LEFT JOIN ms_skillcategory sc ON us.category_id = sc.category_id 
                  WHERE us.user_id = ?";
    
    $stmt_skill = $conn->prepare($skill_sql);
    $stmt_skill->bind_param("i", $p_id);
    $stmt_skill->execute();
    $skill_res = $stmt_skill->get_result();

    $row['skills_offered'] = [];
    $row['learning_interests'] = [];

    while ($s_row = $skill_res->fetch_assoc()) {
        $skillData = [
            'name' => $s_row['skill_name'],
            'category' => $s_row['category_name'] ?? 'General'
        ];

        if ($s_row['skill_type'] == 'teach') {
            $row['skills_offered'][] = $skillData;
        } else {
            $row['learning_interests'][] = $skillData;
        }
    }
    
    $partners[] = $row;
}

echo json_encode(['partners' => $partners]);
?>