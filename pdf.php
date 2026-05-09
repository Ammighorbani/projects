<?php
session_start();
if (!isset($_SESSION['user_id'])) exit('دسترسی ممنوع');
$user_id = $_SESSION['user_id'];

ob_clean();
require_once 'db_pdf.php';

define('K_TCPDF_EXTERNAL_CONFIG', true);
require_once 'vendor/tcpdf/tcpdf.php';

// --- نام کاربر ---
$stmt = $conn->prepare("SELECT first_name, last_name, email, phone_number, country, city, province FROM users_account WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_info = $stmt->get_result()->fetch_assoc();
$stmt->close();

$full_name = $user_info['first_name'] . ' ' . $user_info['last_name'];

// --- اطلاعات شخصی (لینکدین، گیت‌هاب و ...) ---
$stmt = $conn->prepare("SELECT linkedin, github, personal_stmt FROM personal_information WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$personal = $stmt->get_result()->fetch_assoc() ?: [];
$stmt->close();

// --- ساخت PDF ---
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('JobToGo Resume Builder');
$pdf->SetAuthor($full_name);
$pdf->SetTitle('رزومه - ' . $full_name);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(15, 20, 15);
$pdf->SetAutoPageBreak(true, 15);
$pdf->AddPage();
$pdf->SetFont('dejavusans', '', 12);

// --- تابع کوئری ---
$q = function($sql) use ($conn, $user_id) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result();
};

$html = '<h1 style="text-align:center;color:#e67e22;font-size:26pt;">رزومه ' . htmlspecialchars($full_name) . '</h1><br><br>';

// ==================== اطلاعات فردی (جدید و کامل!) ====================
$html .= '<h2 style="color:#e67e22;border-bottom:2px solid #e67e22;padding-bottom:8px;">اطاطلاعات فردی</h2>';

$html .= '<table cellpadding="5" style="width:100%;">';
if (!empty($user_info['email'])) {
    $html .= '<tr><td style="width:25%;"><strong>ایمیل:</strong></td><td>' . htmlspecialchars($user_info['email']) . '</td></tr>';
}
if (!empty($user_info['phone_number'])) {
    $html .= '<tr><td><strong>تلفن:</strong></td><td>' . htmlspecialchars($user_info['phone_number']) . '</td></tr>';
}

$location = array_filter([$user_info['city'], $user_info['province'], $user_info['country']]);
if ($location) {
    $html .= '<tr><td><strong>مکان:</strong></td><td>' . htmlspecialchars(implode('، ', $location)) . '</td></tr>';
}

if (!empty($personal['linkedin'])) {
    $html .= '<tr><td><strong>لینکدین:</strong></td><td><a href="' . htmlspecialchars($personal['linkedin']) . '">' . htmlspecialchars($personal['linkedin']) . '</a></td></tr>';
}
if (!empty($personal['github'])) {
    $html .= '<tr><td><strong>گیت‌هاب:</strong></td><td><a href="' . htmlspecialchars($personal['github']) . '">' . htmlspecialchars($personal['github']) . '</a></td></tr>';
}
if (!empty($personal['personal_stmt'])) {
    $html .= '<tr><td><strong>بیوگرافی:</strong></td><td>' . nl2br(htmlspecialchars($personal['personal_stmt'])) . '</td></tr>';
}
$html .= '</table><br><br>';

// ==================== خلاصه حرفه‌ای ====================
if ($row = $q("SELECT summary FROM professional_summary WHERE user_id=?")->fetch_assoc()) {
    if (!empty(trim($row['summary'] ?? ''))) {
        $html .= '<h2 style="color:#e67e22;border-bottom:2px solid #e67e22;padding-bottom:8px;">خلاصه حرفه‌ای</h2>';
        $html .= '<p style="line-height:1.8;">' . nl2br(htmlspecialchars($row['summary'])) . '</p><br><br>';
    }
}

// ==================== تجربه کاری ====================
$res = $q("SELECT * FROM work_experience WHERE user_id=? ORDER BY job_index");
if ($res->num_rows > 0) {
    $html .= '<h2 style="color:#e67e22;border-bottom:2px solid #e67e22;padding-bottom:8px;">تجربه کاری</h2>';
    while ($w = $res->fetch_assoc()) {
        $tasks = array_filter([$w['bullet1'],$w['bullet2'],$w['bullet3'],$w['bullet4'],$w['bullet5']]);
        $html .= '<h3>' . htmlspecialchars($w['job_title']) . '</h3>';
        $html .= '<p><strong>' . htmlspecialchars($w['company_name']) . '</strong> — ' . htmlspecialchars($w['company_city_province']) . '</p>';
        $html .= '<p style="color:#e67e22;">' . htmlspecialchars($w['start_date']) . ' تا ' . ($w['end_date'] ?: 'تاکنون') . '</p>';
        if ($tasks) {
            $html .= '<ul style="padding-right:20px;">';
            foreach ($tasks as $t) if ($t) $html .= '<li>' . htmlspecialchars($t) . '</li>';
            $html .= '</ul>';
        }
        $html .= '<br>';
    }
}

// ==================== تحصیلات ====================
$res = $q("SELECT * FROM education WHERE user_id=? ORDER BY education_index");
if ($res->num_rows > 0) {
    $html .= '<h2 style="color:#e67e22;border-bottom:2px solid #e67e22;padding-bottom:8px;">تحصیلات</h2>';
    while ($e = $res->fetch_assoc()) {
        if (!empty(trim($e['university_name'] ?? ''))) {
            $html .= '<h3>' . htmlspecialchars($e['university_name']) . '</h3>';
            $html .= '<p>' . htmlspecialchars($e['field_of_study'] ?? '') . ' — معدل: ' . ($e['gpa'] ?: '—') . '</p>';
            $html .= '<p style="color:#e67e22;">سال فارغ‌التحصیلی: ' . htmlspecialchars($e['graduation_year'] ?? '') . '</p><br>';
        }
    }
}

// ==================== مهارت‌ها ====================
$res = $q("SELECT * FROM skills WHERE user_id=? AND skill_name!='' ORDER BY skill_index");
if ($res->num_rows > 0) {
    $html .= '<h2 style="color:#e67e22;border-bottom:2px solid #e67e22;padding-bottom:8px;">مهارت‌ها</h2>';
    $html .= '<table cellpadding="8" border="0"><tr>';
    $i = 0;
    while ($s = $res->fetch_assoc()) {
        if ($i % 3 == 0 && $i > 0) $html .= '</tr><tr>';
        $type = $s['skill_type'] == 'hard' ? 'سخت' : 'نرم';
        $level = ['beginner'=>'مبتدی','intermediate'=>'متوسط','advanced'=>'حرفه‌ای'][$s['skill_level']];
        $html .= '<td>• <strong>' . htmlspecialchars($s['skill_name']) . '</strong> (' . $type . ' - ' . $level . ')</td>';
        $i++;
    }
    $html .= '</tr></table><br>';
}

// ==================== زبان‌ها ====================
$res = $q("SELECT language_name, proficiency FROM languages WHERE user_id=? ORDER BY language_index");
if ($res->num_rows > 0) {
    $html .= '<h2 style="color:#e67e22;border-bottom:2px solid #e67e22;padding-bottom:8px;">زبان‌ها</h2>';
    $html .= '<table cellpadding="8" border="0"><tr>';
    $i = 0;
    while ($l = $res->fetch_assoc()) {
        if ($i % 3 == 0 && $i > 0) $html .= '</tr><tr>';
        $level_fa = ['beginner'=>'مبتدی','intermediate'=>'متوسط','advanced'=>'حرفه‌ای','native'=>'بومی'][$l['proficiency']];
        $html .= '<td>• <strong>' . htmlspecialchars($l['language_name']) . '</strong> - ' . $level_fa . '</td>';
        $i++;
    }
    $html .= '</tr></table><br>';
}

// ==================== دوره‌ها و گواهینامه‌ها ====================
$res = $q("SELECT course_name, received_date FROM courses_certificates WHERE user_id=? AND course_name!='' ORDER BY course_index");
if ($res->num_rows > 0) {
    $html .= '<h2 style="color:#e67e22;border-bottom:2px solid #e67e22;padding-bottom:8px;">دوره‌ها و گواهینامه‌ها</h2>';
    while ($c = $res->fetch_assoc()) {
        $html .= '<p>• <strong>' . htmlspecialchars($c['course_name']) . '</strong> — ' . htmlspecialchars($c['received_date']) . '</p>';
    }
    $html .= '<br>';
}

// ==================== پروژه‌ها ====================
$res = $q("SELECT * FROM project WHERE user_id=? AND title!='' ORDER BY project_index");
if ($res->num_rows > 0) {
    $html .= '<h2 style="color:#e67e22;border-bottom:2px solid #e67e22;padding-bottom:8px;">پروژه‌ها</h2>';
    while ($p = $res->fetch_assoc()) {
        $type_fa = ['project'=>'شخصی','freelance'=>'فریلنس','open-source'=>'اوپن‌سورس','university'=>'دانشگاهی'][$p['type']];
        $html .= '<h3>' . htmlspecialchars($p['title']) . ' <em>(' . $type_fa . ')</em></h3>';
        $html .= '<p>' . nl2br(htmlspecialchars($p['description'])) . '</p><br>';
    }
}

// ==================== پاسپورت و ویزا ====================
$passport = $q("SELECT passport_number FROM passport_info WHERE user_id=?")->fetch_column();
$visa = $q("SELECT description FROM visa_info WHERE user_id=?")->fetch_column();
if ($passport || $visa) {
    $html .= '<h2 style="color:#e67e22;border-bottom:2px solid #e67e22;padding-bottom:8px;">اطلاعات مهاجرت</h2>';
    if ($passport) $html .= '<p><strong>شماره پاسپورت:</strong> ' . htmlspecialchars($passport) . '</p>';
    if ($visa) $html .= '<p><strong>وضعیت ویزا:</strong> ' . nl2br(htmlspecialchars($visa)) . '</p>';
}

// ==================== خروجی نهایی ====================
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('Resume_' . preg_replace('/[^a-zA-Z0-9آ-ی]/', '_', $full_name) . '.pdf', 'D');
exit;