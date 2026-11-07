<?php
/**
 * 403 Forbidden - Direct access to storage directory is denied.
 * All media must be requested through authenticated gatekeeper: lihat-foto.php
 */
http_response_code(403);
header("Content-Type: text/plain; charset=utf-8");
exit("403 Forbidden: Akses langsung ke folder penyimpanan privat tidak diizinkan.");

