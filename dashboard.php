<?php
include "includes/header.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$firstName = explode(' ', ($userData['name'] ?? 'User'))[0];

$teach_skills = [];
$teach_skills_with_id = [];
$learn_skills = [];
$skills_query = "SELECT skill_id, skill_name, skill_type FROM ms_userskills WHERE user_id = ?";
$stmt_skills = $conn->prepare($skills_query);
$stmt_skills->bind_param("i", $user_id);
$stmt_skills->execute();
$skills_result = $stmt_skills->get_result();

while ($row = $skills_result->fetch_assoc()) {
    if ($row['skill_type'] == 'teach') {
        $teach_skills[] = $row['skill_name'];
        $teach_skills_with_id[] = ['skill_id' => $row['skill_id'], 'skill_name' => $row['skill_name']];
    } else {
        $learn_skills[] = $row['skill_name'];
    }
}

$upcoming_query = "SELECT 
    s.scheduled_datetime, 
    sk.skill_name, 
    u.name as partner_name
FROM tr_swapsession s
JOIN tr_swapagreement a ON s.agreement_id = a.agreement_id
JOIN ms_userskills sk ON s.skill_id = sk.skill_id
JOIN ms_users u ON (CASE WHEN a.user_a_id = ? THEN a.user_b_id ELSE a.user_a_id END = u.user_id)
WHERE (a.user_a_id = ? OR a.user_b_id = ?) 
AND s.status = 'scheduled'
ORDER BY s.scheduled_datetime ASC LIMIT 1";

$stmt_up = $conn->prepare($upcoming_query);
$stmt_up->bind_param("iii", $user_id, $user_id, $user_id);
$stmt_up->execute();
$upcoming_session = $stmt_up->get_result()->fetch_assoc();

$partners = [];
$partners_query = "
    SELECT 
        u.user_id, 
        u.name, 
        u.profile_picture,
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
    WHERE u.user_id != ? 
    AND u.role != 'admin'
    ORDER BY match_score DESC, avg_rating DESC, u.joined_date DESC
    LIMIT 8
"; 

$stmt_partners = $conn->prepare($partners_query);
$stmt_partners->bind_param("iii", $user_id, $user_id, $user_id);
$stmt_partners->execute();
$partners_result = $stmt_partners->get_result();

