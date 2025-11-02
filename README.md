# 🎧 Reaper Toolbox [![Build Installer](https://github.com/jakoch/reaper-toolbox/actions/workflows/build.yml/badge.svg?branch=main)](https://github.com/jakoch/reaper-toolbox/actions/workflows/build.yml) [![License](https://img.shields.io/github/license/jakoch/reaper-toolbox.svg)](https://github.com/jakoch/reaper-toolbox/blob/main/LICENSE.md)

### What is Reaper Toolbox?

Reaper Toolbox is an automated build system that creates a unified, portable
installer for the Reaper DAW.

It bundles the core application together with essential extensions such as SWS,
ReaPack, and related documentation.

A PHP-based build system handles version detection, component downloads,
and generation of Inno Setup installer scripts.

Weekly builds are automated via GitHub Actions and published directly to GitHub Releases.

### Why Use Reaper Toolbox?

Setting up Reaper with all essential extensions can be time-consuming.
Reaper Toolbox automates this process, it downloads, configures,
and packages everything into one ready-to-install bundle.
This ensures a consistent, up-to-date environment with minimal effort.

### Bundled Components

The installer includes the following components:

- **Reaper DAW**: Core digital audio workstation (portable installation).
- **Reaper User Guide**: Official PDF documentation.
- **SWS Extension**: Comprehensive extension suite enhancing Reaper functionality.
- **SWS User Guide**: PDF documentation for SWS features.
- **ReaPack**: Package manager for extensions and scripts.

#### What is Reaper DAW?

[Reaper](https://www.reaper.fm/) (Rapid Environment for Audio Production, Engineering, and Recording) is a powerful,
lightweight, and highly customizable digital audio workstation.
It supports multitrack audio and MIDI recording, editing, mixing, and mastering on Windows, macOS, and Linux.
Reaper is known for its small footprint, fast performance, and extensive scripting and extension capabilities.

#### What is SWS?

[SWS Extension](https://www.sws-extension.org/) (Standing Water Studios) is a free, open-source add-on for Reaper that greatly expands its functionality.
It provides hundreds of additional actions, tools, and workflow enhancements — including region management, auto-coloring, cycle actions, and project organization utilities.
Many advanced Reaper workflows depend on SWS for extended automation and customization.

#### What is ReaPack?

[ReaPack](https://reapack.com/) is a package manager for Reaper extensions, scripts, and plug-ins.
It allows users to easily browse, install, and update community-created Reaper tools from curated repositories.
ReaPack makes it simple to manage and update extensions from one place.

### Development Notes

#### Architecture

- **Build Script (`build-tools/build.php`)**: A PHP-based automation script that:
  - Defines version grabber classes for each component (Reaper, SWS Extension, ReaPack, user guides).
  - Scrapes official websites and APIs to retrieve the latest version numbers and download URLs.
  - Downloads all required files to the `downloads/` directory.
  - Generates an auto-generated Inno Setup include file (`installer/install.iss`) with installation commands for each component.
  - Outputs version information to `downloads/reaper_toolbox_versions.txt` and `release_notes.md`.

- **Installer Scripts**:
  - **Main Script (`installer/Reaper-Toolbox.iss`)**: The primary Inno Setup configuration file that defines the installer metadata, UI, file associations, and includes the auto-generated `install.iss` for component installation logic.
  - **Auto-Generated Script (`installer/install.iss`)**: Dynamically created by the build script, containing Pascal code for extracting and installing each component with progress updates.

- **Bundled Tools**:
  - **PHP Runtime (`build-tools/php/`)**: Embedded PHP executable for running the build script on Windows.
  - **Inno Setup Compiler (`build-tools/InnoSetup6/`)**: Compiler for creating the Windows installer executable.

#### Build Process

1. **Version Detection**: The PHP script queries official sources to get the latest versions of:
   - Reaper DAW (from reaper.fm)
   - Reaper User Guide PDF
   - SWS Extension (from sws-extension.org)
   - SWS User Guide PDF
   - ReaPack Extension (from GitHub API)

2. **Download Phase**: Downloads all components to `downloads/` (skipped if files already exist).

3. **Script Generation**: Creates `installer/install.iss` with installation steps for each component, including silent installs and file placements.

4. **Compilation**: Inno Setup compiles the installer to `release/Reaper-Toolbox-v{version}-x64.exe`.

5. **Testing**: An automated installation test is done to verify the installer works correctly.

#### Local Development

To build locally on Windows:

1. Run `build.bat` or execute:

   ```bash
   build-tools\php\php.exe build-tools\build.php
   build-tools\InnoSetup6\iscc.exe installer\Reaper-Toolbox.iss
   ```

2. Test with `test-installation.bat`.

#### TODO

- [x] ~Appveyor~
- [x] ~Azure Pipelines~ (disabled)
- [x] Github Actions CI
  - [x] ~Deployment: Weekly rolling Github Releases (Sunday)~
- [x] Grab Latest Versions
  - [x] Reaper
    - https://www.reaper.fm/download.php
      - Optional functionality provided by the Reaper installer:
        - [x] ReaMote network FX processing support
        - [x] ReWire
        - [x] ReaRoute ASIO driver
    - [x] Reaper User Guide
      - https://www.reaper.fm/userguide.php
    - [x] Reaper SWS
      - http://www.sws-extension.org/ & http://www.standingwaterstudios.com/
      - https://github.com/reaper-oss/sws
    - [x] Reaper SWS Guide
      - http://www.sws-extension.org/download/REAPERPlusSWS171.pdf
    - [x] ReaPack
      - https://github.com/cfillion/reapack/
      - https://github.com/cfillion/reapack/releases/latest
    - [ ] UltraSchall
      - https://github.com/Ultraschall
      - https://ultraschall.io/ultraschall_release.txt
- [x] Download
- [x] Build Installer
- [x] Release
