<?php
/**
 * Admin Logout
 */
if (!defined('BASE_PATH')) { exit; }

admin_logout();
redirect('/admin/login');
