<?php declare(strict_types=1);

/**
 * Reaper Toolbox - InnoSetup Installer Build Script
 *
 * SPDX-FileCopyrightText: 2018-2025 Jens A. Koch
 * SPDX-License-Identifier: MIT
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

error_reporting(E_ALL);

class Paths
{
  static function getDownloadFolder()
  {
    return __DIR__ . '/../downloads/';
  }
  static function getInstallerFolder()
  {
    return __DIR__ . '/../installer/';
  }
}

class Strings
{
  static function endsWith($haystack, $needle) {
    return substr_compare($haystack, $needle, -strlen($needle)) === 0;
  }
  static function startsWith($haystack, $needle) {
    return substr_compare($haystack, $needle, 0, strlen($needle)) === 0;
  }
}

class Arrays
{
  static function flatten($array)
  {
    $return = array();
    foreach ($array as $key => $value) {
        if (is_array($value)){
            $return = array_merge($return, self::flatten($value));
        } else {
            $return[$key] = $value;
        }
    }

    return $return;
  }
}

// GET https://api.github.com/repos/:owner/:repo/releases/latest

class DownloadUtil
{
  function download($url)
  {
    $opts = [
      'http' =>[
        //'user_agent' => 'Reaper-Toolbox-Installer-Build-Script (https://github.com/jakoch/reaper-toolbox/)',
        'user_agent' => 'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2049.0 Safari/537.36',
        'method' => 'GET',
        'header' => implode("\r\n", ['Content-type: text/plain;']),
        'timeout' => 20,
      ]
    ];

    $context = stream_context_create($opts);

    return $this->downloadFileWithRetry($url, $context);
  }

  function downloadFileWithRetry($url, $context, $retries = 5)
  {
    $attempt = 1;
    $content = false;

    while ($attempt <= $retries && !$content)
    {
        $content = file_get_contents($url, false, $context);

        if ($content === false) {
            sleep(5); // Wait 5 seconds before retrying
            $attempt++;
        }
    }

    // check filesize
    if (strlen($content) === 0) {
        die("Downloaded failed. Filesize is 0.");
    }

    return $content;
  }
}

class VersionGrabber extends DownloadUtil
{
  public $name;
  public $url;
  public $latest_version;
  public $downloads = [];
  public $filename;

  function downloadJsonAsArray($url)
  {
    $json = $this->download($url);

    return json_decode($json, 1);
  }

  function getDownloads()
  {
    return $this->downloads;
  }
  function getLatestVersion()
  {
    return $this->latest_version;
  }
  function getName()
  {
    return $this->name;
  }
  function getUrl()
  {
    return $this->url;
  }
  function getFilename()
  {
    return $this->filename;
  }
}

class Reapack_VersionGrabber extends VersionGrabber
{
    public $name = "Extension: Reapack";
    public $url = 'https://github.com/cfillion/reapack';
    public $api_url = 'https://api.github.com/repos/cfillion/reapack/releases/latest';

    function grabVersion()
    {
      $data = $this->downloadJsonAsArray($this->api_url);

      $this->latest_version = $data['name'];

      foreach($data['assets'] as $asset)
      {
        if(Strings::endsWith($asset['browser_download_url'], '64.dll')) {
          $this->downloads[] = $asset['browser_download_url'];
          $this->filename = basename($asset['browser_download_url']);
        }
      }
    }

    function getInstallCommand()
    {
      $install_cmd_template = "RenameFile(ExpandConstant('{tmp}\%s'), ExpandConstant('{app}\UserPlugins\%s'));";

      return sprintf($install_cmd_template, $this->filename, $this->filename);
    }
}

class Reaper_VersionGrabber extends VersionGrabber
{
  public $name = "Reaper";
  public $url = 'https://reaper.fm/download.php';

  // tested strings "Version 6.11:" | "Version 6.12c:"
  private $version_regexp = '/Version (\d+.\d+[a-z]?):/';

  //https://www.reaper.fm/files/5.x/reaper5981_x64-install.exe
  private $download_regexp = '/files\/(.*)x64-install.exe/';
  private $download_url_template = 'https://reaper.fm/%s';

  function grabVersion()
  {
    $html = $this->download($this->url);

    if(preg_match($this->version_regexp, $html, $matches)) {
      $this->latest_version = $matches[1];
    }

    if(preg_match($this->download_regexp, $html, $matches)) {
      $url = sprintf($this->download_url_template, $matches[0]);
      $this->downloads[] = $url;
      $this->filename = basename($url);
    }
  }

  function getInstallCommand()
  {
    $install_cmd_template = "Exec(ExpandConstant('{tmp}\%s'), '/S /PORTABLE /D=' + ExpandConstant('{app}'), '', SW_HIDE, ewWaitUntilTerminated, ResultCode);";

    return sprintf($install_cmd_template, $this->filename);
  }
}

class ReaperUserGuide_VersionGrabber extends VersionGrabber
{
  public $name = "Reaper User Guide (en)";
  public $url = 'https://reaper.fm/userguide.php';

  private $version_regexp = '/Guide(.*)\.pdf/';

  // https://www.reaper.fm/userguide/ReaperUserGuide5981c.pdf
  private $download_url_template = 'https://reaper.fm/userguide/ReaperUserGuide%s.pdf';

  function grabVersion()
  {
    $html = $this->download($this->url);

    if(preg_match($this->version_regexp, $html, $matches)) {
      $this->latest_version = $matches[1];
    }

    $this->downloads[] = sprintf($this->download_url_template, $this->latest_version);
    $this->filename = basename(sprintf($this->download_url_template, $this->latest_version));
  }

  function getInstallCommand()
  {
    $install_cmd_template = "RenameFile(ExpandConstant('{tmp}\%s'), ExpandConstant('{app}\Docs\Reaper_User_Guide.pdf'));";

    return sprintf($install_cmd_template, $this->filename);
  }
}

class SWSExtension_VersionGrabber extends VersionGrabber
{
  public $name = "Extension: SWS";
  public $url = 'https://sws-extension.org/';

  // https://sws-extension.org/download/featured/sws-2.12.1.3-Windows-x64.exe
  private $download_regexp = '/sws-(.*)-Windows-x64.exe/';
  private $download_url_template = 'https://sws-extension.org/download/featured/%s';

  function grabVersion()
  {
    $html = $this->download($this->url);

    if(preg_match($this->download_regexp, $html, $matches)) {
      $this->latest_version = $matches[1];
      $this->downloads[] = sprintf($this->download_url_template, $matches[0]);
      $this->filename = basename(sprintf($this->download_url_template, $matches[0]));

    }
  }

  function getInstallCommand()
  {
    $install_cmd_template = "Exec(ExpandConstant('{tmp}\%s'), '/S /PORTABLE /D=' + ExpandConstant('{app}'), ExpandConstant('{tmp}'), SW_HIDE, ewWaitUntilTerminated, ResultCode);";

    return sprintf($install_cmd_template, $this->filename);
  }
}

class SWSExtensionUserGuide_VersionGrabber extends VersionGrabber
{
  public $name = "Extension: SWS User Guide (en)";
  public $url = 'https://sws-extension.org/';

  private $version_regexp = '/REAPERPlusSWS(.*)\.pdf/';

  // http://www.standingwaterstudios.com/download/REAPERPlusSWS171.pdf
  private $download_url_template = 'https://sws-extension.org/download/REAPERPlusSWS%s.pdf';

  function grabVersion()
  {
    $html = $this->download($this->url);

    if(preg_match($this->version_regexp, $html, $matches)) {
      $this->latest_version = $matches[1];
    }

    $this->downloads[] = sprintf($this->download_url_template, $this->latest_version);
    $this->filename =  basename(sprintf($this->download_url_template, $this->latest_version));
  }

  function getInstallCommand()
  {
    $install_cmd_template = "RenameFile(ExpandConstant('{tmp}\REAPERPlusSWS%s.pdf'), ExpandConstant('{app}\Docs\Reaper_SWS_User_Guide.pdf'));";

    return sprintf($install_cmd_template, $this->latest_version, $this->latest_version);
  }
}

class VersionDisplay
{
  private $grabbers = [];

  function setVersionGrabber(object $grabber)
  {
    $this->grabbers[] = $grabber;
  }
  function printVersionTable()
  {
    $template = "| %-30.30s | %-9.9s | %-42.42s |" . PHP_EOL;
    // header
    $out = sprintf($template, 'Component', 'Version', 'URL');
    // line separator
    $out .= sprintf($template, '------------------------------', '---------', '-----------------------------------------');
    // rows
    foreach($this->grabbers as $grabber) {
      $out .= sprintf(
        $template,
        $grabber->getName(),
        $grabber->getLatestVersion(),
        $grabber->getUrl()
      );
    }
    return $out;
  }
  function printReleaseDescription()
  {
    $template = "%s %s\n";
    $out = '';
    foreach($this->grabbers as $grabber) {
      $out .= sprintf(
        $template,
        $grabber->getName(),
        $grabber->getLatestVersion()
      );
    }
    return $out;
  }
  function writeFile() {
    $file = Paths::getDownloadFolder().'reaper_toolbox_versions.txt';

    if(!file_exists($file)) {
      file_put_contents($file, $this->printVersionTable());
    }

    $desc = $this->printReleaseDescription();

    // Note: this approach is used to set the Github Release Notes on Azure-Pipelines
    $file3 = __DIR__ . '/../release_notes.md';
    file_put_contents($file3, $desc);
  }
}

class Downloader extends DownloadUtil
{
  private $downloads = [];

  function __construct()
  {
    if(!is_dir(Paths::getDownloadFolder())) {
      mkdir(Paths::getDownloadFolder());
    }
  }

  function setDownloads(array $downloads)
  {
    $this->downloads[] = $downloads;
  }

  function downloadAll()
  {
    $this->downloads = Arrays::flatten($this->downloads);

    foreach($this->downloads as $downloadUrl) {
      $this->downloadFile($downloadUrl);
    }
  }

  function downloadFile($url)
  {
    $file = Paths::getDownloadFolder() . basename($url);

    if(!file_exists($file)) {
      file_put_contents($file, $this->download($url));
    }
  }
}

class InnosetupGenerator
{
    private $innosetupIncludeFile = 'install.iss';
    private $grabbers = [];

    function setVersionGrabber(object $grabber)
    {
      $this->grabbers[] = $grabber;
    }

    function generate()
    {
        $max_num_components = count($this->grabbers);
        $progress = 1;

		$lines = [
			'// ===============================================================',
			'// This is an auto-generated Inno Setup installation script.',
			'// Modifications to this file will be overwritten!',
			'// ===============================================================',
			'',
			'',
			'// ===== Installation Steps for Components =====',
		];

		$out = implode(PHP_EOL, $lines) . PHP_EOL;

        foreach($this->grabbers as $component)
        {
            $s0 = '// Installation Script for "%s"';
            $out .= sprintf($s0, $component->getName());
            $out .= PHP_EOL;

            $s1 = "ProgressPage.Msg1Label.Caption := 'Installing %s';";
            $out .= sprintf($s1, $component->getName());
            $out .= PHP_EOL;

            $s2 = "ProgressPage.SetProgress(%s, ".$max_num_components.");";
            $out .= sprintf($s2, $progress++);
            $out .= PHP_EOL;

            $s3 = "ExtractTemporaryFile('%s');";
            $out .= sprintf($s3, $component->getFilename());
            $out .= PHP_EOL;

            // install logic part
            // - some files are copied from temp to the target folder
            // - some executables need silent installation into the target folder
            $out .= $component->getInstallCommand();
            $out .= PHP_EOL;

            // NewLine
            $out .= PHP_EOL;
        }

        return $out;
    }

    function writeFile()
    {
        $file = Paths::getInstallerFolder() . $this->innosetupIncludeFile;

        file_put_contents($file, $this->generate());
    }
}

class Application
{
  private $grabbers = [];
  protected $downloader;
  protected $versionsDisplay;
  protected $innosetupGenerator;

  function __construct()
  {
    $this->downloader = new Downloader;
    $this->versionsDisplay = new VersionDisplay;
    $this->innosetupGenerator = new InnosetupGenerator;
  }

  function setVersionGrabber(object $grabber)
  {
    $this->grabbers[] = $grabber;
  }

  function exec()
  {
    $this->setVersionGrabber(new Reaper_VersionGrabber);
    $this->setVersionGrabber(new ReaperUserGuide_VersionGrabber);
    $this->setVersionGrabber(new SWSExtension_VersionGrabber);
    $this->setVersionGrabber(new SWSExtensionUserGuide_VersionGrabber);
    $this->setVersionGrabber(new Reapack_VersionGrabber);

    foreach($this->grabbers as $grabber)
    {
      $grabber->grabVersion();
      $this->downloader->setDownloads($grabber->getDownloads());
      $this->versionsDisplay->setVersionGrabber($grabber);
      $this->innosetupGenerator->setVersionGrabber($grabber);
    }

    $this->downloader->downloadAll();

    echo $this->versionsDisplay->printVersionTable();

    $this->versionsDisplay->writeFile();

    //echo $this->innosetupGenerator->generate();

    $this->innosetupGenerator->writeFile();
  }
}

(new Application)->exec();
