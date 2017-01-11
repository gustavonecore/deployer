<?php

define('GITHUB', 'github');
define('BITBUCKET', 'bitbucket');
define('APP', __DIR__ . '/');

/**
 * Validate vcs platform
 *
 * @param  string $vcs VCS platform name
 * @return bool
 */
function isValidVCS($vcs)
{
	if (!in_array($vcs, [GITHUB, /*BITBUCKET TODO*/]))
	{
		throw new \InvalidArgumentException('VCS platform ' . $vcs . ' is not allowed');
	}

	return true;
}

/**
 * Download the repository from the VCS platform
 *
 * @param  array   $config  VCS configuration.
 * @return string  Url to get the repository
 */
function downloadRepository($vcs)
{
	$url = null;
	$result = [];
	$vcsName = $vcs['name'];

	isValidVCS($vcsName);

	if (!array_key_exists('username', $vcs))
	{
		throw new \InvalidArgumentException($vcsName . ' username is invalid');
	}

	if (!array_key_exists('repository', $vcs))
	{
		throw new \InvalidArgumentException($vcsName . ' repository is invalid');
	}

	if (!array_key_exists('branch', $vcs))
	{
		throw new \InvalidArgumentException($vcsName . ' branch is invalid');
	}

	if (!array_key_exists('oauth_token', $vcs))
	{
		throw new \InvalidArgumentException($vcsName . ' oauth_token is invalid');
	}

	if ($vcsName === GITHUB)
	{
		$file = APP . $vcs['repository'] . '-' . $vcs['branch'] . '.zip';

		if (file_exists($file))
		{
			unlink($file);
		}

		$url = str_replace('{username}', $vcs['username'], $vcs['url']);
		$url = str_replace('{repository}', $vcs['repository'], $url);
		$url = str_replace('{branch}', $vcs['branch'], $url);

		$result = getGitHubUrl($url, $vcs['oauth_token']);

		// Move the zip file into the remote folder
		$newFile = str_replace(APP, APP . 'remote/', $file);
		rename($file, $newFile);

		$result = [
			'file' => $newFile,
		];
	}

	return $result;
}

/**
 * Get the github repository
 *
 * @param  string  $url     Repository url
 * @param  string  $token   Token of the ouath token
 * @return mixed
 */
function getGitHubUrl($url, $token)
{
	$result = shell_exec('curl -O -J -L -u ' . $token . ':x-oauth-basic ' . $url);
	return $result;
}

/**
 * Unzip the repository files
 *
 * @param  string  $file  Zip File
 * @return string  Repository relative path
 */
function unzipRespository($file)
{
	$zip = new \ZipArchive;
	$res = $zip->open($file);

	if (!$res)
	{
		throw new \RuntimeException('Unable to open zip file to extract');
	}

	if (file_exists(APP . 'remote'))
	{
		shell_exec('rm -fr ' . APP . "remote/");
	}

	mkdir(APP . 'remote');

	$zip->extractTo(APP . 'remote/');
	$zip->close();

	$path = explode('/', $file);
	$filename = str_replace('.zip', '', $path[count($path) - 1]);

	return APP . 'remote/' . $filename;
}

/**
 * Get a list of changed files and folders from the difference between both folders
 *
 * @param  string $target   Target folder
 * @param  string $remote   Remote folder
 * @return array
 */
function getChanges($target, $remote)
{
	$cmd = "diff -r " . $target . " " . $remote . " | grep " . $remote . " | awk '{print $4}'";

	$changes = shell_exec($cmd);

	echo "\n" . $changes ."\n";

	return explode(PHP_EOL, $changes);
}

/**
 * Execute the deloy process
 *
 * @param  array  $config  Configuration of the deploy target
 * @param  array  $changes List of changed files and folders from the remote branch
 * @param  string $remote  The remote branch folder
 * @return array
 */
function deploy($config, $changes, $remote, $dryRun = true)
{
	$timestamp = gmdate('YmdHis');

	$stats = [
		'news' => [],
		'replaced' => [],
		'backups' => [],
	];

	$targetPath = $config['target']['path'];

	$remoteRepositoryFolder = $config['vcs']['repository'] . '-' . $config['vcs']['branch'];

	foreach ($changes as $change)
	{
		if (strpos($change, $remoteRepositoryFolder))
		{
			$replacePath = str_replace($remote, $targetPath, $change);

			// Create a backup if the file|folder already exists
			if (file_exists($replacePath))
			{
				$backup = $replacePath . '.' . $timestamp;

				if (!$dryRun)
				{
					rename($replacePath, $backup);
				}

				$stats['backups'][] = $backup;
			}

			if (!$dryRun)
			{
				rename($change, $replacePath);
			}


			$stats['replaced'][] = $replacePath;
		}
		else if ($change)
		{
			$from = $remote . "/" . $change;

			$to = $targetPath . $change;

			if (!$dryRun)
			{
				rename($from, $to);
			}

			$stats['news'][] = $to;
		}
	}

	return [
		'stats' => $stats
	];
}
