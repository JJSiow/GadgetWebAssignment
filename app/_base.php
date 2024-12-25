<?php

// ============================================================================
// PHP Setups
// ============================================================================

date_default_timezone_set('Asia/Kuala_Lumpur');
session_start();

// ============================================================================
// General Page Functions
// ============================================================================

// Is GET request?
function is_get()
{
    return $_SERVER['REQUEST_METHOD'] == 'GET';
}

// Is POST request?
function is_post()
{
    return $_SERVER['REQUEST_METHOD'] == 'POST';
}

// Obtain GET parameter
function get($key, $value = null)
{
    $value = $_GET[$key] ?? $value;
    return is_array($value) ? array_map('trim', $value) : trim($value);
}

// Obtain POST parameter
function post($key, $value = null)
{
    $value = $_POST[$key] ?? $value;
    return is_array($value) ? array_map('trim', $value) : trim($value);
}

// Obtain REQUEST (GET and POST) parameter
function req($key, $value = null)
{
    $value = $_REQUEST[$key] ?? $value;
    return is_array($value) ? array_map('trim', $value) : trim($value);
}

// Redirect to URL
function redirect($url = null)
{
    $url ??= $_SERVER['REQUEST_URI'];
    header("Location: $url");
    exit();
}

// Set or get temporary session variable
function temp($key, $value = null)
{
    if ($value !== null) {
        $_SESSION["temp_$key"] = $value;
    } else {
        $value = $_SESSION["temp_$key"] ?? null;
        unset($_SESSION["temp_$key"]);
        return $value;
    }
}

// Obtain uploaded file --> cast to object
function get_file($key)
{
    $f = $_FILES[$key] ?? null;

    if ($f && $f['error'] == 0) {
        return (object)$f;
    }

    return null;
}

// Obtain multiple uploaded file --> cast to object
function get_files($key)
{
    $files = $_FILES[$key] ?? null;

    if (!$files || !is_array($files['name'])) {
        return null; // Return null if no files or invalid structure
    }

    $uploadedFiles = [];

    foreach ($files['name'] as $index => $name) {
        if ($files['error'][$index] === 0) {
            $uploadedFiles[] = (object)[
                'name' => $files['name'][$index],
                'type' => $files['type'][$index],
                'tmp_name' => $files['tmp_name'][$index],
                'error' => $files['error'][$index],
                'size' => $files['size'][$index]
            ];
        }
    }

    return $uploadedFiles;
}

// Crop, resize and save photo
function save_photo($f, $folder, $width = 200, $height = 200)
{
    $photo = uniqid() . '.jpg';

    require_once 'lib/SimpleImage.php';
    $img = new SimpleImage();
    $img->fromFile($f->tmp_name)
        ->thumbnail($width, $height)
        ->toFile("$folder/$photo", 'image/jpeg');

    return $photo;
}

function save_photos($files, $folder, $width = 200, $height = 200)
{
    $photos = [];

    require_once 'lib/SimpleImage.php';

    foreach ($files as $f) {
        $photo = uniqid() . '.jpg';

        $img = new SimpleImage();
        $img->fromFile($f->tmp_name)
            ->thumbnail($width, $height)
            ->toFile("$folder/$photo", 'image/jpeg');

        $photos[] = $photo;
    }

    return $photos;
}

// Is money?
function is_money($value)
{
    return preg_match('/^\-?\d+(\.\d{1,2})?$/', $value);
}

// Is email?
function is_email($value)
{
    return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
}

// Return local root path
function root($path = '')
{
    return "$_SERVER[DOCUMENT_ROOT]/$path";
}

// Return base url (host + port)
function base($path = '')
{
    return "http://$_SERVER[SERVER_NAME]:$_SERVER[SERVER_PORT]/$path";
}

// ============================================================================
// HTML Helpers
// ============================================================================

// Encode HTML special characters
function encode($value)
{
    return htmlentities($value);
}

// Generate <input type='text'>
function html_text($key, $attr = '')
{
    $value = encode($GLOBALS[$key] ?? '');
    echo "<input type='text' id='$key' name='$key' value='$value' $attr>";
}

function html_password($key, $attr = '')
{
    $value = encode($GLOBALS[$key] ?? '');
    echo "<input type='password' id='$key' name='$key' value='$value' $attr>";
}

//JJ
// // Generate <input type='number'>
// function html_number($key, $min = '', $max = '', $step = '', $attr = '') {
//     $value = encode($GLOBALS[$key] ?? '');
//     echo "<input type='number' id='$key' name='$key' value='$value'
//                  min='$min' max='$max' step='$step' $attr>";
// }

