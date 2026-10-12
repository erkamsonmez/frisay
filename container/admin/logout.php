<?php
	if (!defined('IN_ADMIN')) {
		exit;
	}

	if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
		header('Location: ' . Admin::url('dashboard'));
		exit;
	}

	Admin::logout();
	header('Location: ' . Admin::url('login'));
	exit;