while ($row = $partners_result->fetch_assoc()) {
    $p_id = $row['user_id']; 
    
    $p_skills_query = "SELECT us.skill_name, us.skill_type, sc.category_name 
                       FROM ms_userskills us 
                       LEFT JOIN ms_skillcategory sc ON us.category_id = sc.category_id 
                       WHERE us.user_id = ?";
                       
    $stmt_p_skills = $conn->prepare($p_skills_query);
    $stmt_p_skills->bind_param("i", $p_id);
    $stmt_p_skills->execute();
    $p_skills_res = $stmt_p_skills->get_result();
    
    $row['skills_offered'] = [];
    $row['learning_interests'] = [];
    
    while ($s_row = $p_skills_res->fetch_assoc()) {
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

$cat_query = "SELECT * FROM ms_skillcategory";
$categories_result = $conn->query($cat_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swapify - Dashboard</title>
    <link rel="stylesheet" href="css/dashboard.css?v=10">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        
    </style>
</head>
<body>
    <div class="hero-section">
        <div class="hero-content">
            <div class="welcome-text">
                <h1>Welcome back, <?= htmlspecialchars($firstName) ?></h1>
                <p>Connect with others to learn and share your expertise</p>
                <div class="welcome-underline"></div>
            </div>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'user'): ?>
                 <a href="verification.php">
                <button class="apply-mentor-btn">
                    Apply Expert Now
                    <span>→</span>
                </button>
            </a>
            <?php endif; ?>
           
        </div>
    </div>

    <div class="main-content">
        <div class="atas">
            <div class="skill-preferences">
                <div>
                    <div class="preferences-header">
                        <div class="preferences-title">Your Skill Preferences</div>
                    </div>
                    <div class="skill-section">
                        <h3>🎓 Your Skills to Teach</h3>
                        <div class="skill-tags">
                            <?php if (!empty($teach_skills)): ?>
                                <?php foreach ($teach_skills as $skill): ?>
                                    <div class="skill-tag teach"><?= htmlspecialchars($skill) ?></div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="empty-msg">No skills added yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="preferences-header">
                        <a href="edit-skills.php">
                            <div class="edit-link">✏️ Add/Edit Skills</div>
                        </a>
                    </div>
                    <div class="skill-section">
                        <h3>📚 Skills You Want to Learn</h3>
                        <div class="skill-tags">
                            <?php if (!empty($learn_skills)): ?>
                                <?php foreach ($learn_skills as $skill): ?>
                                    <div class="skill-tag learn"><?= htmlspecialchars($skill) ?></div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="empty-msg">No skills added yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="upcoming-sessions">
                <h3>Upcoming Swap Sessions</h3>
                <?php if ($upcoming_session): 
                    $timestamp = strtotime($upcoming_session['scheduled_datetime']);
                ?>
                    <div class="session-item">
                        <div class="session-date">
                            <div class="session-day"><?= date('d', $timestamp) ?></div>
                            <div class="session-month"><?= strtoupper(date('M', $timestamp)) ?></div>
                        </div>
                        <div class="session-details">
                            <div class="session-info">
                                <h4><?= htmlspecialchars($upcoming_session['skill_name']) ?></h4>
                                <p>with <?= htmlspecialchars($upcoming_session['partner_name']) ?></p>
                                <div class="session-time">
                                    <?= date('H:i', $timestamp) ?>
                                </div>
                            </div>
                            <a href="sessions.php">
                                <div class="view-link">View</div>
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="session-item" style="justify-content: center; color: #888; padding-top: 15px;">
                        <p>No upcoming sessions scheduled.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="search-section">
            <div class="search-bar">
                <span class="search-icon">🔍</span>
                <input type="text" id="skillSearch" placeholder="Search for a skill or user...">
            </div>
            
            <select class="filter-select" id="categoryFilter">
                <option value="">All Categories</option>
                
                <?php while($cat = $categories_result->fetch_assoc()): ?>
                    <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <h2 class="section-title">Explore Partners</h2>
        <div class="partners-grid">
            <?php if (!empty($partners)): ?>
                <?php foreach ($partners as $partner): ?>
                    <div class="partner-card">
                        <div class="partner-header">
                            <div class="partner-avatar">
                                <img src="uploads/<?= !empty($partner['profile_picture']) ? htmlspecialchars($partner['profile_picture']) : 'default.png' ?>" 
                                    onerror="this.onerror=null; this.src='uploads/default.png';"
                                    alt="Avatar" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">
                            </div>
                            <div class="partner-info">
                                <h3><?= htmlspecialchars($partner['name']) ?></h3>
                                <div class="partner-rating">
                                    <span class="star">★</span>
                                    <span><?= number_format($partner['avg_rating'] ?? 0, 1) ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="skills-section">
                            <h4>Skills Offered</h4>
                            <div class="skill-badges-container">
                                <?php if (!empty($partner['skills_offered'])): ?>
                                    <?php foreach (array_slice($partner['skills_offered'], 0, 2) as $s): ?>
                                        <div class="skill-badge" title="<?= htmlspecialchars($s['name']) ?> (<?= htmlspecialchars($s['category']) ?>)">
                                            <span><?= htmlspecialchars($s['name']) ?></span>
                                            <span class="cat-label"><?= htmlspecialchars($s['category']) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span style="font-size: 11px; color: #9CA3AF;">No skills listed</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="skills-section">
                            <h4>Learning Interests</h4>
                            <div class="skill-badges-container">
                                <?php if (!empty($partner['learning_interests'])): ?>
                                    <?php foreach (array_slice($partner['learning_interests'], 0, 1) as $s): ?>
                                        <div class="interest-badge" title="<?= htmlspecialchars($s['name']) ?> (<?= htmlspecialchars($s['category']) ?>)">
                                            <span><?= htmlspecialchars($s['name']) ?></span>
                                            <span class="cat-label"><?= htmlspecialchars($s['category']) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span style="font-size: 11px; color: #9CA3AF;">Open to anything</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php 
                            $simpleOffered = array_column($partner['skills_offered'], 'name');
                            $simpleLearn   = array_column($partner['learning_interests'], 'name');
                        ?>

                        <button type="button" class="swap-btn" onclick="openSwapPopup(
                            <?= $partner['user_id'] ?>,
                            '<?= htmlspecialchars($partner['name'], ENT_QUOTES) ?>',
                            '<?= htmlspecialchars($partner['profile_picture'] ?? 'default.png', ENT_QUOTES) ?>',
                            '<?= number_format($partner['avg_rating'] ?? 0, 1) ?>',
                            <?= htmlspecialchars(json_encode($simpleOffered), ENT_QUOTES) ?>,
                            <?= htmlspecialchars(json_encode($simpleLearn), ENT_QUOTES) ?>
                        )">Swap Now</button>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-results">
                    <p>No partners found at the moment.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="footer-tagline">
            Knowledge <em>grows</em> when it's <em>shared</em>.
        </div>
    </div>

    <!-- Swap Request Popup -->
    <div class="swap-popup-overlay" id="swapPopup">
        <div class="swap-popup-content">
            <button class="swap-popup-close" onclick="closeSwapPopup()">×</button>
            
            <div class="swap-popup-header">
                <h2>Request Skill Swap</h2>
                <p>Choose skills to exchange and introduce yourself</p>
            </div>

            <div class="swap-partner-info">
                <div class="swap-partner-avatar">
                    <img src="uploads/default.png" alt="Partner" id="swapPartnerAvatar">
                </div>
                <div class="swap-partner-details">
                    <h3 id="swapPartnerName">Partner Name</h3>
                    <div class="swap-partner-rating">
                        <span>★</span>
                        <span id="swapPartnerRating">5.0</span>
                    </div>
                </div>
            </div>

            <div class="swap-info-box">
                💡 Select the skill you want to teach and what you'd like to learn from your partner
            </div>

            <form id="swapRequestForm" action="process-swap-request.php" method="POST">
                <input type="hidden" name="target_user_id" id="targetUserId" value="">
                
                <div class="swap-skills-section">
                    <label for="skillToTeach">🎓 Your Skill to Teach</label>
                    <select class="swap-skills-dropdown" id="skillToTeach" name="skill_to_teach">
                        <option value="">Select a skill you can teach...</option>
                    </select>
                </div>

                <div class="swap-skills-section">
                    <label for="skillToLearn">📚 Skill You Want to Learn</label>
                    <select class="swap-skills-dropdown" id="skillToLearn" name="skill_to_learn">
                        <option value="">Select a skill to learn...</option>
                    </select>
                </div>

                <div class="swap-message-section">
                    <label for="swapMessage">✉️ Introduction Message</label>
                    <textarea 
                        class="swap-message-textarea" 
                        id="swapMessage" 
                        name="message" 
                        placeholder="Introduce yourself and explain why you'd like to swap skills with this person..."
                        maxlength="500"
                        required
                    ></textarea>
                    <div class="char-count">
                        <span id="charCount">0</span>/500 characters
                    </div>
                </div>

                <div class="swap-popup-actions">
                    <button type="button" class="swap-btn-cancel" onclick="closeSwapPopup()">Cancel</button>
                    <button type="submit" class="swap-btn-send" id="sendBtn">Send Request</button>
                </div>
            </form>
        </div>
    </div>
<script>

let currentUserTeachSkills = <?= json_encode($teach_skills_with_id) ?>;

function openSwapPopup(partnerId, partnerName, partnerAvatar, partnerRating, partnerTeachSkills, partnerLearnSkills) {
    document.getElementById('targetUserId').value = partnerId;
    document.getElementById('swapPartnerName').textContent = partnerName;
    document.getElementById('swapPartnerAvatar').src = 'uploads/' + (partnerAvatar || 'default.png');
    document.getElementById('swapPartnerRating').textContent = partnerRating || '0.0';
    
    const teachDropdown = document.getElementById('skillToTeach');
    teachDropdown.innerHTML = '<option value="">Select a skill you can teach...</option>';
    currentUserTeachSkills.forEach(skill => {
        teachDropdown.innerHTML += `<option value="${skill.skill_id}">${skill.skill_name}</option>`;
    });
    
    const learnDropdown = document.getElementById('skillToLearn');
    learnDropdown.innerHTML = '<option value="">Select a skill to learn...</option>';
    if (partnerTeachSkills && partnerTeachSkills.length > 0) {
        partnerTeachSkills.forEach(skill => {
            learnDropdown.innerHTML += `<option value="${skill}">${skill}</option>`;
        });
    }
    
    document.getElementById('swapRequestForm').reset();
    document.getElementById('charCount').textContent = '0';
    document.getElementById('swapPopup').classList.add('active');
}

function closeSwapPopup() {
    document.getElementById('swapPopup').classList.remove('active');
}

document.addEventListener('DOMContentLoaded', function() {
    
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('status') === 'success') {
        Swal.fire({
            title: 'Request Sent!',
            text: 'Your swap request has been sent.',
            icon: 'success',
            confirmButtonColor: '#E68A4D',
            confirmButtonText: 'Okey!'
        }).then(() => {
            window.history.replaceState({}, document.title, window.location.pathname);
        });
    } else if (urlParams.get('status') === 'error') {
        Swal.fire({
            title: 'Oops!',
            text: 'An error occured while processing request.',
            icon: 'error',
            confirmButtonColor: '#d33'
        });
    }

    const swapMsg = document.getElementById('swapMessage');
    if(swapMsg) {
        swapMsg.addEventListener('input', function() {
            document.getElementById('charCount').textContent = this.value.length;
        });
    }

    const searchInput = document.getElementById('skillSearch');
    const categoryFilter = document.getElementById('categoryFilter');
    
    if (searchInput && categoryFilter) {
        function loadPartnersByCategory() {
            const formData = new FormData();
            formData.append('category_id', categoryFilter.value);
            formData.append('search_term', searchInput.value);
            
            fetch('ajax/filter-partners.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const partnersGrid = document.querySelector('.partners-grid');
                partnersGrid.innerHTML = '';
                if (data.partners && data.partners.length > 0) {
                    data.partners.forEach(partner => {
                        partnersGrid.innerHTML += createPartnerCard(partner);
                    });
                } else {
                    partnersGrid.innerHTML = '<div class="empty-results"><p>No partners found matching your criteria.</p></div>';
                }
            });
        }
        searchInput.addEventListener('keyup', loadPartnersByCategory);
        categoryFilter.addEventListener('change', loadPartnersByCategory);
    }
});


