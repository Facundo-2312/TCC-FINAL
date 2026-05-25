#define MyAppName "Restaurante-UY Desktop"
#define MyAppVersion "1.0.0"
#define MyAppPublisher "Restaurante-UY"
#define MyAppURL "http://127.0.0.1:8030/Login.php"
#define MyAppExeName "desktop\\start-hidden.vbs"
#define MyAppStageDir "build\\app"

[Setup]
AppId={{A84856C6-49E7-4AD2-8D14-C49C6D9FA3D8}
AppName={#MyAppName}
AppVersion={#MyAppVersion}
AppPublisher={#MyAppPublisher}
AppPublisherURL={#MyAppURL}
DefaultDirName={autopf}\Restaurante-UY Desktop
DefaultGroupName={#MyAppName}
DisableProgramGroupPage=yes
OutputDir=build\installer
OutputBaseFilename=Restaurante-UY-Desktop-Setup
Compression=lzma
SolidCompression=yes
WizardStyle=modern
ArchitecturesAllowed=x64compatible
ArchitecturesInstallIn64BitMode=x64compatible
PrivilegesRequired=admin

[Languages]
Name: "spanish"; MessagesFile: "compiler:Languages\Spanish.isl"

[Tasks]
Name: "desktopicon"; Description: "Crear acceso directo en el escritorio"; GroupDescription: "Accesos directos:"; Flags: unchecked

[Files]
Source: "{#MyAppStageDir}\*"; DestDir: "{app}"; Flags: ignoreversion recursesubdirs createallsubdirs

[Icons]
Name: "{autoprograms}\{#MyAppName}"; Filename: "{app}\{#MyAppExeName}"
Name: "{autodesktop}\{#MyAppName}"; Filename: "{app}\{#MyAppExeName}"; Tasks: desktopicon
Name: "{autoprograms}\{#MyAppName}\Detener servidor local"; Filename: "{app}\desktop\stop-desktop.bat"
Name: "{autoprograms}\{#MyAppName}\Carpeta de instalación"; Filename: "{app}"

[Run]
Filename: "{app}\desktop\start-hidden.vbs"; Description: "Abrir Restaurante-UY Desktop"; Flags: nowait postinstall skipifsilent
