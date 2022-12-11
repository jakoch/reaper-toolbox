# Reaper Toolbox [![Build Status](https://dev.azure.com/jakoch/jakoch/_apis/build/status/jakoch.reaper-toolbox?branchName=main)](https://dev.azure.com/jakoch/jakoch/_build/latest?definitionId=1&branchName=main) [![License](https://img.shields.io/github/license/jakoch/reaper-toolbox.svg)](https://github.com/jakoch/reaper-toolbox/blob/main/LICENSE.md)

This repository builds an installer for Reaper DAW, which contains Reaper and the extensions SWS and ReaPack.

#### DEV

A PHP script is used for version detection, downloading of components and to build the Innosetup-based installer.

Builds are done continously on a weekly schedule using Azure Pipelines and deployed to Github Releases.

##### TODO
- [x] ~Appveyor~
- [x] Azure Pipelines 
   - [x] Deployment: Weekly rolling Github Releases (Sunday) 
- [x] Grab Latest Versions
   - [x] Reaper 
       - https://www.reaper.fm/download.php
       - Optional functionality provided by the Reaper installer:
         - [x] ReaMote network FX processing support
         - [x] ReWire
         - [x] ReaRoute ASIo driver
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
