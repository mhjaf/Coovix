<?php
$country  = $_SERVER['HTTP_CF_IPCOUNTRY'] ?? '';
$city     = $_SERVER['HTTP_CF_IPCITY'] ?? '';
$language = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
$lang     = strtolower($language);

// Step 1: If user is in Iraq, use Cloudflare geo-detection FIRST
if ($country === 'IQ') {
    $kurdish_cities = [
        'erbil', 'sulaymaniyah', 'duhok', 'halabja',
        'kirkuk', 'zakho', 'soran', 'ranya',
        'kalar', 'chamchamal', 'koysinjaq',
        'shaqlawa', 'pirmam', 'mergasur',
        'amedi', 'akre', 'bardarash', 'penjwin',
        'arbil', 'as sulaymaniyah', 'dahuk'
    ];

    $city_lower = strtolower($city);

    if (in_array($city_lower, $kurdish_cities)) {
        header("Location: /kr.html");
        exit();
    } else {
        header("Location: /ar.html");
        exit();
    }
}

// Step 2: Outside Iraq — check browser language
if (preg_match('/(ku|ckb|kmr)/i', $lang)) {
    header("Location: /kr.html");
    exit();
}

if (preg_match('/^ar/', $lang)) {
    header("Location: /ar.html");
    exit();
}

// Step 3: Default — English
readfile("index.html");
exit();
?>