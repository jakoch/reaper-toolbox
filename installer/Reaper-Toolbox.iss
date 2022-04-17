;
;          _\|/_
;          (o o)
; +-----oOO-{_}-OOo------------------------------------------------------+
; |                                                                      |
; |  Reaper Toolbox - Inno Setup Script File                             |
; |  --------------------------------------------                        |
; |                                                                      |
; |  Reaper Toolbox is an installer for Reaper DAW.                      |
; |  It installs additional tools, like SWS and ReaPack out of the box.  |
; |                                                                      |
; |  Author:   Jens A. Koch <jakoch@web.de>                              |
; |  Website:  https://github.com/jakoch/reaper-toolbox                  |
; |  License:  MIT                                                       |
; |                                                                      |
; |  For the full copyright and license information, please view         |
; |  the LICENSE file that was distributed with this source code.        |
; |                                                                      |
; |  Note for developers                                                 |
; |  -------------------                                                 |
; |  A good resource for developing and understanding                    |
; |  Inno Setup Script files is the official "Inno Setup Help".          |
; |  Website:  http://jrsoftware.org/ishelp/index.php                    |
; |                                                                      |
; +---------------------------------------------------------------------<3

; version is set here, when the version isn't passed to the compiler on invocation
#ifndef APP_VERSION
#define APP_VERSION          "1.0.0"
#endif

; reset APP_VERSIONs like "dev-as7d6a" to simply "1.0.0" for VersionInfoVersion!
#define AppVersionStartsWithDev Pos("dev-", APP_VERSION)
#if AppVersionStartsWithDev == 1
  #define VERSION_INFO_VERSION "1.0.0"
  #define APP_VERSION          "1.0.0-" + APP_VERSION
#else 
  #define APP_VERSION_WITHOUT_LEADING_V StringChange(APP_VERSION, "v", "")
  #define APP_VERSION APP_VERSION_WITHOUT_LEADING_V
  #define VERSION_INFO_VERSION APP_VERSION_WITHOUT_LEADING_V
#endif

#define APP_NAME             "Reaper Toolbox"
#define APP_PUBLISHER        "Jens A. Koch"
#define APP_URL              "https://github.com/jakoch/reaper-toolbox"
#define APP_SUPPORT_URL      "https://github.com/jakoch/reaper-toolbox/issues/new/"
#define COPYRIGHT_YEAR        GetDateTimeString('yyyy', '', '');

