<?php
// app/i18n.php — язык берём только из users.lang, иначе дефолт.
// Никаких cookie/session/Accept-Language и никакой автологики по направлению.

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

/** Поддерживаемые языки */
const I18N_SUPPORTED = ['fi','uk','en'];
const I18N_DEFAULT   = 'fi';

/** (оставим, вдруг нужно в других местах проекта) */
function dir_slug_normalize(?string $s): string {
  $s = strtolower(trim((string)$s));
  $s = str_replace(['→','—','–','_', '  '], '-', $s);
  $s = str_replace([' to ', ' -> '], '-', $s);
  $s = preg_replace('~[^a-z\-]~u', '-', $s);
  $s = preg_replace('~-+~', '-', $s);
  return trim($s, '-');
}

/** Гарантируем существующий поддерживаемый язык и наличие файла словаря */
function i18n_sanitize_lang(?string $lang): string {
  $l = strtolower(substr((string)$lang, 0, 5));
  if (!in_array($l, I18N_SUPPORTED, true)) return I18N_DEFAULT;
  $file = __DIR__ . "/lang/{$l}.php";
  if (!is_file($file)) return I18N_DEFAULT;
  return $l;
}

/**
 * Определение языка:
 * 1) users.lang (источник истины)
 * 2) дефолт
 */
function i18n_detect_lang(PDO $pdo = null, ?int $userId = null): string {
  if ($pdo && $userId) {
    $stmt = $pdo->prepare("SELECT lang FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $userId]);
    $profLang = $stmt->fetchColumn();
    if (!empty($profLang)) return i18n_sanitize_lang($profLang);
  }
  return I18N_DEFAULT;
}

/** Загрузка словаря (есть встроенный фолбэк) */
function i18n_load(string $lang): array {
  $lang = i18n_sanitize_lang($lang);
  $file = __DIR__ . "/lang/{$lang}.php";

  $builtinFallback = [
    'site.title'         => 'Cards',
    'nav.my_decks'       => 'My decks',
    'user.decks'         => 'Decks',
    'user.categories'    => 'Categories',
    'user.edit_profile'  => 'Edit profile',
    'auth.logout'        => 'Logout',
    'direction.badge'    => 'Direction',
    'alert.no_direction' => 'Direction not set.',
    'alert.no_decks'     => 'Nothing here yet.',
    'deck.locked'        => 'Locked',
    'deck.learn'         => 'Learn',
    'deck.preview'       => 'Preview',
    'deck.progress'      => 'Progress',
    'ui.lang'            => 'Language',
  ];

  if (is_file($file)) {
    $data = include $file;
    return is_array($data) ? $data : $builtinFallback;
  }
  return $builtinFallback;
}

/** Запомнить язык (чисто для удобства клиента; детект его НЕ читает) */
function i18n_remember(string $lang): void {
  $lang = i18n_sanitize_lang($lang);
  $_SESSION['lang'] = $lang;
  setcookie('lang', $lang, time()+60*60*24*365, '/');
}

/** Bootstrap: определить язык, загрузить словарь; при пустом users.lang — записать найденный */
function i18n_bootstrap(PDO $pdo = null, ?int $userId = null): void {
  $lang = i18n_detect_lang($pdo, $userId);
  $GLOBALS['_I18N_LANG'] = $lang;
  $GLOBALS['_I18N_DICT'] = i18n_load($lang);

  if ($pdo && $userId) {
    $stmt = $pdo->prepare("SELECT lang FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $userId]);
    $cur = $stmt->fetchColumn();
    if (empty($cur)) {
      $upd = $pdo->prepare("UPDATE users SET lang = :l WHERE id = :id");
      $upd->execute([':l' => $lang, ':id' => $userId]);
    }
  }
  i18n_remember($lang);
}

/** Хелперы */
function t(string $key, array $vars = []): string {
  $dict = $GLOBALS['_I18N_DICT'] ?? [];
  $text = $dict[$key] ?? $key;
  foreach ($vars as $k => $v) $text = str_replace("{{$k}}", (string)$v, $text);
  return $text;
}
function current_lang(): string { return $GLOBALS['_I18N_LANG'] ?? I18N_DEFAULT; }
function supported_langs(): array { return I18N_SUPPORTED; }
