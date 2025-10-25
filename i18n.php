<?php
// i18n.php — простая i18n: язык из ?lang / session / cookie.
// Плюс хелперы __t(), _e(), i18n_link() и i18n_products().

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

const I18N_SUPPORTED = ['uk','fi','en'];
const I18N_DEFAULT   = 'uk';
const I18N_DIR       = __DIR__ . '/locales';

// polyfill для PHP < 8
if (!function_exists('str_contains')) {
  function str_contains(string $haystack, string $needle): bool {
    return $needle === '' || strpos($haystack, $needle) !== false;
  }
}

/** Текущий язык */
function i18n_lang(): string {
  static $lang = null;
  if ($lang !== null) return $lang;

  $lang = I18N_DEFAULT;

  if (isset($_GET['lang'])) {
    $tmp = strtolower(trim($_GET['lang']));
    if (in_array($tmp, I18N_SUPPORTED, true)) $lang = $tmp;
  } elseif (isset($_SESSION['lang']) && in_array($_SESSION['lang'], I18N_SUPPORTED, true)) {
    $lang = $_SESSION['lang'];
  } elseif (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], I18N_SUPPORTED, true)) {
    $lang = $_COOKIE['lang'];
  }

  $_SESSION['lang'] = $lang;
  if (!headers_sent()) {
    setcookie('lang', $lang, time() + 60*60*24*365, '/');
  }
  return $lang;
}

/**
 * Приклеивает текущий lang к ссылке.
 * ВАЖНО: вставляем ?lang=… ДО #anchor и не дублируем lang.
 */
function i18n_link(string $url): string {
  // отделяем якорь
  $frag = '';
  $pos = strpos($url, '#');
  if ($pos !== false) {
    $frag = substr($url, $pos);      // "#..."
    $url  = substr($url, 0, $pos);   // без якоря
  }

  // lang уже есть? — вернуть как есть (плюс вернуть якорь)
  if (strpos($url, 'lang=') !== false) {
    return $url . $frag;
  }

  // выбрать разделитель ? или &
  $sep = (strpos($url, '?') !== false) ? '&' : '?';
  return $url . $sep . 'lang=' . rawurlencode(i18n_lang()) . $frag;
}

/** Ленивая загрузка словаря */
function i18n_dict(): array {
  static $D = null;
  if ($D !== null) return $D;
  $file = I18N_DIR . '/' . i18n_lang() . '.php';
  $D = is_file($file) ? (require $file) : [];
  return $D;
}

/** Получить перевод по ключу "a.b.c" с подстановками {name} */
function __t(string $key, array $vars = []): string {
  $cur = i18n_dict();
  foreach (explode('.', $key) as $seg) {
    if (!is_array($cur) || !array_key_exists($seg, $cur)) return $key;
    $cur = $cur[$seg];
  }
  $str = (string)$cur;
  if ($vars) {
    foreach ($vars as $k => $v) {
      $str = str_replace('{'.$k.'}', (string)$v, $str);
    }
  }
  return $str;
}

/** echo с экранированием */
function _e(string $key, array $vars = []): void {
  echo htmlspecialchars(__t($key, $vars), ENT_QUOTES, 'UTF-8');
}

/** Удобный хелпер: подменить title/desc продуктов из словаря по их id */
function i18n_products(array $products): array {
  foreach ($products as &$p) {
    $id = $p['id'] ?? null;
    if ($id) {
      $t = __t("products.$id.title");
      $d = __t("products.$id.desc");
      if ($t && $t !== "products.$id.title") $p['title'] = $t;
      if ($d && $d !== "products.$id.desc")  $p['desc']  = $d;
    }
  }
  return $products;
}
