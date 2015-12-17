<?php

return array
(

	'config' => array(
		'required' => array(
			'app.name',
			'app.key',
			'app.locale',
			'app.fallback',
			'routing.namespace',
			'routing.defaultcontroller',
			'routing.namespace',
			'routing.loginroute',
			'analytics.google.version',
			'analytics.google.trackingid',
		),
	),

	'admin' => array(
		'pm' => array(
			'driver' => 'slack',

			'slack' => array(
				'hook' => 'https://hooks.slack.com/services/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',
				'icon' => ':sheep:',
				'channel' => array(
					'info' => '#dev-info',
					'warning' => '#dev-warning',
					'error' => '#dev-error',
				),
			),
		),
	),

	'auth' => array(
		'ttl' => array(
			'passwordreset' => 15,
			'authtoken' => 24,
		),
	),

	'localization' => array(
		'languages' => array(
			'rtl' => array(
				'ar',		// Arabic
				// 'arc',	// Aramaic
				// 'bcc',	// Southern Balochi
				// 'bqi',	// Bakthiari
				// 'ckb',	// Sorani Kurdish
				'dv',		// Dhivehi
				'fa',		// Persian
				// 'glk',	// Gilaki
				'he',		// Hebrew
				// 'lrc',	// Northern Luri
				// 'mzn',	// Mazanderani
				// 'pnb',	// Western Punjabi
				'ps',		// Pashto
				'sd',		// Sindhi
				'ug',		// Uyghur
				'ur',		// Urdu
				'yi',		// Yiddish
			),
		),
	),

);