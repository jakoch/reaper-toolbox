<?php declare(strict_types=1);

/**
 * Reaper Toolbox - InnoSetup Installer Build Script
 *
 * SPDX-FileCopyrightText: 2018-2026 Jens A. Koch
 * SPDX-License-Identifier: MIT
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

error_reporting(E_ALL);

class Paths
{
  static function getDownloadFolder(): string
  {
    return __DIR__ . '/../downloads/';
  }
  static function getInstallerFolder(): string
  {
    return __DIR__ . '/../installer/';
  }
}

class Arrays
{
  static function flatten($array): array
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
  /** Some download endpoints reject requests without a browser-like user agent. */
  private const USER_AGENT = 'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2049.0 Safari/537.36';

  private const TIMEOUT = 20;

  /** Aborts the build with a uniform message for every unreachable-URL failure. */
  private function fail(string $url): never
  {
    fwrite(STDERR, sprintf("Website is not reachable: %s. Please try again later.%s", $url, PHP_EOL));
    exit(1);
  }

  function download(string $url): bool|string
  {
    if (!$this->isUrlReachable($url)) {
      $this->fail($url);
    }

    $opts = [
      'http' => [
        'user_agent' => self::USER_AGENT,
        'method' => 'GET',
        'header' => 'Content-type: text/plain;',
        'timeout' => self::TIMEOUT,
      ]
    ];

    return $this->downloadFileWithRetry($url, stream_context_create($opts));
  }

  /**
   * Probes the URL with a ranged GET and inspects the final response status.
   *
   * Downloads fail with a generic message when the host is down, which is hard
   * to tell apart from a 404. Checking up front lets the build report the
   * actual problem.
   */
  function isUrlReachable(string $url): bool
  {
    $opts = [
      'http' => [
        'method' => 'GET',
        'header' => 'Range: bytes=0-0',
        'user_agent' => self::USER_AGENT,
        'timeout' => self::TIMEOUT,
        'ignore_errors' => true,
      ]
    ];

    // get_headers() raises a warning on connection failures; we want a bool back.
    set_error_handler(static fn(int $severity): bool => $severity === E_WARNING, E_WARNING);

    try {
      $headers = get_headers($url, false, stream_context_create($opts));
    } finally {
      restore_error_handler();
    }

    if ($headers === false) {
      return false;
    }

    $statusCode = null;
    foreach ($headers as $header) {
      // With redirects the status line appears more than once; the last one wins.
      if (preg_match('/^HTTP\/\S+\s+(\d{3})\b/', $header, $matches)) {
        $statusCode = (int) $matches[1];
      }
    }

    return $statusCode !== null && $statusCode >= 200 && $statusCode < 400;
  }

  function downloadFileWithRetry(string $url, $context, int $retries = 5): bool|string
  {
    $attempt = 1;
    $content = false;

    while ($attempt <= $retries && !$content)
    {
        $content = file_get_contents($url, false, $context);

        if ($content === false) {
            if ($attempt < $retries) {
                sleep(5); // Wait 5 seconds before retrying
            }
            $attempt++;
        }
    }

    if ($content === false) {
        $this->fail($url);
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
  public string $name;
  public string $url;
  public ?string $latest_version = null;
  /** @var string[] */
  public array $downloads = [];
  public string $filename;

  function downloadJsonAsArray(string $url): array
  {
    $json = $this->download($url);

    return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
  }

  function getDownloads(): array
  {
    return $this->downloads;
  }
  function getLatestVersion(): ?string
  {
    return $this->latest_version;
  }
  function getName(): string
  {
    return $this->name;
  }
  function getUrl(): string
  {
    return $this->url;
  }
  function getFilename(): string
  {
    return $this->filename;
  }
}

class Reaper_VersionGrabber extends VersionGrabber
{
  public string $name = "Reaper";
  public string $url = 'https://reaper.fm/download.php';

  // tested strings "Version 6.11:" | "Version 6.12c:"
  private string $version_regexp = '/Version (\d+.\d+[a-z]?):/';

  //https://www.reaper.fm/files/5.x/reaper5981_x64-install.exe
  private string $download_regexp = '/files\/(.*)x64-install.exe/';
  private string $download_url_template = 'https://reaper.fm/%s';

  function grabVersion(): void
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

  function getInstallCommand(): string
  {
    $install_cmd_template = "Exec(ExpandConstant('{tmp}\%s'), '/S /PORTABLE /D=' + ExpandConstant('{app}'), '', SW_HIDE, ewWaitUntilTerminated, ResultCode);";

    return sprintf($install_cmd_template, $this->filename);
  }
}

class ReaperUserGuide_VersionGrabber extends VersionGrabber
{
  public string $name = "Reaper User Guide (en)";
  public string $url = 'https://reaper.fm/userguide.php';

  private string $version_regexp = '/Guide(.*)\.pdf/';

  // https://www.reaper.fm/userguide/ReaperUserGuide5981c.pdf
  private string $download_url_template = 'https://reaper.fm/userguide/ReaperUserGuide%s.pdf';

  function grabVersion(): void
  {
    $html = $this->download($this->url);

    if(preg_match($this->version_regexp, $html, $matches)) {
      $this->latest_version = $matches[1];
    }
    $url = sprintf($this->download_url_template, $this->latest_version);
    $this->downloads[] = $url;
    $this->filename = basename($url);
  }

  function getInstallCommand(): string
  {
    $install_cmd_template = "RenameFile(ExpandConstant('{tmp}\%s'), ExpandConstant('{app}\Docs\Reaper_User_Guide.pdf'));";

    return sprintf($install_cmd_template, $this->filename);
  }
}

class SWSExtension_VersionGrabber extends VersionGrabber
{
  public string $name = "Extension: SWS";
  public string $url = 'https://sws-extension.org/';

  // https://sws-extension.org/download/featured/sws-2.12.1.3-Windows-x64.exe
  private string $download_regexp = '/sws-(.*)-Windows-x64.exe/';
  private string $download_url_template = 'https://sws-extension.org/download/featured/%s';

  function grabVersion(): void
  {
    $html = $this->download($this->url);

    if(preg_match($this->download_regexp, $html, $matches)) {
      $this->latest_version = $matches[1];
      $url = sprintf($this->download_url_template, $matches[0]);
      $this->downloads[] = $url;
      $this->filename = basename($url);
    }
  }

  function getInstallCommand(): string
  {
    $install_cmd_template = "Exec(ExpandConstant('{tmp}\%s'), '/S /PORTABLE /D=' + ExpandConstant('{app}'), ExpandConstant('{tmp}'), SW_HIDE, ewWaitUntilTerminated, ResultCode);";

    return sprintf($install_cmd_template, $this->filename);
  }
}

class SWSExtensionUserGuide_VersionGrabber extends VersionGrabber
{
  public string $name = "Extension: SWS User Guide (en)";
  public string $url = 'https://sws-extension.org/';

  private string $version_regexp = '/REAPERPlusSWS(.*)\.pdf/';

  // http://www.standingwaterstudios.com/download/REAPERPlusSWS171.pdf
  private string $download_url_template = 'https://sws-extension.org/download/REAPERPlusSWS%s.pdf';

  function grabVersion(): void
  {
    $html = $this->download($this->url);

    if(preg_match($this->version_regexp, $html, $matches)) {
      $this->latest_version = $matches[1];
    }
    $url = sprintf($this->download_url_template, $this->latest_version);
    $this->downloads[] = $url;
    $this->filename = basename($url);
  }

  function getInstallCommand(): string
  {
    $install_cmd_template = "RenameFile(ExpandConstant('{tmp}\REAPERPlusSWS%s.pdf'), ExpandConstant('{app}\Docs\Reaper_SWS_User_Guide.pdf'));";

    return sprintf($install_cmd_template, $this->latest_version);
  }
}

class Reapack_VersionGrabber extends VersionGrabber
{
    public string $name = "Extension: Reapack";
    public string $url = 'https://github.com/cfillion/reapack';
    public string $api_url = 'https://api.github.com/repos/cfillion/reapack/releases/latest';

    function grabVersion(): void
    {
      $data = $this->downloadJsonAsArray($this->api_url);

      $this->latest_version = $data['name'];

      foreach($data['assets'] as $asset)
      {
        if(str_ends_with($asset['browser_download_url'], '64.dll')) {
          $this->downloads[] = $asset['browser_download_url'];
          $this->filename = basename($asset['browser_download_url']);
        }
      }
    }

    function getInstallCommand(): string
    {
      $install_cmd_template = "RenameFile(ExpandConstant('{tmp}\%s'), ExpandConstant('{app}\UserPlugins\%s'));";

      return sprintf($install_cmd_template, $this->filename, $this->filename);
    }
}

class VersionDisplay
{
  /** @var VersionGrabber[] */
  private array $grabbers = [];

  function setVersionGrabber(object $grabber): void
  {
    $this->grabbers[] = $grabber;
  }
  function printVersionTable(): string
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
  function printReleaseDescription(): string
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
  function writeFile(): void
  {
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
  /** @var array<int, string|string[]> */
  private array $downloads = [];

  function __construct()
  {
    if(!is_dir(Paths::getDownloadFolder())) {
      mkdir(Paths::getDownloadFolder());
    }
  }

  function setDownloads(array $downloads): void
  {
    $this->downloads[] = $downloads;
  }

  function downloadAll(): void
  {
    $this->downloads = Arrays::flatten($this->downloads);

    foreach($this->downloads as $downloadUrl) {
      $this->downloadFile($downloadUrl);
    }
  }

  function downloadFile(string $url): void
  {
    $file = Paths::getDownloadFolder() . basename($url);

    if(!file_exists($file)) {
      file_put_contents($file, $this->download($url));
    }
  }
}

class InnosetupGenerator
{
    private string $innosetupIncludeFile = 'install.iss';
    /** @var VersionGrabber[] */
    private array $grabbers = [];

    function setVersionGrabber(object $grabber): void
    {
      $this->grabbers[] = $grabber;
    }

    function generate(): string
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

    function writeFile(): void
    {
        $file = Paths::getInstallerFolder() . $this->innosetupIncludeFile;

        file_put_contents($file, $this->generate());
    }
}

class Application
{
  /** @var VersionGrabber[] */
  private array $grabbers = [];
  protected Downloader $downloader;
  protected VersionDisplay $versionsDisplay;
  protected InnosetupGenerator $innosetupGenerator;

  function __construct()
  {
    $this->downloader = new Downloader;
    $this->versionsDisplay = new VersionDisplay;
    $this->innosetupGenerator = new InnosetupGenerator;
  }

  function setVersionGrabber(object $grabber): void
  {
    $this->grabbers[] = $grabber;
  }

  function exec(): void
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
