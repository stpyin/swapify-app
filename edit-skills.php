<?php
session_start(); 

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include "includes/header.php";

$categories = [];
$cat_query = "SELECT category_id, category_name FROM ms_skillcategory ORDER BY category_name ASC";
$cat_result = $conn->query($cat_query);
while ($cat = $cat_result->fetch_assoc()) {
    $categories[] = $cat;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    
    $teach_names = $_POST['teach_skill_name'] ?? [];
    $teach_cats  = $_POST['teach_skill_category'] ?? [];
    $learn_names = $_POST['learn_skill_name'] ?? [];
    $learn_cats  = $_POST['learn_skill_category'] ?? [];

    $conn->begin_transaction();

    try {
        $delete_query = "DELETE FROM ms_userskills WHERE user_id = ?";
        $stmt_delete = $conn->prepare($delete_query);
        $stmt_delete->bind_param("i", $user_id);
        $stmt_delete->execute();

        if (!empty($teach_names)) {
            $insert_sql = "INSERT INTO ms_userskills (user_id, skill_name, category_id, skill_type) VALUES (?, ?, ?, 'teach')";
            $stmt_t = $conn->prepare($insert_sql);
            
            for ($i = 0; $i < count($teach_names); $i++) {
                $s_name = htmlspecialchars($teach_names[$i]);
                $s_cat  = intval($teach_cats[$i]);
                if (!empty($s_name) && !empty($s_cat)) {
                    $stmt_t->bind_param("isi", $user_id, $s_name, $s_cat);
                    $stmt_t->execute();
                }
            }
        }

        if (!empty($learn_names)) {
            $insert_sql = "INSERT INTO ms_userskills (user_id, skill_name, category_id, skill_type) VALUES (?, ?, ?, 'learn')";
            $stmt_l = $conn->prepare($insert_sql);
            
            for ($i = 0; $i < count($learn_names); $i++) {
                $s_name = htmlspecialchars($learn_names[$i]);
                $s_cat  = intval($learn_cats[$i]);
                if (!empty($s_name) && !empty($s_cat)) {
                    $stmt_l->bind_param("isi", $user_id, $s_name, $s_cat);
                    $stmt_l->execute();
                }
            }
        }

        $conn->commit();
        header("Location: dashboard.php");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $error_msg = "Error saving skills: " . $e->getMessage();
    }
}


$user_id = $_SESSION['user_id'];
$current_teach = [];
$current_learn = [];

