<!DOCTYPE html>
<html lang="tr">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex,nofollow">
	<title>{$siteName|escape} — Bakım</title>
	<style>
		:root {
			--ink: #0f172a;
			--muted: #64748b;
			--line: #e2e8f0;
			--bg: #f8fafc;
		}
		* { box-sizing: border-box; }
		body {
			margin: 0;
			min-height: 100vh;
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 24px;
			font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
			color: var(--ink);
			background:
				radial-gradient(900px 420px at 15% -10%, rgba(30, 91, 219, 0.08), transparent 55%),
				radial-gradient(700px 380px at 90% 0%, rgba(14, 116, 144, 0.06), transparent 50%),
				var(--bg);
		}
		.box {
			width: 100%;
			max-width: 520px;
			padding: 36px 32px;
			border: 1px solid var(--line);
			border-radius: 18px;
			background: #fff;
			box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
			text-align: center;
		}
		.logo {
			display: block;
			max-width: 220px;
			max-height: 64px;
			width: auto;
			height: auto;
			margin: 0 auto 20px;
			object-fit: contain;
		}
		h1 {
			margin: 0 0 12px;
			font-size: 1.45rem;
			font-weight: 750;
			letter-spacing: -0.02em;
		}
		.message {
			margin: 0;
			color: var(--muted);
			font-size: 1rem;
			line-height: 1.65;
		}
		.message p { margin: 0 0 0.65em; }
		.message p:last-child { margin-bottom: 0; }
	</style>
</head>
<body>
	<main class="box">
		{if $logoUrl|default:'' != ''}
		<img class="logo" src="{$logoUrl|escape}" alt="{$siteName|escape}">
		{else}
		<h1>{$siteName|escape}</h1>
		{/if}
		<div class="message">{$maintenanceMessage nofilter}</div>
	</main>
</body>
</html>