// Generate <input type='search'>
function html_search($key, $attr = '')
{
    $value = encode($GLOBALS[$key] ?? '');
    echo "<input type='search' id='$key' name='$key' value='$value' $attr>";
}

function html_search2($key, $value = '', $attr = '')
{
    $sanitized_value = htmlspecialchars($value, ENT_QUOTES);
    echo "<input type='search' id='$key' name='$key' value='$sanitized_value' $attr>";
}

// Generate <input type='radio'> list
function html_radios($key, $items, $attr = '', $br = false)
{
    $value = encode($GLOBALS[$key] ?? '');
    echo '<div>';
    foreach ($items as $id => $text) {
        $state = $id == $value ? 'checked' : '';
        echo "<label><input type='radio' id='{$key}_$id' name='$key' value='$id' $state $attr>$text</label>";
        if ($br) {
            echo '<br>';
        }
    }
    echo '</div>';
}
// Generate <input type="number"> field
// Render a number input with default value and attributes
// How to use: html_number('price', '100', ['min' => '0', 'max' => '1000', 'step' => '10', 'class' => 'number-input'], 'RM ');
function html_number($name, $value = '', $attrs = [], $prefix = '')
{
    $attrs_str = '';
    foreach ($attrs as $k => $v) {
        $attrs_str .= " $k=\"" . htmlspecialchars($v) . '"';
    }

    $prefix_html = $prefix ? "<span class='input-prefix'>$prefix</span>" : '';

    return "$prefix_html<input type='number' id='$name' name='$name' value='" .
        htmlspecialchars($value) . "'$attrs_str>";
}

// Generate <select>
//JJ
function html_select($key, $items, $default = '- Select One -', $attr = '')
{
    $value = encode($GLOBALS[$key] ?? '');
    echo "<select id='$key' name='$key' $attr>";
    if ($default !== null) {
        echo "<option value=''>$default</option>";
    }
    foreach ($items as $id => $text) {
        $state = $id == $value ? 'selected' : '';
        echo "<option value='$id' $state>$text</option>";
    }
    echo '</select>';
}

//KYYAP
function html_select2($key, $items, $default = '- Select One -', $selected = null)
{
    echo "<select id='" . htmlspecialchars($key) . "' name='" . htmlspecialchars($key) . "'>";

    // Default option
    if ($default !== null) {
        $defaultSelected = empty($selected) ? 'selected' : '';
        echo "<option value='' $defaultSelected>" . htmlspecialchars($default) . "</option>";
    }

    // Loop through items to create options
    foreach ($items as $item) {
        $isSelected = ($item == $selected) ? 'selected' : '';
        echo "<option value='" . htmlspecialchars($item) . "' $isSelected>" . htmlspecialchars($item) . "</option>";
    }

    echo '</select>';
}


// Generate <input type='file'>
function html_file($key, $accept = '', $attr = '')
{
    echo "<input type='file' id='$key' name='$key' accept='$accept' $attr>";
}

// Generate <textarea>
function html_textarea($key, $attr = '')
{
    $value = encode($GLOBALS[$key] ?? '');
    echo "<textarea id='$key' name='$key' $attr>$value</textarea>";
}

// Generate table headers <th>
function table_headers($fields, $sort = '', $dir = '', $href = '')
{
    // Parse existing href parameters
    parse_str($href, $params);

    foreach ($fields as $k => $v) {
        // Set the direction for the next click
        $next_dir = ($k == $sort) ? ($dir == 'asc' ? 'desc' : 'asc') : 'asc';

        // Add sort indicators (arrows)
        $arrow = '';
        if ($k == $sort) {
            $arrow = $dir == 'asc' ? ' ▴' : ' ▾';
        }

        // Merge parameters with sort and dir
        $url_params = array_merge($params, [
            'sort' => $k,
            'dir' => $next_dir
        ]);

        // Build query string
        $query = http_build_query($url_params);

        // Build the header link with sort indicator
        echo "<th><a href='?$query'>$v$arrow</a></th>";
    }
}

// ============================================================================
// Error Handlings
// ============================================================================

//KYYAP
// Generate table headers <th>
function table_headers2($fields, $sort, $dir, $href = '')
{
    foreach ($fields as $k => $v) {
        // Check if the column name contains the word "status" (case-insensitive)
        $isStatusColumn = stripos($k, 'status') !== false ? ' data-status-column="true"' : '';

        // If the column is 'action', just print it as is
        if ($k === 'action') {
            echo "<th>$v</th>";
        } else {
            $d = 'asc'; // Default direction
            $c = '';    // Default class

            if ($k == $sort) {
                $d = $dir == 'asc' ? 'desc' : 'asc';
                $c = $dir;
            }

            // Output the table header with the dynamic data-status-column attribute for any column with "status" in its name
            echo "<th$isStatusColumn><a href='?sort=$k&dir=$d&$href' class='$c'>$v</a></th>";
        }
    }
}