$query = "SELECT us.skill_name, us.skill_type, us.category_id, sc.category_name 
          FROM ms_userskills us
          LEFT JOIN ms_skillcategory sc ON us.category_id = sc.category_id
          WHERE us.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    if ($row['skill_type'] == 'teach') {
        $current_teach[] = $row;
    } else {
        $current_learn[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swapify - Edit Skills</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/edit-skills.css">
    <style>
        .remove-tag { cursor: pointer; margin-left: 8px; font-weight: bold; color: #ff6b6b; }
        .remove-tag:hover { color: #ff0000; }
        
        .input-group {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        .input-group input {
            flex: 2;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .input-group select {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: white;
        }
        .btn-add {
            padding: 10px 20px;
            background-color: #4A90E2;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        .btn-add:hover { background-color: #357ABD; }

        .tag {
            display: inline-flex;
            align-items: center;
            background: #f0f4f8;
            border: 1px solid #d1e3f8;
            padding: 6px 12px;
            border-radius: 20px;
            margin-right: 8px;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .tag small {
            color: #888;
            margin-left: 5px;
            font-size: 0.8em;
            background: rgba(255,255,255,0.5);
            padding: 2px 5px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="edit-skills-container">
        <div class="header-back">
            <a href="dashboard.php" class="back-link" style="text-decoration: none; color: #4A90E2;">← Back to Dashboard</a>
            <h2 style="margin-top: 10px;">Personalize Your Skills</h2>
            <p>Tell us what you're an expert in and what you're excited to learn.</p>
        </div>

        <?php if (isset($error_msg)): ?>
            <div class="error-banner"><?= $error_msg ?></div>
        <?php endif; ?>

        <form action="edit-skills.php" method="POST" id="skillsForm">
            
            <div class="skill-card-edit">
                <div class="card-icon">🎓</div>
                <div class="card-content">
                    <h3>Skills You Can Teach</h3>
                    <p>Enter a skill name and select its category.</p>
                    
                    <div class="input-group">
                        <input type="text" id="input-teach-name" placeholder="E.g. Photography, Algebra...">
                        <select id="select-teach-cat">
                            <option value="">Select Category...</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn-add" onclick="addItem('teach')">Add</button>
                    </div>
                    
                    <div class="selected-tags" id="teach-container">
                        <?php foreach ($current_teach as $skill): ?>
                            <div class="tag teach">
                                <span><?= htmlspecialchars($skill['skill_name']) ?></span>
                                <small><?= htmlspecialchars($skill['category_name']) ?></small>
                                <span class="remove-tag" onclick="removeTag(this)">&times;</span>
                                
                                <input type="hidden" name="teach_skill_name[]" value="<?= htmlspecialchars($skill['skill_name']) ?>">
                                <input type="hidden" name="teach_skill_category[]" value="<?= $skill['category_id'] ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="skill-card-edit">
                <div class="card-icon">📚</div>
                <div class="card-content">
                    <h3>Skills You Want to Learn</h3>
                    <p>Enter a skill name and select its category.</p>
                    
                    <div class="input-group">
                        <input type="text" id="input-learn-name" placeholder="E.g. Guitar, Python...">
                        <select id="select-learn-cat">
                            <option value="">Select Category...</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn-add" onclick="addItem('learn')">Add</button>
                    </div>
                    
                    <div class="selected-tags" id="learn-container">
                        <?php foreach ($current_learn as $skill): ?>
                            <div class="tag learn">
                                <span><?= htmlspecialchars($skill['skill_name']) ?></span>
                                <small><?= htmlspecialchars($skill['category_name']) ?></small>
                                <span class="remove-tag" onclick="removeTag(this)">&times;</span>
                                
                                <input type="hidden" name="learn_skill_name[]" value="<?= htmlspecialchars($skill['skill_name']) ?>">
                                <input type="hidden" name="learn_skill_category[]" value="<?= $skill['category_id'] ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="window.location.href='dashboard.php'">Cancel</button>
                <button type="submit" class="btn-save">Save Changes</button>
            </div>
        </form>
    </div>

    <script>
        const MAX_SKILLS = 2;

        function addItem(type) {
            const container = document.getElementById(`${type}-container`);
            const currentCount = container.getElementsByClassName('tag').length;

            if (currentCount >= MAX_SKILLS) {
                alert(`You can only add a maximum of ${MAX_SKILLS} skills to ${type}.`);
                return;
            }

            const nameInput = document.getElementById(`input-${type}-name`);
            const catSelect = document.getElementById(`select-${type}-cat`);
            
            const skillName = nameInput.value.trim();
            const catId = catSelect.value;
            const catName = catSelect.options[catSelect.selectedIndex].text;

            if (skillName === '' || catId === '') {
                alert("Please enter a skill name AND select a category.");
                return;
            }

            const existingInputs = container.querySelectorAll(`input[value="${skillName}"]`);
            if (existingInputs.length > 0) {
                alert("You have already added this skill.");
                nameInput.value = '';
                return;
            }

            const tagDiv = document.createElement('div');
            tagDiv.className = `tag ${type}`;
            tagDiv.innerHTML = `
                <span>${escapeHtml(skillName)}</span>
                <small>${escapeHtml(catName)}</small>
                <span class="remove-tag" onclick="removeTag(this)">&times;</span>
                
                <input type="hidden" name="${type}_skill_name[]" value="${escapeHtml(skillName)}">
                <input type="hidden" name="${type}_skill_category[]" value="${catId}">
            `;

            container.appendChild(tagDiv);
            nameInput.value = '';
            catSelect.value = '';
            nameInput.focus();
            
            checkLimit(type);
        }

        function removeTag(element) {
            const parent = element.parentElement;
            const type = parent.classList.contains('teach') ? 'teach' : 'learn';
            
            parent.remove();
            
            checkLimit(type);
        }

        function checkLimit(type) {
            const container = document.getElementById(`${type}-container`);
            const count = container.getElementsByClassName('tag').length;
            const btn = document.querySelector(`button[onclick="addItem('${type}')"]`);
            const input = document.getElementById(`input-${type}-name`);
            const select = document.getElementById(`select-${type}-cat`);

            if (count >= MAX_SKILLS) {
                btn.disabled = true;
                btn.style.backgroundColor = "#ccc";
                btn.innerText = "Full";
                input.disabled = true;
                select.disabled = true;
                input.placeholder = "Max 2 skills reached";
            } else {
                btn.disabled = false;
                btn.style.backgroundColor = "#4A90E2";
                btn.innerText = "Add";
                input.disabled = false;
                select.disabled = false;
                input.placeholder = type === 'teach' ? "E.g. Photography..." : "E.g. Guitar...";
            }
        }

        function escapeHtml(text) {
            if (!text) return text;
            return text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        document.getElementById('input-teach-name').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); addItem('teach'); }
        });
        document.getElementById('input-learn-name').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); addItem('learn'); }
        });

    
        document.addEventListener("DOMContentLoaded", function() {
            checkLimit('teach');
            checkLimit('learn');
        });
    </script>
</body>
</html>