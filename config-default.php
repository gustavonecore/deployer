<?php

return $config = [
	'target' => [
		'path' => '',
		'ignored' => [],
	],
	'vcs' => [
		'name' => 'github',
		'username' => 'github-username',
		'repository' => 'github-repository',
		'branch' => 'master',
		'oauth_token' => 'github-oauth-token',
		'url' => 'https://github.com/{username}/{repository}/archive/{branch}.zip',
	],
];