@echo off

build-tools\php\php.exe build-tools\build.php

dir downloads

build-tools\InnoSetup6\iscc.exe installer\Reaper-Toolbox.iss

dir release