[Setup]
; NOTE: The value of AppId uniquely identifies this application.
; Do not use the same AppId value in installers for other applications.
; (To generate a new GUID, click Tools | Generate GUID inside the IDE.)
AppId={{A7C79074-FBA9-42B1-A858-E142036E8EBE}} 
AppName={#APP_NAME}
AppVerName={#APP_NAME} {#APP_VERSION}
AppVersion={#APP_VERSION}
AppPublisher={#APP_PUBLISHER}
AppCopyright=© {#APP_PUBLISHER}
AppPublisherURL={#APP_URL}
AppSupportURL={#APP_SUPPORT_URL}
AppUpdatesURL={#APP_URL}

VersionInfoVersion={#VERSION_INFO_VERSION}
VersionInfoCompany={#APP_PUBLISHER}
VersionInfoDescription={#APP_NAME} {#APP_VERSION}
VersionInfoTextVersion={#APP_VERSION}
VersionInfoCopyright=Copyright (C) 2018 - {#COPYRIGHT_YEAR} {#APP_PUBLISHER}, All Rights Reserved.

OutputDir=..\release
OutputBaseFilename=Reaper-Toolbox-v{#APP_VERSION}-x64

; compression           
Compression=lzma2/ultra
LZMAUseSeparateProcess=yes
LZMANumBlockThreads=2
InternalCompressLevel=max
SolidCompression=true

; TODO: show a warning to the user to save & close any running applications
CloseApplications=no

; disable wizard pages: Languages, Ready
ShowLanguageDialog=no
DisableStartupPrompt=yes
DisableReadyPage=yes
CreateAppDir=yes
AppendDefaultDirName=yes
DefaultDirName={commonpf}\Reaper 
ShowComponentSizes=no
PrivilegesRequired=none
Uninstallable=no
DefaultGroupName=Reaper
 
; style
WizardStyle=modern
BackColor=clBlack 
;SetupIconFile={#SOURCE_ROOT}..\installer\icons\Setup.ico
;WizardImageFile={#SOURCE_ROOT}..\installer\icons\banner-left-164x314-standard.bmp
;WizardSmallImageFile={#SOURCE_ROOT}..\installer\icons\icon-topright-55x55-stamp.bmp

[Languages]
Name: english; MessagesFile: compiler:Default.isl

[Files]
; incorporate all files of the download folder for this installation wizard ;deleteafterinstall
Source: ..\downloads\*; DestDir: {tmp}; Flags: dontcopy

[Run]
Filename: "{app}\reaper.exe"; Description: "Launch Reaper DAW"; Flags: postinstall nowait skipifsilent
Filename: "{app}\reaper_toolbox_versions.txt"; Description: "Show versions of installed software"; Flags: postinstall shellexec skipifsilent

[Tasks]
Name: desktopicon; Description: "{cm:CreateDesktopIcon} for Reaper"; GroupDescription: "{cm:AdditionalIcons}"; Flags: unchecked
Name: quicklaunchicon; Description: "Create a &Quick Launch icon for Reaper"; GroupDescription: "{cm:AdditionalIcons}"; Flags: unchecked
Name: associate; Description: "&Associate Reaper project files (.rpp)"; Flags: unchecked

[Icons]
; create desktop icon
Name: "{userdesktop}\Reaper"; Filename: "{app}\reaper.exe"; Tasks: desktopicon
; create quick launch icon
Name: "{userappdata}\Microsoft\Internet Explorer\Quick Launch\Reaper"; Filename: "{app}\reaper.exe"; Tasks: quicklaunchicon
; create start-menu group
Name: "{group}\Reaper (x64)"; Filename: "{app}\reaper.exe"; WorkingDir: "{app}"
Name: "{group}\Reaper (x64) (create new project)"; Filename: "{app}\reaper.exe"; Parameters: "-new"; WorkingDir: "{app}"
Name: "{group}\Reaper (x64) (reset config to factory defaults)"; Filename: "{app}\reaper.exe"; Parameters: "-resetconfig"; WorkingDir: "{app}"
Name: "{group}\Reaper (x64) (show audio configuration on startup)"; Filename: "{app}\reaper.exe"; Parameters: "-audiocfg"; WorkingDir: "{app}"
Name: "{group}\Reaper (x64) User Guide"; Filename: "{app}\Docs\Reaper_User_Guide.pdf"; WorkingDir: "{app}"
Name: "{group}\Reaper (x64) SWS User Guide"; Filename: "{app}\Reaper_SWS_User_Guide.pdf"; WorkingDir: "{app}"
Name: "{group}\REAPER License and User Agreement"; Filename: "{app}\license.txt"; WorkingDir: "{app}"
Name: "{group}\Whatsnew.txt"; Filename: "{app}\whatsnew.txt"; WorkingDir: "{app}"
Name: "{group}\Show software versions"; Filename: "{app}\reaper_toolbox_versions.txt"; WorkingDir: "{app}"
    
[Registry]
; create file-association for ".rpp" files. this is an optional and user selectable task, see [Tasks] section.
Root: HKCR; Subkey: ".rpp";                       ValueData: "Reaper\";                      ValueType: string;  ValueName: ""; Flags: uninsdeletevalue; Tasks: associate
Root: HKCR; Subkey: "Reaper";                     ValueData: "Program Reaper\";              ValueType: string;  ValueName: ""; Flags: uninsdeletekey; Tasks: associate 
Root: HKCR; Subkey: "Reaper\DefaultIcon";         ValueData: "{app}\reaper.exe,0";           ValueType: string;  ValueName: ""; Tasks: associate
Root: HKCR; Subkey: "Reaper\shell\open\command";  ValueData: """{app}\reaper.exe"" ""%1""";  ValueType: string;  ValueName: ""; Tasks: associate

[Code]
procedure InstallProgressPage;
var
  ProgressPage: TOutputProgressWizardPage;
  ResultCode: Integer;
begin
    ProgressPage := CreateOutputProgressPage('Installing Reaper and additional components..', '');
    ProgressPage.Show;
    try    
      #include "install.iss"
             
      // Installation Script for "Reaper Toolbox Versions" file
      ExtractTemporaryFile('reaper_toolbox_versions.txt');
      RenameFile(ExpandConstant('{tmp}\reaper_toolbox_versions.txt'), ExpandConstant('{app}\reaper_toolbox_versions.txt'));
      
    finally
      ProgressPage.Hide;
    end;
end;

procedure CurStepChanged(CurStep: TSetupStep);
begin
  if CurStep = ssInstall then
  begin
    InstallProgressPage;  
  end;   
end;    