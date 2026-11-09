<?php
if (defined('FUNCTIONS_LOADED')) {
    return;
}
define('FUNCTIONS_LOADED', true);

/**
 * Format tanggal Indonesia
 */
function tgl_indo($tanggal) {
    if (empty($tanggal)) return '-';
    $bulan = array (
        1 => 'Januari','Februari','Maret','April','Mei','Juni',
        'Juli','Agustus','September','Oktober','November','Desember'
    );
    $pecahkan = explode('-', $tanggal);
    if (count($pecahkan) < 3) return $tanggal;
    return $pecahkan[2] . ' ' . $bulan[(int)$pecahkan[1]] . ' ' . $pecahkan[0];
}

/**
 * Escape output untuk mencegah XSS
 */
function e($data, $flags = ENT_QUOTES, $encoding = 'UTF-8') {
    return htmlspecialchars($data ?? '', $flags, $encoding);
}

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token($token) {
    if (empty($token)) return false;
    if (isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
        return true;
    }
    return false;
}

/**
 * Validate date format (YYYY-MM-DD)
 */
function validate_date($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Sanitize numeric input
 */
function sanitize_number($value, $min = null, $max = null) {
    $value = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    if ($min !== null && $value < $min) return false;
    if ($max !== null && $value > $max) return false;
    return $value;
}

/**
 * Sanitize string input
 */
function sanitize_string($str, $max_length = null) {
    $str = trim($str ?? '');
    $str = strip_tags($str);
    if ($max_length && strlen($str) > $max_length) {
        return false;
    }
    return $str;
}