function createPartnerCard(partner) {
    const profilePic = (partner.profile_picture && partner.profile_picture !== "") 
                       ? partner.profile_picture 
                       : 'default.png';

    const ratingFixed = parseFloat(partner.avg_rating || 0).toFixed(1);

    const renderBadges = (skills, type) => {
        if (!skills || skills.length === 0) {
            return `<span style="font-size: 11px; color: #9CA3AF;">${type === 'teach' ? 'No skills listed' : 'Open to anything'}</span>`;
        }
        const limit = type === 'teach' ? 2 : 1;
        let html = '';
        
       skills.slice(0, limit).forEach(s => {
        const className = type === 'teach' ? 'skill-badge' : 'interest-badge';

        let skillName = s.name || s; 
        let catName = s.category || 'General';

        html += `
            <div class="${className}" title="${escapeHtml(skillName)} (${escapeHtml(catName)})">
                <span>${escapeHtml(skillName)}</span>
                <span class="cat-label">${escapeHtml(catName)}</span>
            </div>`;
        });
        return html;
    };

    const safeName = escapeHtml(partner.name);
    const safePic = escapeHtml(profilePic); 
    
    const simpleOffered = partner.skills_offered.map(s => s.name || s);
    const simpleLearn = partner.learning_interests.map(s => s.name || s);

    const skillsOfferedJson = JSON.stringify(simpleOffered).replace(/"/g, '&quot;');
    const skillsLearnJson = JSON.stringify(simpleLearn).replace(/"/g, '&quot;');

    return `
    <div class="partner-card">
        <div class="partner-header">
            <div class="partner-avatar">
                <img src="uploads/${safePic}" 
                     onerror="this.onerror=null; this.src='uploads/default.png';"
                     alt="Avatar" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">
            </div>
            <div class="partner-info">
                <h3>${safeName}</h3>
                <div class="partner-rating">
                    <span class="star">★</span>
                    <span>${partner.avg_rating}</span>
                </div>
            </div>
        </div>
        
        <div class="skills-section">
            <h4>Skills Offered</h4>
            <div class="skill-badges-container">
                ${renderBadges(partner.skills_offered, 'teach')}
            </div>
        </div>

        <div class="skills-section">
            <h4>Learning Interests</h4>
            <div class="skill-badges-container">
                ${renderBadges(partner.learning_interests, 'learn')}
            </div>
        </div>

        <button type="button" class="swap-btn" onclick="openSwapPopup(
            ${partner.user_id},
            '${safeName}',
            '${safePic}',
            '${partner.avg_rating}',
            ${skillsOfferedJson},
            ${skillsLearnJson}
        )">Swap Now</button>
    </div>
    `;
}

function escapeHtml(text) {
    if (!text) return "";
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
</script>
</body>
</html>