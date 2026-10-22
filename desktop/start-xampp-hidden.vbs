Set shell = CreateObject("WScript.Shell")
Set fileSystem = CreateObject("Scripting.FileSystemObject")
scriptPath = fileSystem.GetParentFolderName(WScript.ScriptFullName) & "\start-xampp.ps1"
If Not fileSystem.FileExists(scriptPath) Then
    scriptPath = "C:\xampp\htdocs\proj\desktop\start-xampp.ps1"
End If
command = "powershell.exe -NoProfile -ExecutionPolicy Bypass -File " & Chr(34) & scriptPath & Chr(34)
shell.Run command, 0, False
