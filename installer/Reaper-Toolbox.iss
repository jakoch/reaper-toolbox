#define APP_VERSION          "0.1"

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

VersionInfoVersion={#APP_VERSION}
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
             
      // Installation Script for "Reaper Toolbox Versions file"
      RenameFile(ExpandConstant('{tmp}\versions.txt'), ExpandConstant('{app}\reaper_toolbox_versions.txt'));

      ProgressPage.SetProgress(100, 100);       
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