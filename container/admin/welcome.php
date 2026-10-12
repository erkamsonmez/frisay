<?php

if (!defined('IN_ADMIN')) {
	exit;
}

$report = Welcome::getReport();
$setup = is_array($report['setup'] ?? null) ? $report['setup'] : [];

$smarty->assign([
	'welcomeSetupScore' => (int) ($report['setup_score'] ?? 0),
	'welcomeSetupTone' => (string) ($report['setup_tone'] ?? 'bad'),
	'welcomeSetupItems' => $setup['items'] ?? [],
	'welcomeDoneCount' => (int) ($report['done_count'] ?? 0),
	'welcomeTotalChecks' => (int) ($report['total_checks'] ?? 0),
	'welcomeNext' => $report['next'] ?? null,
	'welcomeSeoCards' => $report['seo_cards'] ?? [],
	'welcomeSeoAverage' => (int) ($report['seo_average'] ?? 0),
	'welcomeSeoAverageTone' => (string) ($report['seo_average_tone'] ?? 'bad'),
	'welcomePayments' => $setup['payments'] ?? [],
	'welcomeAdminUri' => (string) ($setup['admin_uri'] ?? Admin::uri()),
	'welcomeAdminChanged' => !empty($setup['admin_changed']),
	'welcomeGuide' => $report['guide'] ?? [],
	'adminBodyClass' => 'welcome-page-on',
]);

AdminPage::add('welcome', 'Welcome');