// Global error array
$_err = [];

// Generate <span class='err'>
//JJ
function err($key)
{
    global $_err;
    if ($_err[$key] ?? false) {
        echo "<span class='err'>$_err[$key]</span>";
    } else {
        echo '<span></span>';
    }
}

// ============================================================================
// Database Setups and Functions
// ============================================================================

// Global PDO object
$_db = new PDO('mysql:dbname=gadgetwebdb', 'root', '', [
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
]);

// Is unique?
function is_unique($value, $table, $field)
{
    global $_db;
    $stm = $_db->prepare("SELECT COUNT(*) FROM $table WHERE $field = ?");
    $stm->execute([$value]);
    return $stm->fetchColumn() == 0;
}

// Is exists?
function is_exists($value, $table, $field)
{
    global $_db;
    $stm = $_db->prepare("SELECT COUNT(*) FROM $table WHERE $field = ?");
    $stm->execute([$value]);
    return $stm->fetchColumn() > 0;
}

function auto_id($idColumn, $tableName, $idPrefix, $pattern = '/(\d+)$/', $padLength = 5)
{
    global $_db;
    $stmt = $_db->query("SELECT $idColumn FROM $tableName ORDER BY $idColumn DESC LIMIT 1");
    $lastId = $stmt->fetchColumn();

    if (!$lastId) {
        return sprintf("%s%0{$padLength}d", $idPrefix, 1);
    }

    if (preg_match($pattern, $lastId, $matches)) {
        $lastIdNum = (int)$matches[1];
        $newIdNum = $lastIdNum + 1;
    } else {
        throw new Exception("Invalid ID format in the table.");
    }

    return sprintf("%s%0{$padLength}d", $idPrefix, $newIdNum);
}

// ============================================================================
// Security
// ============================================================================

// Global user object
$_member = $_SESSION['member'] ?? null;
$_admin = $_SESSION['admin'] ?? null;

// Login user
function login($member, $url = '/home.php')
{
    $_SESSION['member'] = $member;
    redirect($url);
}

// Logout user
function logout($url = '/index.php')
{
    unset($_SESSION['member']);
    redirect($url);
}

function adminlogin($admin, $url = '../admin/adminHome.php')
{
    $_SESSION['admin'] = $admin;
    redirect($url);
}

// Logout user
function adminlogout($url = '../admin/adminLogin.php')
{
    unset($_SESSION['admin']);
    redirect($url);
}

// Authorization
function auth(...$roles)
{
    global $_user;
    if ($_user) {
        if ($roles) {
            if (in_array($_user->role, $roles)) {
                return; // OK
            }
        } else {
            return; // OK
        }
    }

    redirect('/home.php');
}

// ============================================================================
// Email Functions
// ============================================================================

// Demo Accounts:
// --------------
// AACS3173@gmail.com           npsg gzfd pnio aylm
// BAIT2173.email@gmail.com     ytwo bbon lrvw wclr
// liaw.casual@gmail.com        wtpa kjxr dfcb xkhg
// liawcv1@gmail.com            obyj shnv prpa kzvj

// Initialize and return mail object
function get_mail()
{
    require_once 'lib/PHPMailer.php';
    require_once 'lib/SMTP.php';

    $m = new PHPMailer(true);
    $m->isSMTP();
    $m->SMTPAuth = true;
    $m->Host = 'smtp.gmail.com';
    $m->Port = 587;
    $m->Username = 'AACS3173@gmail.com';
    $m->Password = 'npsg gzfd pnio aylm';
    $m->CharSet = 'utf-8';
    $m->setFrom($m->Username, '😺 Admin');

    return $m;
}

// ============================================================================
// Global Constants and Variables
// ============================================================================

$_status = [
    'A' => 'Active',
    'U' => 'Unactive',
];

$_member_attr = [
    'member_id' => 'Member ID',
    'member_name' => 'Name',
    'member_phone_no' => 'Phone No',
    'member_gender' => 'Gender',
    'member_email' => 'Email',
    'shipping_address' => 'Shipping Address',
    'member_status' => 'Status',
    'member_profile_pic' => 'Profile Pic',
];

$_admin_attr = [
    'admin_id' => 'Admin ID',
    'admin_name' => 'Name',
    'admin_phone_no' => 'Phone No',
    'admin_email' => 'Email',
    'admin_profile_pic' => 'Profile Pic',
    'admin_status' => 'Status',
];

$_genders = [
    'F' => 'Female',
    'M' => 'Male',
];

$_operators = [
    '<' => '<',
    '<=' => '<=',
    '>' => '>',
    '>=' => '>='
];