<?php
	if (!defined('IN_ADMIN')) {
		exit;
	}

	$notifyType = AdminNotification::normalizeType((string) Tools::getValue('type', ''));

	if (Tools::isSubmit('markRead')) {
		$postToken = (string) Tools::getValue('token');

		if (hash_equals($adminToken, $postToken)) {
			AdminNotification::markRead((int) Tools::getValue('id'));
		}

		header('Location: ' . Admin::url('notifications' . ($notifyType !== '' ? '?type=' . rawurlencode($notifyType) : '')));
		exit;
	}

	if (Tools::isSubmit('markAllRead')) {
		$postToken = (string) Tools::getValue('token');

		if (hash_equals($adminToken, $postToken)) {
			AdminNotification::markAllRead($notifyType);
		}

		header('Location: ' . Admin::url('notifications' . ($notifyType !== '' ? '?type=' . rawurlencode($notifyType) : '')));
		exit;
	}

	AdminNotification::syncOpenOrders();

	$filterCounts = AdminNotification::getFilterCounts();
	$notifyTypeButtons = [];
	$notifyAllCount = 0;

	foreach (AdminNotification::typeMeta() as $type => $meta) {
		$count = (int) ($filterCounts[$type] ?? 0);
		$notifyAllCount += $count;
		$notifyTypeButtons[] = [
			'type' => $type,
			'label' => $meta['label'],
			'btn' => $meta['btn'],
			'icon' => $meta['icon'],
			'color' => $meta['color'],
			'count' => $count,
		];
	}

	$notifications = AdminNotification::getList(100, false, $notifyType);

	$smarty->assign([
		'notifications' => $notifications,
		'notificationsTotal' => count($notifications),
		'unreadCount' => $notifyAllCount,
		'notifyType' => $notifyType,
		'notifyTypeButtons' => $notifyTypeButtons,
	]);

	AdminPage::add('notifications', 'Notifications');
