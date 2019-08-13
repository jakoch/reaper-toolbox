@echo off

build-tools\php\php.exe build-tools\build.php

ls -lhs downloads

build-tools\InnoSetup6\iscc.exe installer\Reaper-Toolbox.iss

ls -lhs release